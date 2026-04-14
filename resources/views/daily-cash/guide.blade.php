@extends('layouts.app')
@section('title', 'Daily cash flow — Guide')

@section('content')
@php $psM = (int) config('daily_cashflow.period_start_month', 3);
    $psD = (int) config('daily_cashflow.period_start_day', 1);
    $periodStartLabel = \Carbon\Carbon::createFromDate(2000, $psM, $psD)->format('F j');
@endphp
<div class="card border-0 shadow-sm">
    <div class="card-header py-3" style="background: linear-gradient(180deg, #c8f5a0 0%, #a8e078 100%); border: none;">
        <h1 class="h4 mb-0 fw-bold" style="color: #0d47a1;">Daily cash flow guide</h1>
        <p class="mb-0 small text-muted">How the workbook fits together: daily ledger, bank movements, reports, and carry-forward balances.</p>
    </div>
    <div class="card-body">

        <section class="mb-4">
            <h2 class="h6 fw-bold" style="color:#0d47a1;">Cash period &amp; carry-forward</h2>
            <p class="small text-muted mb-2">Your fiscal cash period starts on <strong>{{ $periodStartLabel }}</strong> each year (see <code>config/daily_cashflow.php</code>). Opening balances do not carry from the day before that anchor into the new period.</p>
            <p class="small text-muted mb-0"><strong>Within</strong> the period, each day’s closing cash becomes the next recorded day’s starting point. When you add, edit, or delete a line—or correct total cash—the app recalculates <strong>later days through today</strong> so the chain stays consistent.</p>
        </section>

        <section class="mb-4">
            <h2 class="h6 fw-bold" style="color:#0d47a1;">Daily ledger: entry types</h2>
            <ul class="small text-muted mb-0 ps-3">
                <li><strong>Capital</strong> — owner injections or similar inflows.</li>
                <li><strong>Income</strong> — sales and other revenue (shown in the summary <em>excluding</em> bank withdrawals).</li>
                <li><strong>Cash from Bank — Withdrawals</strong> — choose this under Add Entry (same as Income with the bank-withdrawal category). It <strong>increases total available cash</strong> and appears separately in the daily summary.</li>
                <li><strong>Expenses / Purchases</strong> — operating costs and inventory buys.</li>
                <li><strong>Discretionary</strong> — variable or personal spending (including at-home / “sa balay” style items).</li>
                <li><strong>Savings</strong> — cash set aside or moved out of the till; <strong>reduces</strong> total available cash for the day.</li>
                <li><strong>Other</strong> — anything that does not fit the rows above (also reduces net like savings in the total).</li>
            </ul>
        </section>

        <section class="mb-4">
            <h2 class="h6 fw-bold" style="color:#0d47a1;">Deposit to bank</h2>
            <p class="small text-muted mb-0">From the daily screen, <strong>Deposit to Bank</strong> creates a formal deposit record, credits your bank account, and adds a <strong>Savings</strong> line on that day. That <strong>lowers total available cash</strong> (money left the till). You cannot deposit more than today’s available cash.</p>
        </section>

        <section class="mb-4">
            <h2 class="h6 fw-bold" style="color:#0d47a1;">Editing &amp; data quality</h2>
            <ul class="small text-muted mb-0 ps-3">
                <li>Descriptions are stored in <strong>uppercase</strong> for consistency.</li>
                <li>Changing an entry’s <strong>type</strong> clears a category that only applied to the old type (for example, a transportation preset is dropped if you switch to Income).</li>
                <li>Changing the <strong>type</strong> also clears any <strong>report subcategory override</strong> you set from Monthly/Annual, so classifications stay aligned.</li>
                <li><strong>Correct Total Available Cash</strong> adjusts today’s opening balance from your physical count; it must not be lower than today’s net from entries.</li>
            </ul>
        </section>

        <section class="mb-4">
            <h2 class="h6 fw-bold" style="color:#0d47a1;">Statements &amp; rollups</h2>
            <p class="small text-muted mb-2"><strong>Income, Expenses, Discretionary, and Savings</strong> statements add lines to the <strong>same</strong> daily ledger for the date you pick. Use <strong>Monthly</strong> for a year grid by description, and <strong>Annual</strong> for year totals with expandable detail.</p>
            <p class="small text-muted mb-0">On Monthly/Annual you can <strong>recategorize</strong> a line group (pencil icon): that stores a subcategory override on every matching entry for that calendar year. Choose <strong>Auto</strong> to go back to keyword-based labels from the description.</p>
        </section>

        <div class="row g-3 mt-1">
            <div class="col-md-4">
                <div class="p-3 rounded-3 h-100" style="background: #fdf4ff; border: 1px solid #f0abfc;">
                    <h3 class="h6 fw-bold" style="color: #86198f;">Daily</h3>
                    <p class="small text-muted mb-0">Week strip, summary, entries, deposits, and starting-cash correction.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3 h-100" style="background: #eff6ff; border: 1px solid #93c5fd;">
                    <h3 class="h6 fw-bold" style="color: #1d4ed8;">Monthly</h3>
                    <p class="small text-muted mb-0">Spreadsheet-style months × lines; subcategories from keywords or your overrides.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3 h-100" style="background: #f0fdf4; border: 1px solid #86efac;">
                    <h3 class="h6 fw-bold" style="color: #15803d;">Annual</h3>
                    <p class="small text-muted mb-0">Year rollups and per-year detail tables with the same subcategory tools.</p>
                </div>
            </div>
        </div>

        <p class="small text-muted mt-4 mb-0">Use the <strong>colored tabs at the bottom</strong> of the app (when you are in Daily Cash) to jump between Daily, Monthly, Annual, and this Guide—similar to workbook tabs.</p>
    </div>
</div>
@endsection
