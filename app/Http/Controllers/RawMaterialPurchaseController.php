<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\RawMaterialPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RawMaterialPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $purchases = RawMaterialPurchase::with('creator')
            ->when($search, function ($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('purchases.index', compact('purchases', 'search'));
    }

    public function create(Request $request)
    {
        $materials = RawMaterial::orderBy('category')->orderBy('name')->get();

        $selectedMaterial = null;
        if ($request->has('material_id')) {
            $selectedMaterial = RawMaterial::find($request->material_id);
        }

        return view('purchases.create', compact('materials', 'selectedMaterial'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_name' => 'required|string|max:150',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.cost_per_unit' => 'required|numeric|min:0.0001',
        ]);

        DB::transaction(function () use ($request) {

            $totalCost = 0;

            // Calculate grand total first
            foreach ($request->items as $item) {
                $totalCost += $item['quantity'] * $item['cost_per_unit'];
            }

            // Create the purchase header
            $purchase = RawMaterialPurchase::create([
                'supplier_name' => $request->supplier_name,
                'purchase_date' => $request->purchase_date,
                'total_cost' => $totalCost,
                'created_by' => auth()->id(),
            ]);

            // Create items and update stock (weighted average cost per unit)
            foreach ($request->items as $item) {
                $lineCost = $item['quantity'] * $item['cost_per_unit'];

                // Save the purchase line item
                $purchase->items()->create([
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity' => $item['quantity'],
                    'cost_per_unit' => $item['cost_per_unit'],
                    'total_cost' => $lineCost,
                ]);

                $material = RawMaterial::lockForUpdate()->findOrFail($item['raw_material_id']);
                $addQty = (float) $item['quantity'];
                $purchaseUnitCost = (float) $item['cost_per_unit'];
                $oldQty = (float) $material->stock_quantity;
                $oldCost = (float) $material->cost_per_unit;
                $newQty = $oldQty + $addQty;
                $weightedCost = $newQty > 0
                    ? (($oldQty * $oldCost) + ($addQty * $purchaseUnitCost)) / $newQty
                    : $purchaseUnitCost;

                $material->update([
                    'stock_quantity' => $newQty,
                    'cost_per_unit' => round($weightedCost, 4),
                ]);
            }
        });

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase recorded and stock updated successfully.');
    }

    public function show(RawMaterialPurchase $purchase)
    {
        $purchase->load('items.rawMaterial', 'creator');

        return view('purchases.show', compact('purchase'));
    }
}
