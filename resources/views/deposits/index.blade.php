@extends('layouts.app')
@section('title', 'Deposits')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Deposits</h5>
    <a href="{{ route('deposits.create') }}" class="btn btn-primary btn-sm btn-action">
        <i class="bi bi-plus-lg"></i> New Deposit
    </a>
</div>

<form method="GET" action="{{ route('deposits.index') }}" class="d-flex gap-2 mb-4">
    <div class="input-group" style="max-width: 400px;">
        <span class="input-group-text bg-white">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" name="search" class="form-control border-start-0"
               placeholder="Search by reference or notes..."
               value="{{ request('search') }}">
        @if(request('search'))
            <a href="{{ route('deposits.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x"></i>
            </a>
        @endif
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Source</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Reference</th>
                    <th>Notes</th>
                    <th>By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($deposits as $d)
                <tr>
                    <td class="text-muted small">{{ $d->id }}</td>
                    <td>{{ $d->deposit_date->format('M d, Y') }}</td>
                    <td class="fw-semibold">{{ $d->source_name }}</td>
                    <td>
                        <span class="badge {{ $d->source_type === 'cash' ? 'bg-warning text-dark' : 'bg-primary' }}">
                            {{ ucfirst($d->source_type) }}
                        </span>
                    </td>
                    <td class="fw-bold text-success">₱{{ number_format($d->amount, 2) }}</td>
                    <td class="text-muted small">{{ $d->reference ?? '—' }}</td>
                    <td class="text-muted small">{{ $d->notes ?? '—' }}</td>
                    <td class="text-muted small">{{ $d->creator->name ?? '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('deposits.show', $d) }}" class="btn btn-sm btn-outline-primary btn-action">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No deposits yet.
                        <a href="{{ route('deposits.create') }}">Record one now</a>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex justify-content-between align-items-center">
    <div class="text-muted small">
        Showing {{ $deposits->firstItem() ?? 0 }}–{{ $deposits->lastItem() ?? 0 }}
        of {{ $deposits->total() }} results
    </div>
    {{ $deposits->links() }}
</div>

@endsection