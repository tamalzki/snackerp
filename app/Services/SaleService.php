<?php
namespace App\Services;

use App\Models\BranchInventory;
use App\Models\FinishedProduct;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function store(array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $userId) {

            // STEP 1 — Validate branch stock for ALL items first
            foreach ($data['items'] as $item) {
                $product   = FinishedProduct::findOrFail($item['finished_product_id']);
                $inventory = BranchInventory::where('branch_id', $data['branch_id'])
                    ->where('finished_product_id', $item['finished_product_id'])
                    ->lockForUpdate()
                    ->first();

                $available = $inventory ? $inventory->stock_quantity : 0;

                if ($available < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient branch stock for [{$product->name}].
                                    Available: {$available},
                                    Requested: {$item['quantity']}"
                    ]);
                }
            }

            // STEP 2 — Calculate totals
            $totalAmount = 0;
            $totalCost   = 0;
            $resolvedItems = [];

            foreach ($data['items'] as $item) {
                $inventory = BranchInventory::where('branch_id', $data['branch_id'])
                    ->where('finished_product_id', $item['finished_product_id'])
                    ->first();

                $costSnapshot = $inventory->cost_snapshot ?? 0;
                $totalPrice   = $item['quantity'] * $item['unit_price'];
                $lineCost     = $item['quantity'] * $costSnapshot;

                $totalAmount += $totalPrice;
                $totalCost   += $lineCost;

                $resolvedItems[] = [
                    'finished_product_id' => $item['finished_product_id'],
                    'quantity'            => $item['quantity'],
                    'unit_price'          => $item['unit_price'],
                    'cost_snapshot'       => $costSnapshot,
                    'total_price'         => $totalPrice,
                ];
            }

            // STEP 3 — Create sale header
            $sale = Sale::create([
                'branch_id'    => $data['branch_id'],
                'sale_date'    => $data['sale_date'],
                'total_amount' => $totalAmount,
                'total_cost'   => $totalCost,
                'notes'        => $data['notes'] ?? null,
                'created_by'   => $userId,
            ]);

            // STEP 4 — Save items and deduct branch inventory
            foreach ($resolvedItems as $item) {
                $sale->items()->create($item);

                BranchInventory::where('branch_id', $data['branch_id'])
                    ->where('finished_product_id', $item['finished_product_id'])
                    ->decrement('stock_quantity', $item['quantity']);
            }

            return $sale;
        });
    }
}