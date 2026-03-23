<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BranchInventory;
use App\Models\CashAccount;
use App\Models\ConsignmentSale;
use App\Models\Expense;
use App\Models\FinishedProduct;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use App\Models\Sale;
use App\Models\StockTransfer;

class DashboardController extends Controller
{
    public function index()
    {
        $mtdStart = now()->startOfMonth();
        $mtdEnd = now()->endOfMonth();

        // --- KPI Cards: consignment (primary) + legacy direct sales ---
        $mtdFrom = $mtdStart->format('Y-m-d');
        $mtdTo = $mtdEnd->format('Y-m-d');

        $consignmentRevenue = (float) ConsignmentSale::overlappingPeriod($mtdFrom, $mtdTo)
            ->sum('total_amount');
        $consignmentCOGS = (float) ConsignmentSale::overlappingPeriod($mtdFrom, $mtdTo)
            ->sum('total_cost');

        $legacyRevenue = (float) Sale::whereBetween('sale_date', [$mtdStart, $mtdEnd])
            ->sum('total_amount');
        $legacyCOGS = (float) Sale::whereBetween('sale_date', [$mtdStart, $mtdEnd])
            ->sum('total_cost');

        $totalRevenue = $consignmentRevenue + $legacyRevenue;
        $totalCOGS = $consignmentCOGS + $legacyCOGS;

        $grossProfit = $totalRevenue - $totalCOGS;

        $totalExpenses = (float) Expense::whereBetween('expense_date', [$mtdStart, $mtdEnd])
            ->sum('amount');

        $netProfit = $grossProfit - $totalExpenses;

        $cashBalance = CashAccount::sum('balance');
        $bankBalance = BankAccount::sum('balance');
        $totalBalance = $cashBalance + $bankBalance;

        // --- Inventory Alerts ---
        $lowStockMaterials = RawMaterial::whereRaw('stock_quantity <= low_stock_threshold')
            ->orderBy('name')->get();

        $lowStockProducts = FinishedProduct::whereRaw('current_stock <= low_stock_threshold')
            ->orderBy('name')->get();

        $expiringSoon = ProductionBatch::with('finishedProduct')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', now())
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->orderBy('expiry_date')
            ->get();

        // --- Sales Chart (last 7 days): consignment + legacy ---
        $salesChart = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            $d = $date->format('Y-m-d');
            $c = (float) ConsignmentSale::whereDate('sale_date_from', '<=', $d)
                ->whereDate('sale_date_to', '>=', $d)
                ->sum('total_amount');
            $l = (float) Sale::whereDate('sale_date', $d)->sum('total_amount');

            return ['date' => $date->format('M d'), 'total' => $c + $l];
        });

        // --- Recent Activity (merged, by date) ---
        $recentConsignment = ConsignmentSale::with('branch')
            ->orderByDesc('sale_date_to')
            ->orderByDesc('sale_date_from')
            ->take(8)
            ->get();
        $recentLegacy = Sale::with('branch')->latest('sale_date')->take(8)->get();

        $recentSales = $recentConsignment->map(function (ConsignmentSale $s) {
            return (object) [
                'branch' => $s->branch,
                'sale_date' => $s->sale_date_to,
                'period_label' => $s->periodLabel(),
                'total_amount' => $s->total_amount,
                'source' => 'consignment',
            ];
        })->concat($recentLegacy->map(function (Sale $s) {
            return (object) [
                'branch' => $s->branch,
                'sale_date' => $s->sale_date,
                'period_label' => null,
                'total_amount' => $s->total_amount,
                'source' => 'legacy',
            ];
        }))->sortByDesc(fn ($row) => $row->sale_date)->take(5)->values();
        $recentProduction = ProductionBatch::with('finishedProduct')->latest()->take(5)->get();
        $recentTransfers = StockTransfer::with('branch')->withCount('items')->latest()->take(5)->get();

        // --- Warehouse Stock Value ---
        $warehouseValue = FinishedProduct::all()
            ->sum(fn ($p) => $p->current_stock * $p->average_cost);

        // --- Branch Stock Value ---
        $branchValue = BranchInventory::all()
            ->sum(fn ($i) => $i->stock_quantity * $i->cost_snapshot);

        return view('dashboard', compact(
            'totalRevenue',
            'totalCOGS',
            'grossProfit',
            'totalExpenses',
            'netProfit',
            'cashBalance',
            'bankBalance',
            'totalBalance',
            'lowStockMaterials',
            'lowStockProducts',
            'expiringSoon',
            'salesChart',
            'recentSales',
            'recentProduction',
            'recentTransfers',
            'warehouseValue',
            'branchValue'
        ));
    }
}
