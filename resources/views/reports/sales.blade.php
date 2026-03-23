@extends('layouts.report')
@php $reportTitle = 'Sales Report'; @endphp
@section('report-content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Sales Report</h5>
</div>

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.sales') }}"
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
                <label class="form-label fw-semibold">Branch</label>
                <select name="branch_id" class="form-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}"
                            {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
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

<div class="alert alert-light border small mb-4">
    <i class="bi bi-calendar-range text-primary me-1"></i>
    Consignment sales use a <strong>sale period</strong> (from–to). A row appears if that period <strong>overlaps</strong>
    your filter dates (e.g. a weekly entry touches any day in the range).
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Total Revenue</div>
                <div class="fw-bold fs-4 text-success">
                    ₱{{ number_format($totalSales, 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Total COGS</div>
                <div class="fw-bold fs-4 text-danger">
                    ₱{{ number_format($totalCost, 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Gross Profit</div>
                <div class="fw-bold fs-4 text-primary">
                    ₱{{ number_format($grossProfit, 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Margin</div>
                <div class="fw-bold fs-4">
                    <span class="badge fs-6 {{ $margin >= 30 ? 'bg-success' : ($margin >= 10 ? 'bg-warning text-dark' : 'bg-danger') }}">
                        {{ number_format($margin, 1) }}%
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Sales by Branch --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Sales by Branch</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Sales Entries</th>
                            <th>Revenue</th>
                            <th>Cost</th>
                            <th>Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($byBranch as $b)
                        <tr>
                            <td class="fw-semibold">{{ $b['name'] }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $b['count'] }}</span>
                            </td>
                            <td class="text-success">
                                ₱{{ number_format($b['total_sales'], 2) }}
                            </td>
                            <td class="text-muted">
                                ₱{{ number_format($b['total_cost'], 2) }}
                            </td>
                            <td class="fw-semibold text-primary">
                                ₱{{ number_format($b['profit'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">No data</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Top Products --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Top 10 Products by Revenue</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($topProducts as $p)
                        <tr>
                            <td class="fw-semibold">{{ $p['name'] }}</td>
                            <td>{{ qty_fmt($p['qty']) }}</td>
                            <td class="text-success fw-semibold">
                                ₱{{ number_format($p['revenue'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">No data</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- All Sales Table --}}
<div class="card">
    <div class="card-header">All Sales Records</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Branch</th>
                    <th>DR #</th>
                    <th>Sale period</th>
                    <th>Items</th>
                    <th>Revenue</th>
                    <th>Cost</th>
                    <th>Profit</th>
                    <th>Margin</th>
                </tr>
            </thead>
            <tbody>
            @forelse($sales as $s)
                <tr>
                    <td class="text-muted small">{{ $s->id }}</td>
                    <td class="fw-semibold">{{ $s->branch->name }}</td>
                    <td>
                        @if($s->receivable)
                            <a href="{{ route('consignment.show', $s->receivable) }}"
                               class="badge bg-dark font-monospace text-decoration-none">
                                {{ $s->receivable->dr_number ?? '#' . $s->receivable->id }}
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="small">{{ $s->periodLabel() }}</td>
                    <td>
                        <span class="badge bg-secondary">
                            {{ $s->items->count() }} items
                        </span>
                    </td>
                    <td class="text-success fw-semibold">
                        ₱{{ number_format($s->total_amount, 2) }}
                    </td>
                    <td class="text-muted">
                        ₱{{ number_format($s->total_cost, 2) }}
                    </td>
                    <td class="fw-semibold">
                        ₱{{ number_format($s->gross_profit, 2) }}
                    </td>
                    <td>
                        @php
                            $m = $s->total_amount > 0
                                ? ($s->gross_profit / $s->total_amount) * 100
                                : 0;
                        @endphp
                        <span class="badge {{ $m >= 30 ? 'bg-success' : ($m >= 10 ? 'bg-warning text-dark' : 'bg-danger') }}">
                            {{ number_format($m, 1) }}%
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No sales for the selected period.
                    </td>
                </tr>
            @endforelse
            </tbody>
            @if($sales->count())
            <tfoot class="table-light">
                <tr>
                    <th colspan="5" class="text-end">Totals:</th>
                    <th class="text-success">₱{{ number_format($totalSales, 2) }}</th>
                    <th class="text-muted">₱{{ number_format($totalCost, 2) }}</th>
                    <th class="text-primary">₱{{ number_format($grossProfit, 2) }}</th>
                    <th></th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@endsection