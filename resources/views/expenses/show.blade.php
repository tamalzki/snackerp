@extends('layouts.app')
@section('title', $expense->title)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">{{ $expense->title }}</h5>
    <a href="{{ route('expenses.index') }}" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Expense Date</div>
                <div class="fw-bold">{{ $expense->expense_date->format('F d, Y') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Category</div>
                <div class="fw-bold">
                    <span class="badge bg-secondary">{{ ucfirst($expense->category) }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Amount</div>
                <div class="fw-bold fs-4 text-danger">₱{{ number_format($expense->amount, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Details</div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3 text-muted">Paid From</dt>
            <dd class="col-sm-9">
                <span class="badge {{ $expense->paid_from === 'cash' ? 'bg-warning text-dark' : 'bg-primary' }}">
                    {{ ucfirst($expense->paid_from) }}
                </span>
                <span class="ms-2">{{ $expense->source_name }}</span>
            </dd>
            <dt class="col-sm-3 text-muted">Notes</dt>
            <dd class="col-sm-9">{{ $expense->notes ?? '—' }}</dd>
            <dt class="col-sm-3 text-muted">Recorded By</dt>
            <dd class="col-sm-9">{{ $expense->creator->name ?? '—' }}</dd>
        </dl>
    </div>
</div>

@endsection
