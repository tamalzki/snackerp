<?php
namespace App\Http\Controllers;

use App\Models\FinishedProduct;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use App\Services\ProductionService;
use Illuminate\Http\Request;

class ProductionBatchController extends Controller
{
    public function __construct(private ProductionService $service) {}

    public function index(Request $request)
{
    $search = $request->get('search');

    $batches = ProductionBatch::with(['finishedProduct', 'creator'])
        ->when($search, function ($q) use ($search) {
            $q->whereHas('finishedProduct', function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%");
            })->orWhereHas('creator', function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%");
            });
        })
        ->latest()
        ->paginate(15)
        ->withQueryString();

    return view('production.index', compact('batches', 'search'));
}

public function create(Request $request)
{
    $products = FinishedProduct::orderBy('name')->get();
    $allMaterials = RawMaterial::orderBy('category')
        ->orderBy('name')
        ->get();

    $selectedProduct = null;
    $lastBatchItems  = collect();

    if ($request->has('product_id')) {
        $selectedProduct = FinishedProduct::find($request->product_id);

        if ($selectedProduct) {
            // Get last production batch for this product
            $lastBatch = \App\Models\ProductionBatch::where('finished_product_id', $selectedProduct->id)
                ->with('items.rawMaterial')
                ->latest()
                ->first();

            if ($lastBatch) {
                $lastBatchItems = $lastBatch->items->map(fn($item) => [
                    'raw_material_id' => $item->raw_material_id,
                    'quantity_used'   => $item->quantity_used,
                    'name'            => $item->rawMaterial->name,
                    'unit'            => $item->rawMaterial->unit,
                    'category'        => $item->rawMaterial->category,
                    'stock_quantity'  => $item->rawMaterial->stock_quantity,
                    'cost_per_unit'   => $item->rawMaterial->cost_per_unit,
                ]);
            }
        }
    }

    return view('production.create', compact(
        'products', 'allMaterials', 'selectedProduct', 'lastBatchItems'
    ));
}

    public function store(Request $request)
{
    $request->validate([
        'finished_product_id'     => 'required|exists:finished_products,id',
        'production_date'         => 'required|date',
        'expected_output_qty'     => 'required|numeric|min:0.0001',
        'actual_output_qty'       => 'required|numeric|min:0.0001',
        'reject_qty'              => 'nullable|numeric|min:0',
        'items'                   => 'required|array|min:1',
        'items.*.raw_material_id' => 'required|exists:raw_materials,id',
        'items.*.quantity_used'   => 'required|numeric|min:0.0001',
    ]);

    try {
        $batch = $this->service->store($request->all(), auth()->id());

        return redirect()->route('production.show', $batch)
            ->with('success', 'Production batch recorded successfully.');

    } catch (\Illuminate\Validation\ValidationException $e) {
        return back()->withErrors($e->errors())->withInput();

    } catch (\Exception $e) {
        return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
    }
}

    public function show(ProductionBatch $production)
    {
        $production->load(['items.rawMaterial', 'finishedProduct', 'creator']);
        return view('production.show', compact('production'));
    }

    public function index_summary()
    {
        return view('production.index');
    }

    public function lastRecipe(FinishedProduct $product)
{
    $lastBatch = ProductionBatch::where('finished_product_id', $product->id)
        ->with('items.rawMaterial')
        ->latest()
        ->first();

    if (!$lastBatch) {
        return response()->json([]);
    }

    return response()->json(
        $lastBatch->items->map(fn($item) => [
            'raw_material_id' => $item->raw_material_id,
            'quantity_used'   => $item->quantity_used,
            'name'            => $item->rawMaterial->name,
            'unit'            => $item->rawMaterial->unit,
            'category'        => $item->rawMaterial->category,
            'stock_quantity'  => $item->rawMaterial->stock_quantity,
            'cost_per_unit'   => $item->rawMaterial->cost_per_unit,
        ])
    );
}
}