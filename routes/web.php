<?php

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CashAccountController;
use App\Http\Controllers\ConsignmentController;
use App\Http\Controllers\DailyCashController;
use App\Http\Controllers\DailyCashStatementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinishedProductController;
use App\Http\Controllers\ProductionBatchController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\RawMaterialPurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Inventory
    Route::resource('raw-materials', RawMaterialController::class);
    Route::resource('purchases', RawMaterialPurchaseController::class)
        ->only(['index', 'create', 'store', 'show']);
    Route::resource('finished-products', FinishedProductController::class);
    Route::get('finished-products/{finishedProduct}/restock', [FinishedProductController::class, 'restockForm'])->name('finished-products.restock');
    Route::post('finished-products/{finishedProduct}/restock', [FinishedProductController::class, 'restockStore'])->name('finished-products.restock.store');
    Route::get('finished-products/{finishedProduct}/adjust', [FinishedProductController::class, 'adjustForm'])->name('finished-products.adjust');
    Route::post('finished-products/{finishedProduct}/adjust', [FinishedProductController::class, 'adjustStore'])->name('finished-products.adjust.store');

    // Production
    Route::resource('production', ProductionBatchController::class);
    Route::get('production/last-recipe/{product}', [ProductionBatchController::class, 'lastRecipe'])
        ->name('production.last-recipe');

    // Distribution
    Route::resource('transfers', StockTransferController::class);

    // Sales (legacy direct branch sales)
    Route::resource('sales', SaleController::class);

    // Finance (admin / treasury)
    Route::middleware(['can:manage-bank'])->group(function () {
        Route::resource('cash-accounts', CashAccountController::class);
        Route::resource('bank-accounts', BankAccountController::class);
        Route::resource('deposits', DepositController::class)
            ->only(['index', 'create', 'store', 'show']);
        Route::resource('expenses', ExpenseController::class)
            ->only(['index', 'create', 'store', 'show']);
    });

    // Admin
    Route::middleware(['can:manage-branches'])->group(function () {
        Route::resource('branches', BranchController::class);
    });

    Route::middleware(['can:manage-users'])->group(function () {
        Route::resource('users', UserController::class);
    });

    // Reports
    Route::middleware(['can:view-reports'])->group(function () {
        Route::get('reports', [ReportController::class, 'hub'])->name('reports.hub');
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('production', [ReportController::class, 'production'])->name('production');
            Route::get('sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('inventory', [ReportController::class, 'inventory'])->name('inventory');
            Route::get('profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
            Route::get('branch-delivery', [ReportController::class, 'branchDelivery'])->name('branch-delivery');
            Route::get('remittance', [ReportController::class, 'remittance'])->name('remittance');
        });
    });

    // Daily Cash Flow
    Route::get('daily-cash/today', [DailyCashController::class, 'today'])->name('daily-cash.today');
    Route::get('daily-cash', [DailyCashController::class, 'index'])->name('daily-cash.index');
    Route::post('daily-cash', [DailyCashController::class, 'store'])->name('daily-cash.store');
    Route::post('daily-cash/subcategory-override', [DailyCashController::class, 'bulkSubcategoryOverride'])->name('daily-cash.subcategory-override');
    Route::get('daily-cash/open/{date}', [DailyCashController::class, 'openDate'])
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->name('daily-cash.open-date');
    Route::get('daily-cash/statements/income', [DailyCashStatementController::class, 'income'])->name('daily-cash.statements.income');
    Route::get('daily-cash/statements/expenses', [DailyCashStatementController::class, 'expenses'])->name('daily-cash.statements.expenses');
    Route::get('daily-cash/statements/discretionary', [DailyCashStatementController::class, 'discretionary'])->name('daily-cash.statements.discretionary');
    Route::get('daily-cash/statements/savings', [DailyCashStatementController::class, 'savings'])->name('daily-cash.statements.savings');
    Route::post('daily-cash/statements/entry', [DailyCashStatementController::class, 'storeEntry'])->name('daily-cash.statements.store-entry');
    Route::get('daily-cash/{dailyCash}', [DailyCashController::class, 'show'])->name('daily-cash.show');
    Route::put('daily-cash/{dailyCash}', [DailyCashController::class, 'update'])->name('daily-cash.update');
    Route::post('daily-cash/{dailyCash}/entries', [DailyCashController::class, 'storeEntry'])->name('daily-cash.entries.store');
    Route::put('daily-cash/{dailyCash}/entries/{entry}', [DailyCashController::class, 'updateEntry'])->name('daily-cash.entries.update');
    Route::delete('daily-cash/{dailyCash}/entries/{entry}', [DailyCashController::class, 'destroyEntry'])->name('daily-cash.entries.destroy');
    Route::post('daily-cash/{dailyCash}/deposit', [DailyCashController::class, 'depositToBank'])->name('daily-cash.deposit');

    // Consignment
    Route::get('consignment', [ConsignmentController::class, 'index'])->name('consignment.index');
    Route::get('consignment/branch/{branch}', [ConsignmentController::class, 'branch'])->name('consignment.branch');
    Route::get('consignment/branch/{branch}/transfer-products/create', [ConsignmentController::class, 'createBranchTransfer'])->name('consignment.branch-transfer.create');
    Route::post('consignment/branch/{branch}/transfer-products', [ConsignmentController::class, 'storeBranchTransfer'])->name('consignment.branch-transfer.store');
    Route::get('consignment/{receivable}', [ConsignmentController::class, 'show'])->name('consignment.show');
    Route::get('consignment/{receivable}/sale/create', [ConsignmentController::class, 'createSale'])->name('consignment.sale.create');
    Route::post('consignment/{receivable}/sale', [ConsignmentController::class, 'storeSale'])->name('consignment.sale.store');
    Route::get('consignment/{receivable}/pullout/create', [ConsignmentController::class, 'createPullOut'])->name('consignment.pullout.create');
    Route::post('consignment/{receivable}/pullout', [ConsignmentController::class, 'storePullOut'])->name('consignment.pullout.store');
    Route::post('consignment/{receivable}/payment', [ConsignmentController::class, 'storePayment'])->name('consignment.payment.store');
});

require __DIR__.'/auth.php';
