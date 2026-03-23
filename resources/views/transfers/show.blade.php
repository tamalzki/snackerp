@extends('layouts.app')
@section('title', ($transfer->dr_number ?? 'Delivery #' . $transfer->id))
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">
            Delivery Receipt
            @if($transfer->dr_number)
                <span class="badge bg-dark font-monospace ms-1">{{ $transfer->dr_number }}</span>
            @else
                <span class="text-muted">#{{ $transfer->id }}</span>
            @endif
        </h5>
        <div class="text-muted small">
            @if($transfer->source_branch_id)
                <i class="bi bi-shop"></i>
                <strong>{{ $transfer->sourceBranch->name ?? '—' }}</strong>
                <i class="bi bi-arrow-right"></i>
                <strong>{{ $transfer->branch->name }}</strong>
                <span class="badge bg-info text-dark ms-1">Branch → Branch</span>
            @else
                <i class="bi bi-building"></i> Warehouse →
                <strong>{{ $transfer->branch->name }}</strong>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()"
                class="btn btn-outline-secondary btn-sm btn-action">
            <i class="bi bi-printer"></i> Print
        </button>
        <a href="{{ route('transfers.index') }}"
           class="btn btn-secondary btn-sm btn-action">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">DR Number</div>
                <div class="fw-bold font-monospace fs-5">
                    {{ $transfer->dr_number ?? '—' }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Delivered To</div>
                <div class="fw-bold">{{ $transfer->branch->name }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Delivery Date</div>
                <div class="fw-bold">{{ $transfer->transfer_date->format('M d, Y') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Recorded By</div>
                <div class="fw-bold">{{ $transfer->creator->name ?? '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Notes</div>
                <div class="fw-bold">{{ $transfer->notes ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Delivered Products</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Cost Snapshot</th>
                    <th class="text-end">Line Value</th>
                </tr>
            </thead>
            <tbody>
            @foreach($transfer->items as $i => $item)
                <tr>
                    <td class="text-muted small">{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $item->finishedProduct->name }}</td>
                    <td>{{ qty_fmt($item->quantity) }}</td>
                    <td>₱{{ number_format($item->cost_snapshot, 4) }}</td>
                    <td class="text-end">
                        ₱{{ number_format($item->quantity * $item->cost_snapshot, 2) }}
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr class="table-dark">
                    <th colspan="4" class="text-end">Total Delivery Value</th>
                    <th class="text-end">
                        ₱{{ number_format($transfer->items->sum(fn($i) => $i->quantity * $i->cost_snapshot), 2) }}
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection