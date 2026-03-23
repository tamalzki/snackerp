<?php
namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private SaleService $service) {}

    public function index(Request $request)
    {
        $search = $request->get('search');

        $sales = Sale::with(['branch', 'creator', 'items'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('branch', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('sales.index', compact('sales', 'search'));
    }

    public function create(Request $request)
{
    $branches = Branch::where('is_active', true)->orderBy('name')->get();

    $selectedBranch  = null;
    $branchInventory = collect();
    $branchProducts  = collect();

    if ($request->has('branch_id')) {
        $selectedBranch = Branch::find($request->branch_id);

        if ($selectedBranch) {
            $branchInventory = BranchInventory::with('finishedProduct')
                ->where('branch_id', $selectedBranch->id)
                ->where('stock_quantity', '>', 0)
                ->get();

            $branchProducts = $branchInventory->map(fn($i) => [
                'id'            => $i->finished_product_id,
                'name'          => $i->finishedProduct->name,
                'stock'         => (float) $i->stock_quantity,
                'cost_snapshot' => (float) $i->cost_snapshot,
                'selling_price' => (float) $i->finishedProduct->selling_price,
            ])->values();
        }
    }

    return view('sales.create', compact(
        'branches', 'selectedBranch', 'branchInventory', 'branchProducts'
    ));
}

    public function store(Request $request)
    {
        $itemsIn = $request->input('items', []);
        if (! is_array($itemsIn)) {
            $itemsIn = [];
        }

        $normalizedItems = [];
        foreach ($itemsIn as $row) {
            if (! is_array($row)) {
                continue;
            }

            $pid = (int) ($row['finished_product_id'] ?? 0);
            if ($pid < 1) {
                continue;
            }

            $rawQty = $row['quantity'] ?? null;
            if ($rawQty === null || $rawQty === '' || (is_string($rawQty) && trim($rawQty) === '')) {
                $qty = 0.0;
            } elseif (is_numeric($rawQty)) {
                $qty = max(0.0, (float) $rawQty);
            } else {
                return back()
                    ->withErrors(['items' => 'Each quantity must be a number (use 0 to skip a line).'])
                    ->withInput();
            }

            $rawPrice = $row['unit_price'] ?? null;
            if ($qty > 0) {
                if ($rawPrice === null || $rawPrice === '' || ! is_numeric($rawPrice)) {
                    return back()
                        ->withErrors(['items' => 'Enter a valid unit price for every product with quantity greater than zero.'])
                        ->withInput();
                }
                $unitPrice = max(0.0, (float) $rawPrice);
            } else {
                $unitPrice = 0.0;
            }

            $normalizedItems[] = [
                'finished_product_id' => $pid,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
            ];
        }

        if (count($normalizedItems) < 1) {
            return back()
                ->withErrors(['items' => 'Select at least one product on a line (blank lines are ignored).'])
                ->withInput();
        }

        $request->merge(['items' => $normalizedItems]);

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'sale_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.finished_product_id' => 'required|integer|exists:finished_products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $picked = collect($request->items)
            ->filter(fn ($row) => (float) $row['quantity'] > 0)
            ->values()
            ->all();

        if (count($picked) < 1) {
            return back()
                ->withErrors(['items' => 'Enter a quantity greater than zero for at least one product.'])
                ->withInput();
        }

        $payload = array_merge($request->except('items'), ['items' => $picked]);

        try {
            $sale = $this->service->store($payload, auth()->id());

            return redirect()->route('sales.show', $sale)
                ->with('success', 'Sale recorded successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.finishedProduct', 'branch', 'creator']);
        return view('sales.show', compact('sale'));
    }
}