@extends('layouts.app')
@section('title', 'Sale #' . $sale->id)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Sale #{{ $sale->id }}</h5>
    <a href="{{ route('sales.index') }}" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Branch</div>
                <div class="fw-bold">{{ $sale->branch->name }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Sale Date</div>
                <div class="fw-bold">{{ $sale->sale_date->format('M d, Y') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Sales</div>
                <div class="fw-bold text-success">₱{{ number_format($sale->total_amount, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Total Cost</div>
                <div class="fw-bold">₱{{ number_format($sale->total_cost, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Gross Profit</div>
                <div class="fw-bold text-primary">₱{{ number_format($sale->gross_profit, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-1">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Margin</div>
                <div class="fw-bold">
                    @php $margin = $sale->margin; @endphp
                    <span class="badge {{ $margin >= 30 ? 'bg-success' : ($margin >= 10 ? 'bg-warning text-dark' : 'bg-danger') }}">
                        {{ number_format($margin, 1) }}%
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Items Sold</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Cost Snapshot</th>
                    <th>Line Profit</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td class="fw-semibold">{{ $item->finishedProduct->name }}</td>
                    <td>{{ qty_fmt($item->quantity) }}</td>
                    <td>₱{{ number_format($item->unit_price, 2) }}</td>
                    <td>₱{{ number_format($item->cost_snapshot, 4) }}</td>
                    <td class="{{ $item->line_profit >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                        ₱{{ number_format($item->line_profit, 2) }}
                    </td>
                    <td class="text-end fw-semibold">
                        ₱{{ number_format($item->total_price, 2) }}
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr class="table-dark">
                    <th colspan="4" class="text-end">Totals</th>
                    <th class="text-success">₱{{ number_format($sale->gross_profit, 2) }}</th>
                    <th class="text-end">₱{{ number_format($sale->total_amount, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@if($sale->notes)
<div class="mt-3 text-muted small">
    <i class="bi bi-chat-left-text"></i> {{ $sale->notes }}
</div>
@endif

@endsection