<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\FinishedProduct;
use App\Models\StockTransfer;
use App\Services\TransferService;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function __construct(private TransferService $service) {}

    public function index(Request $request)
    {
        $search = $request->get('search');

        $transfers = StockTransfer::with(['branch', 'sourceBranch', 'creator', 'items'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('branch', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('dr_number', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('transfers.index', compact('transfers', 'search'));
    }

    public function create(Request $request)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        $warehouseProducts = FinishedProduct::where('current_stock', '>', 0)
            ->orderBy('name')->get();

        $selectedBranch = null;
        if ($request->has('branch_id')) {
            $selectedBranch = Branch::find($request->branch_id);
        }

        return view('transfers.create', compact(
            'branches', 'warehouseProducts', 'selectedBranch'
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
            $rawQty = $row['quantity'] ?? null;

            if ($rawQty === null || $rawQty === '' || (is_string($rawQty) && trim($rawQty) === '')) {
                $qty = 0.0;
            } elseif (is_numeric($rawQty)) {
                $qty = max(0.0, (float) $rawQty);
            } else {
                return back()
                    ->withErrors(['items' => 'Each quantity must be a number (use 0 or leave blank to skip a product).'])
                    ->withInput();
            }

            $normalizedItems[] = [
                'finished_product_id' => $pid,
                'quantity' => $qty,
            ];
        }

        if (count($normalizedItems) < 1) {
            return back()
                ->withErrors(['items' => 'No product lines were submitted.'])
                ->withInput();
        }

        $request->merge(['items' => $normalizedItems]);

        $request->validate([
            'dr_number' => 'nullable|string|max:50',
            'branch_id' => 'required|exists:branches,id',
            'transfer_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.finished_product_id' => 'required|integer|exists:finished_products,id',
            'items.*.quantity' => 'required|numeric|min:0',
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
            $transfer = $this->service->store($payload, auth()->id());

            return redirect()->route('transfers.show', $transfer)
                ->with('success', 'Stock delivered successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Error: '.$e->getMessage())->withInput();
        }
    }

    public function show(StockTransfer $transfer)
    {
        $transfer->load(['items.finishedProduct', 'branch', 'sourceBranch', 'creator']);

        return view('transfers.show', compact('transfer'));
    }
}
