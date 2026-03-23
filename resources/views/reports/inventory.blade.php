@extends('layouts.report')
@php $reportTitle = 'Inventory Report'; @endphp
@section('report-content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Inventory Report</h5>
</div>

{{-- Alerts --}}
@if($lowStockItems->count() || $lowStockProducts->count())
<div class="alert alert-danger mb-4">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>
    <strong>Stock Alerts:</strong>
    @foreach($lowStockItems as $m)
        <span class="badge bg-danger ms-1">
            {{ $m->name }} — {{ qty_fmt($m->stock_quantity) }} {{ $m->unit }}
        </span>
    @endforeach
    @foreach($lowStockProducts as $p)
        <span class="badge bg-warning text-dark ms-1">
            {{ $p->name }} — {{ qty_fmt($p->current_stock) }} pcs
        </span>
    @endforeach
</div>
@endif

{{-- Overall Value Summary --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Warehouse Stock Value</div>
                <div class="fw-bold fs-4 text-primary">
                    ₱{{ number_format($warehouseValue, 2) }}
                </div>
                <div class="text-muted" style="font-size:0.72rem;">finished products at cost</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Branch Stock Value</div>
                <div class="fw-bold fs-4 text-success">
                    ₱{{ number_format($totalBranchValue, 2) }}
                </div>
                <div class="text-muted" style="font-size:0.72rem;">at cost snapshot</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Raw Materials Value</div>
                <div class="fw-bold fs-4 text-secondary">
                    ₱{{ number_format($rawMaterialValue, 2) }}
                </div>
                <div class="text-muted" style="font-size:0.72rem;">at cost per unit</div>
            </div>
        </div>
    </div>
</div>

{{-- Consignment Outstanding --}}
@if($consignmentSummary->count())
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <span>
            <i class="bi bi-receipt"></i> Outstanding Consignment Balances
        </span>
        <a href="{{ route('consignment.index') }}"
           class="btn btn-sm btn-outline-primary btn-action">
            <i class="bi bi-eye"></i> View Consignment
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Branch</th>
                    <th>Open DRs</th>
                    <th class="text-end">Outstanding Balance</th>
                </tr>
            </thead>
            <tbody>
            @foreach($consignmentSummary as $c)
                <tr>
                    <td class="fw-semibold">{{ $c['name'] }}</td>
                    <td>
                        <span class="badge bg-danger">{{ $c['drs'] }}</span>
                    </td>
                    <td class="text-end fw-bold text-danger">
                        ₱{{ number_format($c['balance'], 2) }}
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="2" class="text-end">Total Outstanding:</th>
                    <th class="text-end text-danger">
                        ₱{{ number_format($consignmentSummary->sum('balance'), 2) }}
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Warehouse Stock --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-building"></i> Warehouse Stock — Finished Products</span>
        <span class="text-success fw-semibold small">
            Value: ₱{{ number_format($warehouseValue, 2) }}
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Type</th>
                    <th class="text-center">Stock</th>
                    <th class="text-end">Avg Cost</th>
                    <th class="text-end">Stock Value</th>
                    <th class="text-end">Selling Price</th>
                    <th class="text-center">Margin</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($warehouseStock as $p)
                <tr>
                    <td class="fw-semibold">{{ $p->name }}</td>
                    <td>
                        @if($p->isManufactured())
                            <span class="badge bg-primary">Manufactured</span>
                        @else
                            <span class="badge bg-success">Resale</span>
                        @endif
                    </td>
                    <td class="text-center">{{ qty_fmt($p->current_stock) }}</td>
                    <td class="text-end">₱{{ number_format($p->average_cost, 4) }}</td>
                    <td class="text-end">
                        ₱{{ number_format($p->current_stock * $p->average_cost, 2) }}
                    </td>
                    <td class="text-end">₱{{ number_format($p->selling_price, 2) }}</td>
                    <td class="text-center">
                        @if($p->average_cost > 0)
                            @php
                                $m = (($p->selling_price - $p->average_cost) / $p->selling_price) * 100;
                            @endphp
                            <span class="badge {{ $m >= 30 ? 'bg-success' : ($m >= 10 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ number_format($m, 1) }}%
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($p->isLowStock())
                            <span class="badge bg-danger">Low Stock</span>
                        @else
                            <span class="badge bg-success">OK</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">No products.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Branch Stock --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-shop"></i> Branch Inventory</span>
        <span class="text-success fw-semibold small">
            Value: ₱{{ number_format($totalBranchValue, 2) }}
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Branch</th>
                    <th>Product</th>
                    <th class="text-center">Stock</th>
                    <th class="text-end">Cost Snapshot</th>
                    <th class="text-end">Stock Value</th>
                </tr>
            </thead>
            <tbody>
            @forelse($branches as $branch)
                @if(isset($branchStock[$branch->id]) && $branchStock[$branch->id]->count())
                    @foreach($branchStock[$branch->id] as $inv)
                    <tr>
                        <td class="fw-semibold">{{ $branch->name }}</td>
                        <td>{{ $inv->finishedProduct->name }}</td>
                        <td class="text-center">
                            <span class="{{ $inv->stock_quantity > 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                {{ qty_fmt($inv->stock_quantity) }}
                            </span>
                        </td>
                        <td class="text-end">₱{{ number_format($inv->cost_snapshot, 4) }}</td>
                        <td class="text-end">
                            ₱{{ number_format($inv->stock_quantity * $inv->cost_snapshot, 2) }}
                        </td>
                    </tr>
                    @endforeach
                    {{-- Branch subtotal --}}
                    <tr style="background:#f8fafc;">
                        <td colspan="4" class="text-end text-muted small">
                            {{ $branch->name }} subtotal:
                        </td>
                        <td class="text-end fw-semibold text-primary">
                            ₱{{ number_format($branchValues[$branch->id] ?? 0, 2) }}
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">No branch stock.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Raw Materials --}}
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-boxes"></i> Raw Materials Stock</span>
        <span class="text-primary fw-semibold small">
            Value: ₱{{ number_format($rawMaterialValue, 2) }}
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th class="text-center">Stock</th>
                    <th>Unit</th>
                    <th class="text-end">Cost/Unit</th>
                    <th class="text-end">Stock Value</th>
                    <th class="text-center">Threshold</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rawMaterials as $m)
                <tr>
                    <td class="fw-semibold">{{ $m->name }}</td>
                    <td>
                        <span class="badge {{ $m->category === 'ingredients' ? 'bg-primary' : 'bg-info text-dark' }}">
                            {{ $m->category === 'ingredients' ? '🧂 Ingredients' : '📦 Packaging' }}
                        </span>
                    </td>
                    <td class="text-center {{ $m->isLowStock() ? 'text-danger fw-bold' : '' }}">
                        {{ qty_fmt($m->stock_quantity) }}
                    </td>
                    <td>{{ $m->unit }}</td>
                    <td class="text-end">₱{{ number_format($m->cost_per_unit, 2) }}</td>
                    <td class="text-end">
                        ₱{{ number_format($m->stock_quantity * $m->cost_per_unit, 2) }}
                    </td>
                    <td class="text-center">{{ qty_fmt($m->low_stock_threshold) }}</td>
                    <td class="text-center">
                        @if($m->isLowStock())
                            <span class="badge bg-danger">
                                <i class="bi bi-exclamation-triangle"></i> Low
                            </span>
                        @else
                            <span class="badge bg-success">OK</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">No raw materials.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection