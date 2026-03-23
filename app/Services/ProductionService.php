<?php
namespace App\Services;

use App\Models\FinishedProduct;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionService
{
    public function store(array $data, int $userId): ProductionBatch
    {
        return DB::transaction(function () use ($data, $userId) {

            // STEP 1 — Validate ALL stock before touching anything
            foreach ($data['items'] as $item) {
                $rm = RawMaterial::lockForUpdate()->findOrFail($item['raw_material_id']);

                if ($rm->stock_quantity < $item['quantity_used']) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for [{$rm->name}].
                                    Available: {$rm->stock_quantity} {$rm->unit},
                                    Needed: {$item['quantity_used']} {$rm->unit}.
                                    Please purchase more stock before proceeding."
                    ]);
                }
            }

            // STEP 2 — Calculate total cost using cost snapshots
            $totalCost     = 0;
            $resolvedItems = [];

            foreach ($data['items'] as $item) {
                $rm       = RawMaterial::find($item['raw_material_id']);
                $lineCost = $item['quantity_used'] * $rm->cost_per_unit;
                $totalCost += $lineCost;

                $resolvedItems[] = [
                    'raw_material_id' => $rm->id,
                    'quantity_used'   => $item['quantity_used'],
                    'cost_snapshot'   => $rm->cost_per_unit,
                    'total_cost'      => $lineCost,
                ];
            }

            $actualQty   = $data['actual_output_qty'];
            $costPerUnit = $actualQty > 0 ? $totalCost / $actualQty : 0;

            // STEP 3 — Create batch header
            $batch = ProductionBatch::create([
                'finished_product_id'     => $data['finished_product_id'],
                'expected_output_qty'     => $data['expected_output_qty'],
                'actual_output_qty'       => $actualQty,
                'reject_qty'              => $data['reject_qty'] ?? 0,
                'total_raw_material_cost' => $totalCost,
                'cost_per_unit'           => $costPerUnit,
                'production_date'         => $data['production_date'],
                'expiry_date'             => $data['expiry_date'] ?? null,
                'created_by'              => $userId,
            ]);

            // STEP 4 — Save items and deduct raw materials
            foreach ($resolvedItems as $item) {
                $batch->items()->create($item);
                RawMaterial::where('id', $item['raw_material_id'])
                    ->decrement('stock_quantity', $item['quantity_used']);
            }

            // STEP 5 — Update finished product stock + weighted average cost
            $product = FinishedProduct::lockForUpdate()
                ->findOrFail($data['finished_product_id']);

            $existingValue = $product->current_stock * $product->average_cost;
            $newValue      = $existingValue + $totalCost;
            $newStock      = $product->current_stock + $actualQty;
            $newAvgCost    = $newStock > 0 ? $newValue / $newStock : 0;

            $product->update([
                'current_stock' => $newStock,
                'average_cost'  => $newAvgCost,
            ]);

            return $batch;
        });
    }
  
    
}