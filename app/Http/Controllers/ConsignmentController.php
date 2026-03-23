<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\ConsignmentPayment;
use App\Models\ConsignmentReceivable;
use App\Models\ConsignmentSale;
use App\Models\ConsignmentSaleItem;
use App\Models\FinishedProduct;
use App\Services\TransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConsignmentController extends Controller
{
    public function __construct(private TransferService $transferService) {}

    // ── All Branches Overview ─────────────────────────────

    public function index()
    {
        $user = auth()->user();
        if ($user->isBranchUser()) {
            if (! $user->branch_id) {
                return redirect()->route('dashboard')
                    ->with('error', 'Your account is not assigned to a branch. Ask an admin to set your branch.');
            }

            return redirect()->route('consignment.branch', $user->branch_id);
        }

        $branches = Branch::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($branch) {
                $summary = ConsignmentReceivable::where('branch_id', $branch->id)
                    ->selectRaw('
                        COUNT(*) as total_drs,
                        SUM(total_amount)    as total_receivable,
                        SUM(amount_paid)     as total_paid,
                        SUM(amount_returned) as total_returned,
                        SUM(balance)         as total_balance
                    ')
                    ->first();
                $branch->summary = $summary;

                return $branch;
            });

        return view('consignment.index', compact('branches'));
    }

    // ── Branch Ledger ─────────────────────────────────────

    public function branch(Branch $branch)
    {
        $this->assertBranchUserOwns($branch->id);

        $receivables = ConsignmentReceivable::with(['transfer', 'sales', 'payments'])
            ->where('branch_id', $branch->id)
            ->latest('delivery_date')
            ->paginate(15);

        $summary = ConsignmentReceivable::where('branch_id', $branch->id)
            ->selectRaw('
                SUM(total_amount)    as total_receivable,
                SUM(amount_paid)     as total_paid,
                SUM(amount_returned) as total_returned,
                SUM(balance)         as total_balance,
                COUNT(*) as total_drs,
                SUM(CASE WHEN status = "open"    THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = "partial" THEN 1 ELSE 0 END) as partial_count,
                SUM(CASE WHEN status = "paid"    THEN 1 ELSE 0 END) as paid_count
            ')
            ->first();

        return view('consignment.branch', compact('branch', 'receivables', 'summary'));
    }

    // ── Branch → Branch (new DR at destination) ─────────────

    public function createBranchTransfer(Branch $branch)
    {
        $this->assertBranchUserOwns($branch->id);

        $inventory = BranchInventory::where('branch_id', $branch->id)
            ->where('stock_quantity', '>', 0)
            ->with('finishedProduct')
            ->orderBy('finished_product_id')
            ->get();

        $destinationBranches = Branch::where('is_active', true)
            ->where('id', '!=', $branch->id)
            ->orderBy('name')
            ->get();

        return view('consignment.branch-transfer', compact('branch', 'inventory', 'destinationBranches'));
    }

    public function storeBranchTransfer(Request $request, Branch $branch)
    {
        $this->assertBranchUserOwns($branch->id);

        // Normalize lines first: blank / empty qty => 0 (excluded later). Avoids validation failing on "".
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
                    ->withErrors(['items' => 'Each quantity must be a number (leave blank or enter 0 to skip a product).'])
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
            'destination_branch_id' => ['required', 'exists:branches,id', Rule::notIn([$branch->id])],
            'transfer_date' => 'required|date',
            'reason' => 'required|string|max:500',
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

        $payload = [
            'source_branch_id' => $branch->id,
            'destination_branch_id' => (int) $request->destination_branch_id,
            'dr_number' => $request->dr_number,
            'transfer_date' => $request->transfer_date,
            'reason' => $request->reason,
            'items' => $picked,
        ];

        try {
            $transfer = $this->transferService->storeBranchToBranch($payload, auth()->id());
            $receivable = ConsignmentReceivable::where('stock_transfer_id', $transfer->id)->firstOrFail();

            return redirect()->route('consignment.show', $receivable)
                ->with('success', 'Products transferred. A new DR was created for the destination branch.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Error: '.$e->getMessage())->withInput();
        }
    }

    // ── DR Detail ─────────────────────────────────────────

    public function show(ConsignmentReceivable $receivable)
    {
        $this->assertReceivableAccessible($receivable);

        $receivable->load([
            'branch',
            'transfer.items.finishedProduct',
            'sales.items.finishedProduct',
            'payments.creator',
            'payments.sale',
            'creator',
        ]);

        $productIds = $receivable->transfer->items->pluck('finished_product_id');

        $branchInventory = BranchInventory::where('branch_id', $receivable->branch_id)
            ->whereIn('finished_product_id', $productIds)
            ->with('finishedProduct')
            ->get()
            ->keyBy('finished_product_id');

        return view('consignment.show', compact('receivable', 'branchInventory'));
    }

    // ── Record Sales ──────────────────────────────────────

    public function createSale(ConsignmentReceivable $receivable)
    {
        $this->assertReceivableAccessible($receivable);

        $receivable->load(['branch', 'transfer.items.finishedProduct']);

        $branchInventory = BranchInventory::where('branch_id', $receivable->branch_id)
            ->whereIn('finished_product_id',
                $receivable->transfer->items->pluck('finished_product_id'))
            ->with('finishedProduct')
            ->get()
            ->keyBy('finished_product_id');

        $deliveredProducts = $receivable->transfer->items
            ->map(function ($item) use ($branchInventory) {
                $inv = $branchInventory[$item->finished_product_id] ?? null;

                return [
                    'id' => $item->finished_product_id,
                    'name' => $item->finishedProduct->name,
                    'selling_price' => (float) $item->finishedProduct->selling_price,
                    'branch_stock' => $inv ? (float) $inv->stock_quantity : 0,
                ];
            })
            ->values();

        return view('consignment.create-sale',
            compact('receivable', 'branchInventory', 'deliveredProducts'));
    }

    public function storeSale(Request $request, ConsignmentReceivable $receivable)
    {
        $this->assertReceivableAccessible($receivable);

        $receivable->loadMissing('transfer.items');

        $allowedProductIds = $receivable->transfer->items
            ->pluck('finished_product_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $itemsIn = $request->input('items', []);
        if (! is_array($itemsIn)) {
            $itemsIn = [];
        }

        $normalizedByProduct = [];
        foreach ($itemsIn as $row) {
            if (! is_array($row)) {
                continue;
            }

            $pid = (int) ($row['finished_product_id'] ?? 0);
            if ($pid < 1 || ! in_array($pid, $allowedProductIds, true)) {
                continue;
            }

            $rawQty = $row['qty_sold'] ?? null;
            if ($rawQty === null || $rawQty === '' || (is_string($rawQty) && trim($rawQty) === '')) {
                $qty = 0.0;
            } elseif (is_numeric($rawQty)) {
                $qty = max(0.0, (float) $rawQty);
            } else {
                return back()
                    ->withErrors(['items' => 'Each quantity must be a number (use 0 or leave blank to skip a line).'])
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
                $unitPrice = ($rawPrice !== null && $rawPrice !== '' && is_numeric($rawPrice))
                    ? max(0.0, (float) $rawPrice)
                    : 0.0;
            }

            $normalizedByProduct[$pid] = [
                'finished_product_id' => $pid,
                'qty_sold' => $qty,
                'unit_price' => $unitPrice,
            ];
        }

        $normalizedItems = array_values($normalizedByProduct);

        if (count($normalizedItems) < 1) {
            return back()
                ->withErrors(['items' => 'No valid product lines were submitted.'])
                ->withInput();
        }

        $request->merge(['items' => $normalizedItems]);

        $request->validate([
            'sale_date_from' => 'required|date',
            'sale_date_to' => 'required|date|after_or_equal:sale_date_from',
            'notes' => 'nullable|string|max:500',
            'remitted_amount' => 'nullable|numeric|min:0',
            'remittance_reference' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.finished_product_id' => 'required|exists:finished_products,id',
            'items.*.qty_sold' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $picked = collect($request->items)
            ->filter(fn ($row) => (float) $row['qty_sold'] > 0)
            ->values()
            ->all();

        if (count($picked) < 1) {
            return back()
                ->withErrors(['items' => 'Enter a quantity greater than zero for at least one product.'])
                ->withInput();
        }

        $remittedAmount = 0.0;
        $rawRemit = $request->input('remitted_amount');
        if ($rawRemit !== null && $rawRemit !== '' && is_numeric($rawRemit)) {
            $remittedAmount = max(0.0, (float) $rawRemit);
        }

        DB::transaction(function () use ($picked, $request, $receivable, $remittedAmount) {
            $totalAmount = 0;
            $totalCost = 0;

            $sale = ConsignmentSale::create([
                'consignment_receivable_id' => $receivable->id,
                'branch_id' => $receivable->branch_id,
                'sale_date_from' => $request->sale_date_from,
                'sale_date_to' => $request->sale_date_to,
                'total_amount' => 0,
                'total_cost' => 0,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($picked as $item) {
                $product = FinishedProduct::findOrFail($item['finished_product_id']);
                $inv = BranchInventory::where('branch_id', $receivable->branch_id)
                    ->where('finished_product_id', $product->id)
                    ->first();
                $costSnapshot = $inv ? (float) $inv->cost_snapshot : (float) $product->average_cost;
                $lineTotal = $item['qty_sold'] * $item['unit_price'];
                $lineCost = $item['qty_sold'] * $costSnapshot;

                ConsignmentSaleItem::create([
                    'consignment_sale_id' => $sale->id,
                    'finished_product_id' => $product->id,
                    'qty_sold' => $item['qty_sold'],
                    'unit_price' => $item['unit_price'],
                    'cost_snapshot' => $costSnapshot,
                    'total_price' => $lineTotal,
                ]);

                if ($inv) {
                    $inv->decrement('stock_quantity', $item['qty_sold']);
                }

                $totalAmount += $lineTotal;
                $totalCost += $lineCost;
            }

            $sale->update([
                'total_amount' => $totalAmount,
                'total_cost' => $totalCost,
            ]);

            if ($remittedAmount > 0) {
                $remitNotes = 'Cash remittance with this sale entry';
                if ($request->filled('notes')) {
                    $remitNotes .= ' — '.$request->notes;
                }

                ConsignmentPayment::create([
                    'consignment_receivable_id' => $receivable->id,
                    'consignment_sale_id' => $sale->id,
                    'branch_id' => $receivable->branch_id,
                    'amount' => $remittedAmount,
                    'payment_date' => $request->sale_date_to,
                    'reference' => $request->input('remittance_reference'),
                    'notes' => $remitNotes,
                    'created_by' => auth()->id(),
                ]);
            }

            $receivable->refresh();
            $receivable->recalculate();
        });

        $lineCount = count($picked);
        $unitsTotal = collect($picked)->sum(fn ($row) => (float) $row['qty_sold']);

        $success = sprintf(
            'Sales recorded: %d product line(s), %s total qty sold.',
            $lineCount,
            qty_fmt($unitsTotal)
        );

        if ($remittedAmount > 0) {
            $success .= ' Cash remittance of ₱'.number_format($remittedAmount, 2).' recorded with this sale.';
        }

        return redirect()->route('consignment.show', $receivable)
            ->with('success', $success);
    }

    // ── Pull Out (BO / Expired) ───────────────────────────

    public function createPullOut(ConsignmentReceivable $receivable)
    {
        $this->assertReceivableAccessible($receivable);

        $receivable->load(['branch', 'transfer.items.finishedProduct']);

        $branchInventory = BranchInventory::where('branch_id', $receivable->branch_id)
            ->whereIn('finished_product_id',
                $receivable->transfer->items->pluck('finished_product_id'))
            ->with('finishedProduct')
            ->get()
            ->keyBy('finished_product_id');

        $pulloutProducts = $receivable->transfer->items
            ->map(function ($item) use ($branchInventory) {
                $inv = $branchInventory[$item->finished_product_id] ?? null;

                return [
                    'id' => $item->finished_product_id,
                    'name' => $item->finishedProduct->name,
                    'selling_price' => (float) $item->finishedProduct->selling_price,
                    'branch_stock' => $inv ? (float) $inv->stock_quantity : 0,
                ];
            })
            ->values();

        return view('consignment.pullout',
            compact('receivable', 'branchInventory', 'pulloutProducts'));
    }

    public function storePullOut(Request $request, ConsignmentReceivable $receivable)
    {
        $this->assertReceivableAccessible($receivable);

        $request->validate([
            'pullout_date' => 'required|date',
            'reason' => 'required|in:expired,bo,other',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.finished_product_id' => 'required|exists:finished_products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        try {
            $this->transferService->storePullOut([
                'branch_id' => $receivable->branch_id,
                'transfer_date' => $request->pullout_date,
                'notes' => '['.strtoupper($request->reason).'] '.$request->notes,
                'consignment_receivable_id' => $receivable->id,
                'items' => $request->items,
            ], auth()->id());

            return redirect()->route('consignment.show', $receivable)
                ->with('success', 'Pull out recorded and receivable balance updated.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error: '.$e->getMessage())->withInput();
        }
    }

    // ── Record Payment ─────────────────────────────────────

    public function storePayment(Request $request, ConsignmentReceivable $receivable)
    {
        $this->assertReceivableAccessible($receivable);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $receivable) {
            ConsignmentPayment::create([
                'consignment_receivable_id' => $receivable->id,
                'branch_id' => $receivable->branch_id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'reference' => $request->reference,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            $receivable->recalculate();
        });

        return redirect()->route('consignment.show', $receivable)
            ->with('success', 'Payment recorded and balance updated.');
    }

    private function assertBranchUserOwns(int $branchId): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return;
        }
        if (! $user->branch_id || (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'You can only access your assigned branch.');
        }
    }

    private function assertReceivableAccessible(ConsignmentReceivable $receivable): void
    {
        $this->assertBranchUserOwns($receivable->branch_id);
    }
}
