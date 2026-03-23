@extends('layouts.app')
@section('title', 'Cash Accounts')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Cash Accounts</h5>
    <a href="{{ route('cash-accounts.create') }}" class="btn btn-primary btn-sm btn-action">
        <i class="bi bi-plus-lg"></i> Add Cash Account
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Total Cash Balance</div>
                <div class="fw-bold fs-4 text-success">₱{{ number_format($total, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Total Accounts</div>
                <div class="fw-bold fs-4">{{ $accounts->count() }}</div>
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
                    <th>Account Name</th>
                    <th>Balance</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($accounts as $a)
                <tr>
                    <td class="text-muted small">{{ $a->id }}</td>
                    <td class="fw-semibold">{{ $a->name }}</td>
                    <td class="fw-bold {{ $a->balance > 0 ? 'text-success' : 'text-danger' }}">
                        ₱{{ number_format($a->balance, 2) }}
                    </td>
                    <td class="text-muted small">{{ $a->notes ?? '—' }}</td>
                    <td>
                        <a href="{{ route('deposits.create') }}"
                           class="btn btn-sm btn-outline-success btn-action">
                            <i class="bi bi-plus-circle"></i> Deposit
                        </a>
                        <a href="{{ route('cash-accounts.edit', $a) }}"
                           class="btn btn-sm btn-outline-secondary btn-action">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form action="{{ route('cash-accounts.destroy', $a) }}"
                              method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this account?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger btn-action">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        No cash accounts yet.
                        <a href="{{ route('cash-accounts.create') }}">Add one now</a>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection