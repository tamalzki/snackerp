@extends('layouts.report')
@php $reportTitle = 'Production Report'; @endphp
@section('report-content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Production Report</h5>
</div>

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.production') }}"
              class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">From</label>
                <input type="date" name="from" class="form-control" value="{{ $from }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">To</label>
                <input type="date" name="to" class="form-control" value="{{ $to }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Product</label>
                <select name="product_id" class="form-select">
                    <option value="">All Products</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}"
                            {{ request('product_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                            ({{ $p->isManufactured() ? 'Manufactured' : 'Resale' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Production Batches</div>
                <div class="fw-bold fs-4">{{ $batches->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Total Output</div>
                <div class="fw-bold fs-4 text-success">
                    {{ qty_fmt($totalOutput) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Production Cost</div>
                <div class="fw-bold fs-4 text-primary">
                    ₱{{ number_format($totalCost, 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Total Rejects</div>
                <div class="fw-bold fs-4 text-danger">
                    {{ qty_fmt($totalRejects) }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Resale Restock Summary --}}
@if($restocks->count())
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Resale Restocks</div>
                <div class="fw-bold fs-4">{{ $restocks->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Restock Qty</div>
                <div class="fw-bold fs-4 text-success">
                    {{ qty_fmt($totalRestockQty) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Restock Cost</div>
                <div class="fw-bold fs-4 text-primary">
                    ₱{{ number_format($totalRestockCost, 2) }}
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Expiry Alerts --}}
@if($expired->count())
<div class="alert alert-danger mb-4">
    <i class="bi bi-x-circle-fill"></i>
    <strong>{{ $expired->count() }} expired batch(es):</strong>
    @foreach($expired as $b)
        <span class="badge bg-danger ms-1">
            {{ $b->finishedProduct->name }} — {{ $b->expiry_date->format('M d, Y') }}
        </span>
    @endforeach
</div>
@endif

@if($expiringSoon->count())
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <strong>{{ $expiringSoon->count() }} batch(es) expiring within 30 days:</strong>
    @foreach($expiringSoon as $b)
        <span class="badge bg-warning text-dark ms-1">
            {{ $b->finishedProduct->name }} — {{ $b->expiry_date->format('M d, Y') }}
        </span>
    @endforeach
</div>
@endif

{{-- Production Batches Table --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-gear-wide-connected"></i> Production Batches
            <span class="badge bg-primary ms-2">Manufactured</span>
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Date</th>
                    <th>Expected</th>
                    <th>Actual</th>
                    <th>Rejects</th>
                    <th>Reject Rate</th>
                    <th>Cost/Unit</th>
                    <th>Total Cost</th>
                    <th>Expiry</th>
                </tr>
            </thead>
            <tbody>
            @forelse($batches as $b)
                <tr>
                    <td class="text-muted small">{{ $b->id }}</td>
                    <td class="fw-semibold">{{ $b->finishedProduct->name }}</td>
                    <td>{{ $b->production_date->format('M d, Y') }}</td>
                    <td>{{ qty_fmt($b->expected_output_qty) }}</td>
                    <td class="text-success fw-semibold">
                        {{ qty_fmt($b->actual_output_qty) }}
                    </td>
                    <td class="{{ $b->reject_qty > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ qty_fmt($b->reject_qty) }}
                    </td>
                    <td>
                        @php $rate = $b->reject_rate; @endphp
                        <span class="badge {{ $rate == 0 ? 'bg-success' : ($rate <= 5 ? 'bg-warning text-dark' : 'bg-danger') }}">
                            {{ $rate }}%
                        </span>
                    </td>
                    <td>₱{{ number_format($b->cost_per_unit, 4) }}</td>
                    <td>₱{{ number_format($b->total_raw_material_cost, 2) }}</td>
                    <td>
                        @if($b->expiry_date)
                            @if($b->isExpired())
                                <span class="badge bg-danger">
                                    Expired {{ $b->expiry_date->format('M d, Y') }}
                                </span>
                            @elseif($b->isExpiringSoon())
                                <span class="badge bg-warning text-dark">
                                    {{ $b->expiry_date->format('M d, Y') }}
                                </span>
                            @else
                                <span class="badge bg-success">
                                    {{ $b->expiry_date->format('M d, Y') }}
                                </span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        No production batches for the selected period.
                    </td>
                </tr>
            @endforelse
            </tbody>
            @if($batches->count())
            <tfoot class="table-light">
                <tr>
                    <th colspan="4" class="text-end">Totals:</th>
                    <th class="text-success">{{ qty_fmt($totalOutput) }}</th>
                    <th class="text-danger">{{ qty_fmt($totalRejects) }}</th>
                    <th></th>
                    <th></th>
                    <th>₱{{ number_format($totalCost, 2) }}</th>
                    <th></th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- Resale Restocks Table --}}
@if($restocks->count())
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-cart-check"></i> Resale Restocks
            <span class="badge bg-success ms-2">Buy & Sell</span>
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Date</th>
                    <th>Qty Added</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Supplier</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
            @foreach($restocks as $r)
                <tr>
                    <td class="text-muted small">{{ $r->id }}</td>
                    <td class="fw-semibold">{{ $r->finishedProduct->name }}</td>
                    <td>{{ $r->restock_date->format('M d, Y') }}</td>
                    <td class="text-success fw-semibold">
                        +{{ qty_fmt($r->quantity) }}
                    </td>
                    <td>₱{{ number_format($r->unit_cost, 4) }}</td>
                    <td>₱{{ number_format($r->total_cost, 2) }}</td>
                    <td class="text-muted">{{ $r->supplier ?? '—' }}</td>
                    <td class="text-muted small">{{ $r->creator->name ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="3" class="text-end">Totals:</th>
                    <th class="text-success">{{ qty_fmt($totalRestockQty) }}</th>
                    <th></th>
                    <th>₱{{ number_format($totalRestockCost, 2) }}</th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

@endsection