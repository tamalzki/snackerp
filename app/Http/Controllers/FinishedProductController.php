<?php

namespace App\Http\Controllers;

use App\Models\FinishedProduct;
use App\Models\FinishedProductStockAdjustment;
use App\Models\ProductRestock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinishedProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $type = $request->get('type');

        $products = FinishedProduct::when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('finished-products.index', compact('products', 'search', 'type'));
    }

    public function create()
    {
        return view('finished-products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:finished_products,name',
            'type' => 'required|in:manufactured,resale',
            'selling_price' => 'required|numeric|min:0',
            'low_stock_threshold' => 'nullable|numeric|min:0',
        ]);

        FinishedProduct::create([
            'name' => $request->name,
            'type' => $request->type,
            'selling_price' => $request->selling_price,
            'low_stock_threshold' => $request->low_stock_threshold ?? 0,
        ]);

        return redirect()->route('finished-products.index')
            ->with('success', 'Finished product added successfully.');
    }

    public function show(FinishedProduct $finishedProduct)
    {
        $finishedProduct->load('productionBatches', 'restocks.creator');

        $stockAdjustments = $finishedProduct->stockAdjustments()
            ->latest()
            ->limit(50)
            ->with('creator')
            ->get();

        return view('finished-products.show', compact('finishedProduct', 'stockAdjustments'));
    }

    public function edit(FinishedProduct $finishedProduct)
    {
        return view('finished-products.edit', compact('finishedProduct'));
    }

    public function update(Request $request, FinishedProduct $finishedProduct)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:finished_products,name,'.$finishedProduct->id,
            'selling_price' => 'required|numeric|min:0',
            'low_stock_threshold' => 'nullable|numeric|min:0',
        ]);

        $finishedProduct->update([
            'name' => $request->name,
            'selling_price' => $request->selling_price,
            'low_stock_threshold' => $request->low_stock_threshold ?? 0,
        ]);

        return redirect()->route('finished-products.index')
            ->with('success', 'Finished product updated successfully.');
    }

    public function destroy(FinishedProduct $finishedProduct)
    {
        if ((float) $finishedProduct->current_stock > 0) {
            return redirect()->route('finished-products.index')
                ->with('error', 'Cannot delete: warehouse still has stock for this product.');
        }

        if ($finishedProduct->branchInventory()->where('stock_quantity', '>', 0)->exists()) {
            return redirect()->route('finished-products.index')
                ->with('error', 'Cannot delete: one or more branches still hold this product.');
        }

        $finishedProduct->delete();

        return redirect()->route('finished-products.index')
            ->with('success', 'Finished product deleted.');
    }

    // ── Restock (Resale only) ──────────────────────────────

    public function restockForm(FinishedProduct $finishedProduct)
    {
        abort_if($finishedProduct->isManufactured(), 403,
            'Only resale products can be restocked manually.');

        return view('finished-products.restock', compact('finishedProduct'));
    }

    public function restockStore(Request $request, FinishedProduct $finishedProduct)
    {
        abort_if($finishedProduct->isManufactured(), 403);

        $request->validate([
            'quantity' => 'required|numeric|min:0.0001',
            'unit_cost' => 'required|numeric|min:0',
            'restock_date' => 'required|date',
            'supplier' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $finishedProduct) {
            $qty = (float) $request->quantity;
            $unitCost = (float) $request->unit_cost;
            $totalCost = $qty * $unitCost;

            // Weighted average cost
            $existingStock = (float) $finishedProduct->current_stock;
            $existingCost = (float) $finishedProduct->average_cost;
            $newAvgCost = ($existingStock + $qty) > 0
                ? (($existingStock * $existingCost) + ($qty * $unitCost)) / ($existingStock + $qty)
                : $unitCost;

            ProductRestock::create([
                'finished_product_id' => $finishedProduct->id,
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'restock_date' => $request->restock_date,
                'supplier' => $request->supplier,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            $finishedProduct->increment('current_stock', $qty);
            $finishedProduct->update(['average_cost' => $newAvgCost]);
        });

        return redirect()->route('finished-products.show', $finishedProduct)
            ->with('success', 'Stock restocked and average cost updated.');
    }

    // ── Warehouse stock adjustment (physical count / corrections) ──

    public function adjustForm(FinishedProduct $finishedProduct)
    {
        return view('finished-products.adjust', compact('finishedProduct'));
    }

    public function adjustStore(Request $request, FinishedProduct $finishedProduct)
    {
        $request->validate([
            'new_quantity' => 'required|numeric|min:0',
            'reason' => 'required|string|in:physical_count,damage,shrinkage,found,data_entry,other',
            'notes' => 'nullable|string|max:1000',
        ]);

        $newQty = max(0.0, (float) $request->new_quantity);

        $noOp = false;

        DB::transaction(function () use ($request, $finishedProduct, $newQty, &$noOp) {
            $product = FinishedProduct::lockForUpdate()->findOrFail($finishedProduct->id);
            $before = (float) $product->current_stock;
            $diff = $newQty - $before;

            if (abs($diff) < 0.0000001) {
                $noOp = true;

                return;
            }

            FinishedProductStockAdjustment::create([
                'finished_product_id' => $product->id,
                'quantity_before' => $before,
                'quantity_after' => $newQty,
                'difference' => $diff,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            $product->update(['current_stock' => $newQty]);
        });

        if ($noOp) {
            return redirect()->route('finished-products.show', $finishedProduct)
                ->with('success', 'No change — quantity already matches current warehouse stock.');
        }

        return redirect()->route('finished-products.show', $finishedProduct)
            ->with('success', 'Warehouse stock adjusted. Average cost was not changed.');
    }
}
