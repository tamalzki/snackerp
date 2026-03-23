@extends('layouts.app')
@section('title', 'Reports')
@section('content')

<div class="mb-4">
    <h5 class="fw-bold mb-1">Reports</h5>
    <p class="text-muted small mb-0">Open a report for filters, exports, and detail tables.</p>
</div>

<div class="row g-3">
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('reports.production') }}" class="text-decoration-none">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <span class="rounded-circle bg-success bg-opacity-10 text-success p-3">
                            <i class="bi bi-gear-wide-connected fs-4"></i>
                        </span>
                        <div>
                            <div class="fw-semibold text-dark">Production</div>
                            <div class="text-muted small">Batches, restocks, expiry</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('reports.sales') }}" class="text-decoration-none">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <span class="rounded-circle bg-primary bg-opacity-10 text-primary p-3">
                            <i class="bi bi-receipt fs-4"></i>
                        </span>
                        <div>
                            <div class="fw-semibold text-dark">Sales</div>
                            <div class="text-muted small">Consignment sales by branch</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('reports.inventory') }}" class="text-decoration-none">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <span class="rounded-circle bg-warning bg-opacity-10 text-warning p-3">
                            <i class="bi bi-boxes fs-4"></i>
                        </span>
                        <div>
                            <div class="fw-semibold text-dark">Inventory</div>
                            <div class="text-muted small">Warehouse, branches, raw materials</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('reports.profit-loss') }}" class="text-decoration-none">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <span class="rounded-circle bg-info bg-opacity-10 text-info p-3">
                            <i class="bi bi-graph-up-arrow fs-4"></i>
                        </span>
                        <div>
                            <div class="fw-semibold text-dark">Profit &amp; Loss</div>
                            <div class="text-muted small">Revenue, COGS, expenses</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('reports.branch-delivery') }}" class="text-decoration-none">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <span class="rounded-circle bg-secondary bg-opacity-10 text-secondary p-3">
                            <i class="bi bi-truck fs-4"></i>
                        </span>
                        <div>
                            <div class="fw-semibold text-dark">Branch delivery</div>
                            <div class="text-muted small">Delivered, sold, pull-outs</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-4">
        <a href="{{ route('reports.remittance') }}" class="text-decoration-none">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <span class="rounded-circle bg-dark bg-opacity-10 p-3">
                            <i class="bi bi-cash-coin fs-4"></i>
                        </span>
                        <div>
                            <div class="fw-semibold text-dark">Remittance</div>
                            <div class="text-muted small">Cash remitted with sales, per DR</div>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<style>
    .hover-shadow:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.08) !important; }
</style>

@endsection
