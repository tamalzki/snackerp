@php
    $cfYear = (int) request()->get('year', now()->year);
    $indexTab = request()->get('tab', 'daily');
    $dailyTabActive = request()->routeIs('daily-cash.today', 'daily-cash.show', 'daily-cash.open-date')
        || (request()->routeIs('daily-cash.index') && $indexTab === 'daily');
    $monthlyTabActive = request()->routeIs('daily-cash.index') && $indexTab === 'monthly';
    $annualTabActive = request()->routeIs('daily-cash.index') && $indexTab === 'annual';
@endphp
<nav class="cashflow-bottom-tabs" aria-label="Cashflow workbook tabs">
    <a href="{{ route('daily-cash.guide') }}"
       class="cashflow-tab cashflow-tab--guide {{ request()->routeIs('daily-cash.guide') ? 'active' : '' }}">Guide</a>
    <a href="{{ route('daily-cash.today') }}"
       class="cashflow-tab cashflow-tab--daily {{ $dailyTabActive ? 'active' : '' }}">Daily cash flow</a>
    <a href="{{ route('daily-cash.index', ['tab' => 'monthly', 'year' => $cfYear]) }}"
       class="cashflow-tab cashflow-tab--monthly {{ $monthlyTabActive ? 'active' : '' }}">Monthly cash flow</a>
    <a href="{{ route('daily-cash.index', ['tab' => 'annual']) }}"
       class="cashflow-tab cashflow-tab--annual {{ $annualTabActive ? 'active' : '' }}">Annual cash flow</a>
    <a href="{{ route('daily-cash.statements.income', ['year' => $cfYear]) }}"
       class="cashflow-tab cashflow-tab--income {{ request()->routeIs('daily-cash.statements.income') ? 'active' : '' }}">Income</a>
    <a href="{{ route('daily-cash.statements.expenses', ['year' => $cfYear]) }}"
       class="cashflow-tab cashflow-tab--expenses {{ request()->routeIs('daily-cash.statements.expenses') ? 'active' : '' }}">Expenses</a>
    <a href="{{ route('daily-cash.statements.discretionary', ['year' => $cfYear]) }}"
       class="cashflow-tab cashflow-tab--discretionary {{ request()->routeIs('daily-cash.statements.discretionary') ? 'active' : '' }}">Discretionary</a>
    <a href="{{ route('daily-cash.statements.savings', ['year' => $cfYear]) }}"
       class="cashflow-tab cashflow-tab--savings {{ request()->routeIs('daily-cash.statements.savings') ? 'active' : '' }}">Savings</a>
</nav>
