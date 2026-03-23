@extends('layouts.app')
@section('title', 'Expenses')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Expenses</h5>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm btn-action">
        <i class="bi bi-plus-lg"></i> New Expense
    </a>
</div>

<form method="GET" action="{{ route('expenses.index') }}" class="d-flex gap-2 mb-4">
    <div class="input-group" style="max-width: 400px;">
        <span class="input-group-text bg-white">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" name="search" class="form-control border-start-0"
               placeholder="Search by title or category..."
               value="{{ request('search') }}">
        @if(request('search'))
            <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x"></i>
            </a>
        @endif
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Total Expenses</div>
                <div class="fw-bold fs-4 text-danger">₱{{ number_format($totalExpenses, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>Paid From</th>
                    <th>Amount</th>
                    <th>Notes</th>
                    <th>By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($expenses as $e)
                <tr>
                    <td class="text-muted small">{{ $e->id }}</td>
                    <td class="fw-semibold">{{ $e->title }}</td>
                    <td>
                        <span class="badge bg-secondary">{{ ucfirst($e->category) }}</span>
                    </td>
                    <td>{{ $e->expense_date->format('M d, Y') }}</td>
                    <td>
                        <span class="badge {{ $e->paid_from === 'cash' ? 'bg-warning text-dark' : 'bg-primary' }}">
                            {{ ucfirst($e->paid_from) }}
                        </span>
                        <span class="text-muted small">{{ $e->source_name }}</span>
                    </td>
                    <td class="fw-bold text-danger">₱{{ number_format($e->amount, 2) }}</td>
                    <td class="text-muted small">{{ $e->notes ?? '—' }}</td>
                    <td class="text-muted small">{{ $e->creator->name ?? '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('expenses.show', $e) }}" class="btn btn-sm btn-outline-primary btn-action">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No expenses yet.
                        <a href="{{ route('expenses.create') }}">Record one now</a>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex justify-content-between align-items-center">
    <div class="text-muted small">
        Showing {{ $expenses->firstItem() ?? 0 }}–{{ $expenses->lastItem() ?? 0 }}
        of {{ $expenses->total() }} results
    </div>
    {{ $expenses->links() }}
</div>

@endsection