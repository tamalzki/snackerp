<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RawMaterialController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $materials = RawMaterial::when($search, function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('unit', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%");
        })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('raw-materials.index', compact('materials', 'search'));
    }

    public function create()
    {
        return view('raw-materials.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:raw_materials,name',
            'category' => 'required|in:ingredients,packaging',
            'unit' => 'required|in:kg,grams,liters,pcs',
            'cost_per_unit' => 'required|numeric|min:0',
            'low_stock_threshold' => 'nullable|numeric|min:0',
        ]);

        RawMaterial::create([
            'name' => $request->name,
            'category' => $request->category,
            'unit' => $request->unit,
            'cost_per_unit' => $request->cost_per_unit,
            'low_stock_threshold' => $request->low_stock_threshold ?? 0,
        ]);

        return redirect()->route('raw-materials.index')
            ->with('success', 'Raw material added successfully.');
    }

    public function edit(RawMaterial $rawMaterial)
    {
        return view('raw-materials.edit', compact('rawMaterial'));
    }

    public function update(Request $request, RawMaterial $rawMaterial)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:raw_materials,name,'.$rawMaterial->id,
            'category' => 'required|in:ingredients,packaging',
            'unit' => 'required|in:kg,grams,liters,pcs',
            'cost_per_unit' => 'required|numeric|min:0',
            'low_stock_threshold' => 'nullable|numeric|min:0',
        ]);

        $rawMaterial->update([
            'name' => $request->name,
            'category' => $request->category,
            'unit' => $request->unit,
            'cost_per_unit' => $request->cost_per_unit,
            'low_stock_threshold' => $request->low_stock_threshold ?? 0,
        ]);

        return redirect()->route('raw-materials.index')
            ->with('success', 'Raw material updated successfully.');
    }

    public function destroy(RawMaterial $rawMaterial)
    {
        if ((float) $rawMaterial->stock_quantity > 0) {
            return redirect()->route('raw-materials.index')
                ->with('error', 'Cannot delete a raw material that still has stock on hand.');
        }

        if (DB::table('production_batch_items')->where('raw_material_id', $rawMaterial->id)->exists()) {
            return redirect()->route('raw-materials.index')
                ->with('error', 'Cannot delete: this material appears on production batches.');
        }

        if (DB::table('raw_material_purchase_items')->where('raw_material_id', $rawMaterial->id)->exists()) {
            return redirect()->route('raw-materials.index')
                ->with('error', 'Cannot delete: this material appears on purchase records.');
        }

        $rawMaterial->delete();

        return redirect()->route('raw-materials.index')
            ->with('success', 'Raw material deleted.');
    }

    public function show(RawMaterial $rawMaterial)
    {
        return view('raw-materials.show', compact('rawMaterial'));
    }
}
