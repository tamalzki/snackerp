@extends('layouts.app')
@section('title', $reportTitle ?? 'Reports')
@section('content')

<style>
    .report-tabs {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        background: #fff;
        padding: 10px 16px;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        border-left: 3px solid #007A5E;
        margin-bottom: 24px;
    }

    .report-tab {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: 8px;
        font-size: 0.84rem;
        font-weight: 500;
        color: #6b7280;
        text-decoration: none;
        border: 1.5px solid transparent;
        transition: all 0.15s;
        white-space: nowrap;
    }

    .report-tab:hover {
        background: #f0fdf4;
        color: #007A5E;
        border-color: #bbf7d0;
        text-decoration: none;
    }

    .report-tab.active {
        background: #007A5E;
        color: #fff !important;
        border-color: #007A5E;
        box-shadow: 0 2px 8px rgba(0,122,94,0.25);
        text-decoration: none;
    }

    .report-tab i { font-size: 0.9rem; }
</style>

{{-- Tab Bar --}}
<div class="report-tabs">
    <a href="{{ route('reports.production') }}"
       class="report-tab {{ request()->routeIs('reports.production') ? 'active' : '' }}">
        <i class="bi bi-gear-wide-connected"></i> Production
    </a>
    <a href="{{ route('reports.sales') }}"
       class="report-tab {{ request()->routeIs('reports.sales') ? 'active' : '' }}">
        <i class="bi bi-receipt"></i> Sales
    </a>
    <a href="{{ route('reports.inventory') }}"
       class="report-tab {{ request()->routeIs('reports.inventory') ? 'active' : '' }}">
        <i class="bi bi-boxes"></i> Inventory
    </a>
    <a href="{{ route('reports.profit-loss') }}"
       class="report-tab {{ request()->routeIs('reports.profit-loss') ? 'active' : '' }}">
        <i class="bi bi-graph-up-arrow"></i> Profit & Loss
    </a>
    <a href="{{ route('reports.branch-delivery') }}"
       class="report-tab {{ request()->routeIs('reports.branch-delivery') ? 'active' : '' }}">
        <i class="bi bi-truck"></i> Branch Delivery
    </a>
    <a href="{{ route('reports.remittance') }}"
       class="report-tab {{ request()->routeIs('reports.remittance') ? 'active' : '' }}">
        <i class="bi bi-cash-coin"></i> Remittance
    </a>
</div>

{{-- Report Content --}}
@yield('report-content')

@endsection
@push('scripts')
@stack('report-scripts')
@endpush