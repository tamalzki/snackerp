<?php

namespace App\Services;

use App\Models\BranchInventory;
use App\Models\ConsignmentReceivable;
use App\Models\FinishedProduct;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferService
{
    public function store(array $data, int $userId): StockTransfer
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $userId) {

            $transfer = StockTransfer::create([
                'dr_number' => $data['dr_number'] ?? null,
                'branch_id' => $data['branch_id'],
                'transfer_date' => $data['transfer_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                $product = FinishedProduct::lockForUpdate()->findOrFail($item['finished_product_id']);

                if ($product->current_stock < $item['quantity']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => "Insufficient warehouse stock for {$product->name}. Available: {$product->current_stock}",
                    ]);
                }

                // Deduct from warehouse
                $product->decrement('current_stock', $item['quantity']);

                // Save transfer item
                $transfer->items()->create([
                    'finished_product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'cost_snapshot' => $product->average_cost,
                ]);

                // Add to branch inventory (weighted average cost_snapshot)
                $unitCost = (float) $product->average_cost;
                $lineQty = (float) $item['quantity'];

                $branchInventory = \App\Models\BranchInventory::firstOrCreate(
                    [
                        'branch_id' => $data['branch_id'],
                        'finished_product_id' => $product->id,
                    ],
                    [
                        'stock_quantity' => 0,
                        'cost_snapshot' => $unitCost,
                    ]
                );

                $branchInventory = \App\Models\BranchInventory::whereKey($branchInventory->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $oldQty = (float) $branchInventory->stock_quantity;
                $oldSnap = (float) $branchInventory->cost_snapshot;
                $totalQty = $oldQty + $lineQty;
                $newSnap = $totalQty > 0
                    ? (($oldQty * $oldSnap) + ($lineQty * $unitCost)) / $totalQty
                    : $unitCost;

                $branchInventory->increment('stock_quantity', $lineQty);
                $branchInventory->update(['cost_snapshot' => round($newSnap, 4)]);
            }

            // Auto-create consignment receivable at selling price
            $totalSellingAmount = 0;
            foreach ($transfer->items as $item) {
                $product = FinishedProduct::find($item->finished_product_id);
                $totalSellingAmount += $item->quantity * $product->selling_price;
            }

            ConsignmentReceivable::create([
                'stock_transfer_id' => $transfer->id,
                'branch_id' => $transfer->branch_id,
                'dr_number' => $transfer->dr_number,
                'total_amount' => $totalSellingAmount,
                'amount_paid' => 0,
                'amount_returned' => 0,
                'balance' => $totalSellingAmount,
                'status' => 'open',
                'delivery_date' => $transfer->transfer_date,
                'created_by' => $userId,
            ]);

            return $transfer;
        });
    }

    /**
     * Branch → branch: moves stock from source branch inventory to destination;
     * creates a new StockTransfer + ConsignmentReceivable (new DR) for the destination at selling value.
     */
    public function storeBranchToBranch(array $data, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($data, $userId) {
            $sourceBranchId = (int) $data['source_branch_id'];
            $destBranchId = (int) $data['destination_branch_id'];

            if ($sourceBranchId === $destBranchId) {
                throw ValidationException::withMessages([
                    'destination_branch_id' => 'Destination must be a different branch.',
                ]);
            }

            $reason = trim((string) ($data['reason'] ?? ''));
            $notes = '[Branch→Branch] '.($reason !== '' ? $reason : 'No reason given');

            $transfer = StockTransfer::create([
                'dr_number' => $data['dr_number'] ?? null,
                'branch_id' => $destBranchId,
                'source_branch_id' => $sourceBranchId,
                'transfer_date' => $data['transfer_date'],
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            $totalSellingAmount = 0;

            foreach ($data['items'] as $item) {
                $product = FinishedProduct::lockForUpdate()->findOrFail($item['finished_product_id']);
                $qty = (float) $item['quantity'];

                $sourceInv = BranchInventory::where('branch_id', $sourceBranchId)
                    ->where('finished_product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                $available = $sourceInv ? (float) $sourceInv->stock_quantity : 0.0;
                if (! $sourceInv || $available < $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock at source branch for {$product->name}. Available: ".\qty_fmt($available),
                    ]);
                }

                $unitCost = (float) $sourceInv->cost_snapshot;
                $sourceInv->decrement('stock_quantity', $qty);

                $destInv = BranchInventory::firstOrCreate(
                    [
                        'branch_id' => $destBranchId,
                        'finished_product_id' => $product->id,
                    ],
                    [
                        'stock_quantity' => 0,
                        'cost_snapshot' => $unitCost,
                    ]
                );

                $destInv = BranchInventory::whereKey($destInv->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $oldQty = (float) $destInv->stock_quantity;
                $oldSnap = (float) $destInv->cost_snapshot;
                $totalQty = $oldQty + $qty;
                $newSnap = $totalQty > 0
                    ? (($oldQty * $oldSnap) + ($qty * $unitCost)) / $totalQty
                    : $unitCost;

                $destInv->increment('stock_quantity', $qty);
                $destInv->update(['cost_snapshot' => round($newSnap, 4)]);

                $transfer->items()->create([
                    'finished_product_id' => $product->id,
                    'quantity' => $qty,
                    'cost_snapshot' => $unitCost,
                ]);

                $totalSellingAmount += $qty * (float) $product->selling_price;
            }

            ConsignmentReceivable::create([
                'stock_transfer_id' => $transfer->id,
                'branch_id' => $destBranchId,
                'dr_number' => $transfer->dr_number,
                'total_amount' => $totalSellingAmount,
                'amount_paid' => 0,
                'amount_returned' => 0,
                'balance' => $totalSellingAmount,
                'status' => 'open',
                'delivery_date' => $transfer->transfer_date,
                'created_by' => $userId,
            ]);

            return $transfer;
        });
    }

    public function storePullOut(array $data, int $userId): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $returnValue = 0;

            foreach ($data['items'] as $item) {
                if (empty($item['quantity']) || $item['quantity'] <= 0) {
                    continue;
                }

                $product = FinishedProduct::lockForUpdate()
                    ->findOrFail($item['finished_product_id']);

                // Return stock to warehouse
                $product->increment('current_stock', $item['quantity']);

                // Deduct from branch inventory
                $branchInv = BranchInventory::where('branch_id', $data['branch_id'])
                    ->where('finished_product_id', $product->id)
                    ->first();

                if ($branchInv) {
                    $branchInv->decrement('stock_quantity', $item['quantity']);
                }

                $returnValue += $item['quantity'] * $product->selling_price;
            }

            // Reduce receivable balance
            if ($returnValue > 0 && ! empty($data['consignment_receivable_id'])) {
                $receivable = ConsignmentReceivable::find($data['consignment_receivable_id']);
                if ($receivable) {
                    $receivable->applyReturn($returnValue);
                }
            }
        });
    }
}
