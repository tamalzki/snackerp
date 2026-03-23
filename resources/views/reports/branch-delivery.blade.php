@extends('layouts.report')
@php $reportTitle = 'Branch Delivery'; @endphp
@section('report-content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Branch Delivery Report</h5>
</div>

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.branch-delivery') }}"
              class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Branch</label>
                <select name="branch_id" class="form-select" required>
                    <option value="">-- Select Branch --</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}"
                            {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">From</label>
                <input type="date" name="from" class="form-control" value="{{ $from }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">To</label>
                <input type="date" name="to" class="form-control" value="{{ $to }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Product (optional)</label>
                <select name="product_id" class="form-select">
                    <option value="">All Products</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}"
                            {{ request('product_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Generate
                </button>
            </div>
        </form>
    </div>
</div>

@if(isset($selectedBranch) && $selectedBranch)

<div class="alert alert-light border small mb-3">
    <i class="bi bi-calendar-range text-primary me-1"></i>
    <strong>Sold</strong> quantities count consignment sale lines only when the sale’s <strong>period</strong> overlaps
    this report’s dates (same idea as the Sales report).
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold mb-0">
        <i class="bi bi-shop"></i> {{ $selectedBranch->name }} —
        {{ \Carbon\Carbon::parse($from)->format('M d') }}
        to {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}
    </h6>
    <button onclick="window.print()"
            class="btn btn-sm btn-outline-secondary btn-action">
        <i class="bi bi-printer"></i> Print
    </button>
</div>

{{-- DR Summary for period --}}
@if($drSummary->count())
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-receipt"></i> Delivery Receipts in Period
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>DR #</th>
                    <th>Date</th>
                    <th class="text-end">DR Value</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Returned</th>
                    <th class="text-end">Balance</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
            @foreach($drSummary as $dr)
                <tr>
                    <td>
                        <span class="badge bg-dark font-monospace">
                            {{ $dr['dr_number'] }}
                        </span>
                    </td>
                    <td>{{ $dr['date'] }}</td>
                    <td class="text-end text-primary fw-semibold">
                        ₱{{ number_format($dr['total_amount'], 2) }}
                    </td>
                    <td class="text-end text-success">
                        ₱{{ number_format($dr['amount_paid'], 2) }}
                    </td>
                    <td class="text-end text-warning">
                        ₱{{ number_format($dr['returned'], 2) }}
                    </td>
                    <td class="text-end fw-bold {{ $dr['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                        ₱{{ number_format($dr['balance'], 2) }}
                    </td>
                    <td class="text-center">
                        @php
                            $map = [
                                'open'    => 'bg-danger',
                                'partial' => 'bg-warning text-dark',
                                'paid'    => 'bg-success',
                            ];
                        @endphp
                        <span class="badge {{ $map[$dr['status']] }}">
                            {{ ucfirst($dr['status']) }}
                        </span>
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="2" class="text-end">Totals:</th>
                    <th class="text-end text-primary">
                        ₱{{ number_format($drSummary->sum('total_amount'), 2) }}
                    </th>
                    <th class="text-end text-success">
                        ₱{{ number_format($drSummary->sum('amount_paid'), 2) }}
                    </th>
                    <th class="text-end text-warning">
                        ₱{{ number_format($drSummary->sum('returned'), 2) }}
                    </th>
                    <th class="text-end text-danger">
                        ₱{{ number_format($drSummary->sum('balance'), 2) }}
                    </th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- Per Product Summary --}}
<div class="card">
    <div class="card-header">
        <i class="bi bi-box-seam"></i> Product Movement Summary
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0">
            <thead style="background:#004d3b;">
                <tr>
                    <th rowspan="2" style="padding:10px 14px; color:#fff; border:none; width:20%;">Product</th>
                    <th colspan="2" style="padding:10px 14px; color:#fff; border:none; text-align:center; background:#1a6b53;">Delivered</th>
                    <th colspan="2" style="padding:10px 14px; color:#fff; border:none; text-align:center; background:#92400e;">Pull Out</th>
                    <th colspan="2" style="padding:10px 14px; color:#fff; border:none; text-align:center; background:#065f46;">Sold</th>
                    <th colspan="2" style="padding:10px 14px; color:#fff; border:none; text-align:center; background:#1e3a5f;">Ending Balance</th>
                </tr>
                <tr>
                    <th style="padding:8px 14px; color:#fff; border:none; background:#1a6b53; text-align:center;">Packs</th>
                    <th style="padding:8px 14px; color:#fff; border:none; background:#1a6b53; text-align:right;">Amount</th>
                    <th style="padding:8px 14px; color:#fff; border:none; background:#92400e; text-align:center;">Packs</th>
                    <th style="padding:8px 14px; color:#fff; border:none; background:#92400e; text-align:right;">Amount</th>
                    <th style="padding:8px 14px; color:#fff; border:none; background:#065f46; text-align:center;">Packs</th>
                    <th style="padding:8px 14px; color:#fff; border:none; background:#065f46; text-align:right;">Amount</th>
                    <th style="padding:8px 14px; color:#fff; border:none; background:#1e3a5f; text-align:center;">Packs</th>
                    <th style="padding:8px 14px; color:#fff; border:none; background:#1e3a5f; text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="fw-semibold">{{ $row['product'] }}</td>
                    <td class="text-center">{{ qty_fmt($row['delivered_qty']) }}</td>
                    <td class="text-end text-primary fw-semibold">
                        ₱{{ number_format($row['delivered_amt'], 2) }}
                    </td>
                    <td class="text-center {{ $row['pullout_qty'] > 0 ? 'text-warning fw-semibold' : 'text-muted' }}">
                        {{ $row['pullout_qty'] > 0 ? qty_fmt($row['pullout_qty']) : '—' }}
                    </td>
                    <td class="text-end {{ $row['pullout_qty'] > 0 ? 'text-warning' : 'text-muted' }}">
                        {{ $row['pullout_qty'] > 0 ? '₱'.number_format($row['pullout_amt'], 2) : '—' }}
                    </td>
                    <td class="text-center text-success fw-semibold">
                        {{ qty_fmt($row['sold_qty']) }}
                    </td>
                    <td class="text-end text-success fw-semibold">
                        ₱{{ number_format($row['sold_amt'], 2) }}
                    </td>
                    <td class="text-center fw-bold {{ $row['ending_qty'] > 0 ? 'text-primary' : 'text-muted' }}">
                        {{ qty_fmt($row['ending_qty']) }}
                    </td>
                    <td class="text-end fw-bold text-primary">
                        ₱{{ number_format($row['ending_amt'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No activity for this branch in the selected period.
                    </td>
                </tr>
            @endforelse
            </tbody>
            @if($rows->count())
            <tfoot style="background:#004d3b;">
                <tr>
                    <th style="padding:10px 14px; color:#fff; border:none;">TOTAL</th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:center;">
                        {{ qty_fmt($rows->sum('delivered_qty')) }}
                    </th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right;">
                        ₱{{ number_format($rows->sum('delivered_amt'), 2) }}
                    </th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:center;">
                        {{ qty_fmt($rows->sum('pullout_qty')) }}
                    </th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right;">
                        ₱{{ number_format($rows->sum('pullout_amt'), 2) }}
                    </th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:center;">
                        {{ qty_fmt($rows->sum('sold_qty')) }}
                    </th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right;">
                        ₱{{ number_format($rows->sum('sold_amt'), 2) }}
                    </th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:center;">
                        {{ qty_fmt($rows->sum('ending_qty')) }}
                    </th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right;">
                        ₱{{ number_format($rows->sum('ending_amt'), 2) }}
                    </th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@endif

@endsection