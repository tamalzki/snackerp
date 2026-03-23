@extends('layouts.app')
@section('title', 'Deposit #' . $deposit->id)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Deposit #{{ $deposit->id }}</h5>
    <a href="{{ route('deposits.index') }}" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Deposit Date</div>
                <div class="fw-bold">{{ $deposit->deposit_date->format('F d, Y') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Source</div>
                <div class="fw-bold">{{ $deposit->source_name }}</div>
                <span class="badge {{ $deposit->source_type === 'cash' ? 'bg-warning text-dark' : 'bg-primary' }} mt-1">
                    {{ ucfirst($deposit->source_type) }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Amount</div>
                <div class="fw-bold fs-4 text-success">₱{{ number_format($deposit->amount, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Details</div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3 text-muted">Reference</dt>
            <dd class="col-sm-9">{{ $deposit->reference ?? '—' }}</dd>
            <dt class="col-sm-3 text-muted">Notes</dt>
            <dd class="col-sm-9">{{ $deposit->notes ?? '—' }}</dd>
            <dt class="col-sm-3 text-muted">Recorded By</dt>
            <dd class="col-sm-9">{{ $deposit->creator->name ?? '—' }}</dd>
        </dl>
    </div>
</div>

@endsection
