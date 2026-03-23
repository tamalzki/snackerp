<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\FinishedProduct;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function production(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $product = $request->get('product_id');

        // Manufactured batches
        $batches = ProductionBatch::with(['finishedProduct', 'creator'])
            ->whereBetween('production_date', [$from, $to])
            ->when($product, fn ($q) => $q->where('finished_product_id', $product))
            ->latest('production_date')
            ->get();

        // Resale restocks
        $restocks = \App\Models\ProductRestock::with(['finishedProduct', 'creator'])
            ->whereBetween('restock_date', [$from, $to])
            ->when($product, fn ($q) => $q->where('finished_product_id', $product))
            ->latest('restock_date')
            ->get();

        $products = FinishedProduct::orderBy('name')->get();
        $totalOutput = $batches->sum('actual_output_qty');
        $totalRejects = $batches->sum('reject_qty');
        $totalCost = $batches->sum('total_raw_material_cost');

        $totalRestockQty = $restocks->sum('quantity');
        $totalRestockCost = $restocks->sum('total_cost');

        // Expiring soon (within 30 days)
        $expiringSoon = ProductionBatch::with('finishedProduct')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now())
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->orderBy('expiry_date')
            ->get();

        // Expired
        $expired = ProductionBatch::with('finishedProduct')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now())
            ->orderBy('expiry_date', 'desc')
            ->get();

        return view('reports.production', compact(
            'batches', 'restocks', 'products', 'from', 'to',
            'totalOutput', 'totalRejects', 'totalCost',
            'totalRestockQty', 'totalRestockCost',
            'expiringSoon', 'expired'
        ));
    }

    public function sales(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $branch = $request->get('branch_id');

        $sales = \App\Models\ConsignmentSale::with([
            'branch',
            'items.finishedProduct',
            'receivable',
        ])
            ->overlappingPeriod($from, $to)
            ->when($branch, fn ($q) => $q->where('branch_id', $branch))
            ->orderByDesc('sale_date_to')
            ->orderByDesc('sale_date_from')
            ->get();

        $branches = Branch::orderBy('name')->get();
        $totalSales = $sales->sum('total_amount');
        $totalCost = $sales->sum('total_cost');
        $grossProfit = $totalSales - $totalCost;
        $margin = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;

        // Sales by branch
        $byBranch = $sales->groupBy('branch_id')->map(function ($group) {
            return [
                'name' => $group->first()->branch->name,
                'total_sales' => $group->sum('total_amount'),
                'total_cost' => $group->sum('total_cost'),
                'profit' => $group->sum('total_amount') - $group->sum('total_cost'),
                'count' => $group->count(),
            ];
        })->sortByDesc('total_sales')->values();

        // Top products
        $topProducts = $sales->flatMap(fn ($s) => $s->items)
            ->groupBy('finished_product_id')
            ->map(function ($items) {
                return [
                    'name' => $items->first()->finishedProduct->name,
                    'qty' => $items->sum('qty_sold'),
                    'revenue' => $items->sum('total_price'),
                ];
            })
            ->sortByDesc('revenue')
            ->take(10)
            ->values();

        return view('reports.sales', compact(
            'sales', 'branches', 'from', 'to',
            'totalSales', 'totalCost', 'grossProfit', 'margin',
            'byBranch', 'topProducts'
        ));
    }

    public function inventory(Request $request)
    {
        // Warehouse stock — finished products
        $warehouseStock = FinishedProduct::orderBy('name')->get();
        $warehouseValue = $warehouseStock->sum(fn ($p) => $p->current_stock * $p->average_cost);

        // Branch inventory — grouped by branch
        $branchStock = \App\Models\BranchInventory::with(['branch', 'finishedProduct'])
            ->get()
            ->groupBy('branch_id');

        $branches = Branch::orderBy('name')->get();

        // Branch total value per branch
        $branchValues = $branchStock->map(fn ($items) => $items->sum(fn ($i) => $i->stock_quantity * $i->cost_snapshot)
        );

        $totalBranchValue = $branchValues->sum();

        // Raw materials
        $rawMaterials = RawMaterial::orderBy('category')->orderBy('name')->get();
        $rawMaterialValue = $rawMaterials->sum(fn ($m) => $m->stock_quantity * $m->cost_per_unit);
        $lowStockItems = $rawMaterials->filter(fn ($m) => $m->isLowStock());

        // Low stock finished products
        $lowStockProducts = $warehouseStock->filter(fn ($p) => $p->isLowStock());

        // Consignment summary per branch — outstanding stock at branches
        $consignmentSummary = \App\Models\ConsignmentReceivable::with('branch')
            ->whereIn('status', ['open', 'partial'])
            ->get()
            ->groupBy('branch_id')
            ->map(function ($items) {
                return [
                    'name' => $items->first()->branch->name,
                    'balance' => $items->sum('balance'),
                    'drs' => $items->count(),
                ];
            })
            ->values();

        return view('reports.inventory', compact(
            'warehouseStock', 'warehouseValue',
            'branchStock', 'branches', 'branchValues', 'totalBranchValue',
            'rawMaterials', 'rawMaterialValue', 'lowStockItems',
            'lowStockProducts', 'consignmentSummary'
        ));
    }

    public function profitLoss(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));

        // Revenue from consignment sales
        $sales = \App\Models\ConsignmentSale::overlappingPeriod($from, $to)->get();
        $totalRevenue = $sales->sum('total_amount');
        $totalCOGS = $sales->sum('total_cost');
        $grossProfit = $totalRevenue - $totalCOGS;

        // Payments received (cash actually collected)
        $paymentsReceived = \App\Models\ConsignmentPayment::whereBetween('payment_date', [$from, $to])
            ->sum('amount');

        // Pull outs (returned value)
        $pullouts = \App\Models\ConsignmentReceivable::whereBetween('delivery_date', [$from, $to])
            ->sum('amount_returned');

        // Outstanding receivables (sold but not yet paid)
        $outstanding = \App\Models\ConsignmentReceivable::whereIn('status', ['open', 'partial'])
            ->sum('balance');

        // Expenses
        $expenses = Expense::whereBetween('expense_date', [$from, $to])->get();
        $totalExpenses = $expenses->sum('amount');

        $expensesByCategory = $expenses->groupBy('category')
            ->map(function ($group) {
                return [
                    'category' => ucfirst($group->first()->category),
                    'total' => $group->sum('amount'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $netProfit = $grossProfit - $totalExpenses;

        // Sales by month for trend
        // Bucket by end of sale period (sale_date_to) so weekly ranges land in the closing month.
        $monthlySales = \App\Models\ConsignmentSale::selectRaw(
            'YEAR(sale_date_to) as year,
             MONTH(sale_date_to) as month,
             SUM(total_amount) as revenue,
             SUM(total_cost) as cost'
        )
            ->overlappingPeriod($from, $to)
            ->groupByRaw('YEAR(sale_date_to), MONTH(sale_date_to)')
            ->orderByRaw('YEAR(sale_date_to), MONTH(sale_date_to)')
            ->get()
            ->map(function ($row) {
                return [
                    'label' => \Carbon\Carbon::createFromDate($row->year, $row->month, 1)
                        ->format('M Y'),
                    'revenue' => (float) $row->revenue,
                    'cost' => (float) $row->cost,
                    'profit' => (float) $row->revenue - (float) $row->cost,
                ];
            });

        return view('reports.profit-loss', compact(
            'from', 'to',
            'totalRevenue', 'totalCOGS', 'grossProfit',
            'paymentsReceived', 'pullouts', 'outstanding',
            'totalExpenses', 'expensesByCategory', 'netProfit',
            'monthlySales'
        ));
    }

    public function branchDelivery(Request $request)
    {
        $branches = Branch::orderBy('name')->get();
        $products = FinishedProduct::orderBy('name')->get();
        $branchId = $request->get('branch_id');
        $from = $request->get('from', now()->startOfWeek()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $productId = $request->get('product_id');

        if (! $branchId) {
            return view('reports.branch-delivery', compact('branches', 'products', 'from', 'to'))
                ->with(['rows' => collect(), 'selectedBranch' => null]);
        }

        $selectedBranch = Branch::findOrFail($branchId);

        // Get all receivables for this branch in the period
        $receivables = \App\Models\ConsignmentReceivable::with([
            'transfer.items.finishedProduct',
            'sales.items.finishedProduct',
            'payments',
        ])
            ->where('branch_id', $branchId)
            ->whereBetween('delivery_date', [$from, $to])
            ->get();

        // Build per-product rows
        $trackProducts = $products->when($productId, fn ($c) => $c->where('id', $productId));

        $rows = $trackProducts->map(function ($product) use ($receivables, $branchId) {

            // Qty delivered from all DRs in period
            $deliveredQty = 0;
            $deliveredAmt = 0;
            foreach ($receivables as $rec) {
                foreach ($rec->transfer->items as $item) {
                    if ($item->finished_product_id == $product->id) {
                        $deliveredQty += $item->quantity;
                        $deliveredAmt += $item->quantity * $product->selling_price;
                    }
                }
            }

            // Qty sold from consignment sales in period
            $soldQty = 0;
            $soldAmt = 0;
            foreach ($receivables as $rec) {
                foreach ($rec->sales as $sale) {
                    if (! $sale->overlapsPeriod($from, $to)) {
                        continue;
                    }
                    foreach ($sale->items as $item) {
                        if ($item->finished_product_id == $product->id) {
                            $soldQty += $item->qty_sold;
                            $soldAmt += $item->total_price;
                        }
                    }
                }
            }

            // Pull outs — from amount_returned on receivables
            // We approximate per product by checking branch inventory changes
            $pulloutQty = max(0, $deliveredQty - $soldQty -
                (\App\Models\BranchInventory::where('branch_id', $branchId)
                    ->where('finished_product_id', $product->id)
                    ->value('stock_quantity') ?? 0));
            $pulloutAmt = $pulloutQty * $product->selling_price;

            // Current branch stock
            $currentStock = (float) (\App\Models\BranchInventory::where('branch_id', $branchId)
                ->where('finished_product_id', $product->id)
                ->value('stock_quantity') ?? 0);

            $endingAmt = $currentStock * $product->selling_price;

            if ($deliveredQty == 0 && $currentStock == 0 && $soldQty == 0) {
                return null;
            }

            return [
                'product' => $product->name,
                'delivered_qty' => $deliveredQty,
                'delivered_amt' => $deliveredAmt,
                'sold_qty' => $soldQty,
                'sold_amt' => $soldAmt,
                'pullout_qty' => $pulloutQty,
                'pullout_amt' => $pulloutAmt,
                'ending_qty' => $currentStock,
                'ending_amt' => $endingAmt,
            ];
        })->filter()->values();

        // DR summary for the period
        $drSummary = $receivables->map(function ($rec) {
            return [
                'dr_number' => $rec->dr_number ?? '#'.$rec->id,
                'date' => $rec->delivery_date->format('M d, Y'),
                'total_amount' => $rec->total_amount,
                'amount_paid' => $rec->amount_paid,
                'returned' => $rec->amount_returned,
                'balance' => $rec->balance,
                'status' => $rec->status,
            ];
        });

        return view('reports.branch-delivery', compact(
            'branches', 'products', 'selectedBranch',
            'from', 'to', 'rows', 'productId', 'drSummary'
        ));
    }

    public function remittance(Request $request)
    {
        $branches = Branch::orderBy('name')->get();
        $branchId = $request->get('branch_id');
        $from = $request->get('from', now()->startOfWeek()->format('Y-m-d'));
        $to = $request->get('to', now()->endOfWeek()->format('Y-m-d'));

        if (! $branchId) {
            return view('reports.remittance', compact('branches', 'from', 'to'))
                ->with(['rows' => collect(), 'selectedBranch' => null, 'totals' => []]);
        }

        $selectedBranch = Branch::findOrFail($branchId);

        // All receivables for this branch in period
        $receivables = \App\Models\ConsignmentReceivable::with([
            'transfer.items.finishedProduct',
            'sales.items',
            'payments.sale',
        ])
            ->where('branch_id', $branchId)
            ->whereBetween('delivery_date', [$from, $to])
            ->latest('delivery_date')
            ->get();

        // Build per-DR rows
        $rows = $receivables->map(function ($rec) {
            $totalSales = $rec->sales->sum('total_amount');

            return [
                'dr_number' => $rec->dr_number ?? '#'.$rec->id,
                'delivery_date' => $rec->delivery_date->format('M d, Y'),
                'delivered_amt' => (float) $rec->total_amount,
                'sold_amt' => (float) $totalSales,
                'returned_amt' => (float) $rec->amount_returned,
                'paid_amt' => (float) $rec->amount_paid,
                'balance' => (float) $rec->balance,
                'status' => $rec->status,
                'receivable_id' => $rec->id,
                'payments' => $rec->payments->map(function ($p) {
                    return [
                        'date' => $p->payment_date->format('M d, Y'),
                        'amount' => (float) $p->amount,
                        'reference' => $p->reference,
                        'with_sale' => $p->consignment_sale_id !== null,
                        'sale_period' => $p->sale?->periodLabel(),
                    ];
                }),
            ];
        });

        // Overall branch totals (all time — not just period)
        $allTime = \App\Models\ConsignmentReceivable::where('branch_id', $branchId)->get();

        $paidWithSale = 0.0;
        $paidAdditional = 0.0;
        foreach ($rows as $r) {
            foreach ($r['payments'] as $p) {
                if (! empty($p['with_sale'])) {
                    $paidWithSale += $p['amount'];
                } else {
                    $paidAdditional += $p['amount'];
                }
            }
        }

        $totals = [
            'delivered_amt' => $rows->sum('delivered_amt'),
            'sold_amt' => $rows->sum('sold_amt'),
            'returned_amt' => $rows->sum('returned_amt'),
            'paid_amt' => $rows->sum('paid_amt'),
            'paid_with_sale' => $paidWithSale,
            'paid_additional' => $paidAdditional,
            'balance' => $rows->sum('balance'),
            // All time outstanding
            'all_time_balance' => $allTime->sum('balance'),
            'all_time_paid' => $allTime->sum('amount_paid'),
        ];

        return view('reports.remittance', compact(
            'branches', 'selectedBranch', 'from', 'to', 'rows', 'totals'
        ));
    }

    public function hub()
    {
        return view('reports.hub');
    }
}
