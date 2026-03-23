@extends('layouts.app')
@section('title', $branch->name . ' — Consignment Ledger')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-1"><i class="bi bi-shop"></i> {{ $branch->name }}</h5>
        <small class="text-muted">Consignment Ledger</small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('consignment.branch-transfer.create', $branch) }}" class="btn btn-primary btn-sm btn-action">
            <i class="bi bi-arrow-left-right"></i> Transfer Products
        </a>
        <a href="{{ route('consignment.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

{{-- Summary --}}
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Total DRs</div>
                <div class="fw-bold fs-4">{{ $summary->total_drs ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Open</div>
                <div class="fw-bold fs-4 text-danger">{{ $summary->open_count ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Partial</div>
                <div class="fw-bold fs-4 text-warning">{{ $summary->partial_count ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Paid</div>
                <div class="fw-bold fs-4 text-success">{{ $summary->paid_count ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Total Value</div>
                <div class="fw-bold text-primary small">
                    ₱{{ number_format($summary->total_receivable ?? 0, 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-danger">
            <div class="card-body py-3">
                <div class="text-muted small">Outstanding</div>
                <div class="fw-bold text-danger">
                    ₱{{ number_format($summary->total_balance ?? 0, 2) }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DRs Table --}}
<div class="card">
    <div class="card-header">Delivery Receipts</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>DR #</th>
                    <th>Date</th>
                    <th>DR Value</th>
                    <th>Paid</th>
                    <th>Returned</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Sales</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($receivables as $r)
                <tr>
                    <td>
                        <span class="badge bg-dark font-monospace">
                            {{ $r->dr_number ?? '—' }}
                        </span>
                    </td>
                    <td>{{ $r->delivery_date->format('M d, Y') }}</td>
                    <td class="fw-semibold text-primary">
                        ₱{{ number_format($r->total_amount, 2) }}
                    </td>
                    <td class="text-success">
                        ₱{{ number_format($r->amount_paid, 2) }}
                    </td>
                    <td class="text-warning">
                        ₱{{ number_format($r->amount_returned, 2) }}
                    </td>
                    <td class="fw-bold {{ $r->balance > 0 ? 'text-danger' : 'text-success' }}">
                        ₱{{ number_format($r->balance, 2) }}
                    </td>
                    <td>
                        @php
                            $map = [
                                'open'    => 'bg-danger',
                                'partial' => 'bg-warning text-dark',
                                'paid'    => 'bg-success',
                            ];
                        @endphp
                        <span class="badge {{ $map[$r->status] }}">
                            {{ ucfirst($r->status) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary">
                            {{ $r->sales->count() }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('consignment.show', $r) }}"
                           class="btn btn-sm btn-outline-primary btn-action">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No deliveries yet.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $receivables->links() }}</div>

@endsection