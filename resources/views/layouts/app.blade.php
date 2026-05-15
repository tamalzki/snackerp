<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angie ERP — @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <style>
    body { background: #f0f2f5; font-size: 0.9rem; }

    .sidebar {
        width: 240px;
        height: 100vh;
        background: #004d3b;
        position: fixed;
        top: 0; left: 0;
        z-index: 100;
        overflow-y: auto;
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
    }

    /* Brand */
    .sidebar .brand {
    padding: 12px 20px 10px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 2px;
}

.sidebar .brand .brand-script {
    font-family: 'Playfair Display', serif;
    font-style: italic;
    font-size: 1.6rem;
    color: #fff;
    line-height: 1;
    letter-spacing: 0.3px;
}

.sidebar .brand .brand-sub {
    font-size: 0.5rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.45);
    font-weight: 600;
    white-space: nowrap;
}

    /* Nav scroll area */
    .sidebar .sidebar-nav {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding-bottom: 4px;
    }

    /* Nav sections */
    .sidebar .nav-section {
        padding: 11px 16px 3px;
        font-size: 0.65rem;
        text-transform: uppercase;
        color: rgba(255,255,255,0.35);
        letter-spacing: 1.2px;
        font-weight: 600;
    }

    .sidebar .nav-link {
        color: rgba(255,255,255,0.65);
        padding: 7px 12px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        margin: 1px 6px;
        transition: all 0.15s;
    }

    .sidebar .nav-link:hover {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }

    .sidebar .nav-link.active {
        background: #007A5E;
        color: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .sidebar .nav-link i {
        font-size: 0.9rem;
        width: 16px;
    }

    .sidebar-footer {
        margin-top: auto;
        padding: 8px;
        border-top: 1px solid rgba(255,255,255,0.1);
        background: #004d3b;
        flex-shrink: 0;
    }

    .sidebar-footer .nav-link {
        color: rgba(255,255,255,0.5);
    }

    .sidebar-footer .nav-link:hover {
        color: #fff;
        background: rgba(255,255,255,0.08);
    }

    /* Main content */
    .main-content {
        margin-left: 240px;
        padding: 24px;
        min-height: 100vh;
    }

    /* Topbar */
    .topbar {
        background: #fff;
        border-radius: 10px;
        padding: 12px 20px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        border-left: 3px solid #007A5E;
    }

    .topbar .page-title {
        font-weight: 700;
        font-size: 1rem;
        color: #1a2234;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .topbar .page-title .brand-prefix {
        font-family: 'Playfair Display', serif;
        font-style: italic;
        color: #007A5E;
        font-size: 1.1rem;
        font-weight: 700;
    }

    .topbar .page-title .separator {
        color: #d1d5db;
        font-weight: 300;
    }

    .topbar .user-info {
        font-size: 0.82rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .topbar .user-info .role-badge {
        background: #e6f4f1;
        color: #007A5E;
        border-radius: 20px;
        padding: 2px 10px;
        font-weight: 600;
        font-size: 0.78rem;
    }

    /* Cards */
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }

    .card-header {
        background: #fff;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 600;
        padding: 14px 20px;
        border-radius: 10px 10px 0 0 !important;
    }

    /* Buttons */
    .btn-primary {
        background: #007A5E;
        border-color: #007A5E;
    }

    .btn-primary:hover {
        background: #005C47;
        border-color: #005C47;
    }

    .btn-primary:focus,
    .btn-primary:active {
        background: #005C47;
        border-color: #005C47;
        box-shadow: 0 0 0 3px rgba(0,122,94,0.2);
    }

    /* Table */
.table thead th {
    background: #004d3b;
    color: #fff;
    border: none;
    font-weight: 500;
    font-size: 0.82rem;
}

/* Sub-table header override (used in collapsible sale items) */
.table thead.sub-header th {
    background: #f0f2f5;
    color: #374151;
    font-weight: 600;
    font-size: 0.78rem;
}

    .table tbody tr:hover { background: #f8fafc; }
    .badge { font-weight: 500; }

    /* Misc */
    .btn-action { display: inline-flex; align-items: center; gap: 5px; font-size: 0.82rem; }
    .pagination { margin-bottom: 0; }
    .page-link { color: #007A5E; }
    .page-item.active .page-link { background: #007A5E; border-color: #007A5E; }
    .page-link:hover { color: #005C47; }

    /* Daily Cashflow distinct style */
    .sidebar .cashflow-section {
        padding: 11px 16px 3px;
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        font-weight: 700;
        color: #f5c842;
    }
    .sidebar .nav-link.cashflow-link {
        color: #f5e6a3;
        background: rgba(245,200,66,0.1);
        margin: 1px 6px;
        border-radius: 6px;
        padding: 7px 12px;
        font-size: 15px;
        border: 1px solid rgba(245,200,66,0.25);
    }
    .sidebar .nav-link.cashflow-link:hover {
        background: rgba(245,200,66,0.2);
        color: #fff;
        border-color: rgba(245,200,66,0.5);
    }
    .sidebar .nav-link.cashflow-link.active {
        background: #b8880f;
        color: #fff;
        border-color: #f5c842;
        box-shadow: 0 2px 8px rgba(0,0,0,0.25);
    }

    /* Workbook-style bottom tabs (daily cashflow) — matches spreadsheet tab strip */
    body.cashflow-bottom-tabs-active .main-content {
        padding-bottom: 3.75rem;
    }
    .cashflow-bottom-tabs {
        position: fixed;
        bottom: 0;
        left: 240px;
        right: 0;
        z-index: 200;
        display: flex;
        flex-wrap: nowrap;
        gap: 3px;
        padding: 6px 10px 10px;
        background: #d9d9d9;
        box-shadow: 0 -3px 12px rgba(0,0,0,0.12);
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        align-items: flex-end;
    }
    .cashflow-bottom-tabs .cashflow-tab {
        flex: 0 0 auto;
        padding: 0.45rem 0.75rem;
        font-size: 0.72rem;
        font-weight: 600;
        text-decoration: none;
        color: #1e293b;
        border-radius: 6px 6px 0 0;
        border: 1px solid rgba(0,0,0,0.12);
        border-bottom: none;
        white-space: nowrap;
        line-height: 1.2;
        opacity: 0.92;
        transition: opacity 0.15s, transform 0.15s;
    }
    .cashflow-bottom-tabs .cashflow-tab:hover {
        opacity: 1;
        color: #0f172a;
    }
    .cashflow-bottom-tabs .cashflow-tab.active {
        opacity: 1;
        font-weight: 700;
        box-shadow: 0 -2px 0 rgba(0,0,0,0.15) inset;
        transform: translateY(-1px);
        outline: 2px solid rgba(15, 23, 42, 0.35);
        outline-offset: -1px;
    }
    .cashflow-tab--daily { background: #f9a8d4; color: #4a044e !important; }
    .cashflow-tab--monthly { background: #93c5fd; }
    .cashflow-tab--annual { background: #86efac; }
    .cashflow-tab--income { background: #d8b4fe; }
    .cashflow-tab--expenses { background: #60a5fa; color: #0f172a !important; }
    .cashflow-tab--discretionary { background: #bef264; }
    .cashflow-tab--savings { background: #c4b5fd; }
    </style>
</head>
<body class="@if(request()->routeIs('daily-cash.*')) cashflow-bottom-tabs-active @endif">

{{-- SIDEBAR --}}
<nav class="sidebar">
    <div class="brand">
        <span class="brand-script">Angie</span>
        <span class="brand-sub">Inventory · Sales · Finance</span>
    </div>

    <div class="sidebar-nav">
        {{-- DAILY CASHFLOW — pinned at top --}}
        <div class="cashflow-section">💰 Daily Cashflow</div>
        <a href="{{ route('daily-cash.today') }}"
           class="nav-link cashflow-link {{ request()->routeIs('daily-cash.*') ? 'active' : '' }}">
            <i class="bi bi-journal-text"></i> Daily Cashflow
        </a>

        {{-- MAIN --}}
        <div class="nav-section">Main</div>
        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        {{-- INVENTORY --}}
        <div class="nav-section">Inventory</div>
        <a href="{{ route('raw-materials.index') }}"
           class="nav-link {{ request()->routeIs('raw-materials.*') ? 'active' : '' }}">
            <i class="bi bi-boxes"></i> Raw Materials
        </a>
        <a href="{{ route('purchases.index') }}"
           class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
            <i class="bi bi-cart-plus"></i> RM Purchases
        </a>
        <a href="{{ route('finished-products.index') }}"
           class="nav-link {{ request()->routeIs('finished-products.*') ? 'active' : '' }}">
            <i class="bi bi-bag-check"></i> Finished Products
        </a>

        @can('manage-branches')
        <a href="{{ route('branches.index') }}"
           class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}">
            <i class="bi bi-shop"></i> Branches
        </a>
        @endcan

        @can('manage-users')
        <a href="{{ route('users.index') }}"
           class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Users
        </a>
        @endcan

        {{-- DISTRIBUTION --}}
        <div class="nav-section">Distribution</div>
        <a href="{{ route('transfers.index') }}"
           class="nav-link {{ request()->routeIs('transfers.*') ? 'active' : '' }}">
            <i class="bi bi-arrow-left-right"></i> Stock Transfers
        </a>

        {{-- SALES --}}
<div class="nav-section">Sales & Consignment</div>
<a href="{{ route('consignment.index') }}"
   class="nav-link {{ request()->routeIs('consignment.*') ? 'active' : '' }}">
    <i class="bi bi-receipt"></i> Consignment
</a>

        {{-- FINANCE --}}
        @can('manage-bank')
        <div class="nav-section">Finance</div>
        <a href="{{ route('bank-accounts.index') }}"
           class="nav-link {{ request()->routeIs('bank-accounts.*') ? 'active' : '' }}">
            <i class="bi bi-bank"></i> Bank Accounts
        </a>
        <a href="{{ route('deposits.index') }}"
           class="nav-link {{ request()->routeIs('deposits.*') ? 'active' : '' }}">
            <i class="bi bi-safe"></i> Deposits
        </a>
        <a href="{{ route('expenses.index') }}"
           class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
            <i class="bi bi-cash-stack"></i> Expenses
        </a>
        @endcan

        {{-- REPORTS --}}
        @can('view-reports')
<div class="nav-section">Reports</div>
<a href="{{ route('reports.hub') }}"
   class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
    <i class="bi bi-bar-chart-line"></i> Reports
</a>
@endcan

        

    </div>{{-- end .sidebar-nav --}}

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                <i class="bi bi-box-arrow-left"></i> Logout
            </button>
        </form>
    </div>
</nav>

{{-- MAIN CONTENT --}}
<div class="main-content">
    <div class="topbar">
        <div class="page-title">
            <span class="brand-prefix">Angie</span>
            <span class="separator">|</span>
            <span>@yield('title', 'Dashboard')</span>
        </div>
        <div class="user-info">
            <i class="bi bi-person-circle"></i>
            {{ auth()->user()->name }}
            <span class="role-badge">{{ ucfirst(auth()->user()->role) }}</span>
        </div>
    </div>

    {{-- FLASH MESSAGES --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            {{ session('success') }}
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ session('error') }}
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</div>

@if(request()->routeIs('daily-cash.*'))
    @include('daily-cash._bottom-tabs')
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@stack('scripts')
</body>
</html>