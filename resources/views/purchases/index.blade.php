@extends('layouts.app')
@section('title', 'Raw Material Purchases')
@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Raw Material Purchases</h5>
    <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm btn-action">
        <i class="bi bi-plus-lg"></i> New Purchase
    </a>
</div>

{{-- Search bar on its own row --}}
<form method="GET" action="{{ route('purchases.index') }}" class="d-flex gap-2 mb-4">
    <div class="input-group" style="max-width: 400px;">
        <span class="input-group-text bg-white">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text"
               name="search"
               class="form-control border-start-0"
               placeholder="Search by supplier name..."
               value="{{ request('search') }}">
        @if(request('search'))
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">
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
                    <th>Supplier</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total Cost</th>
                    <th>Recorded By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($purchases as $p)
                <tr>
                    <td class="text-muted small">{{ $p->id }}</td>
                    <td class="fw-semibold">{{ $p->supplier_name }}</td>
                    <td>{{ $p->purchase_date->format('M d, Y') }}</td>
                    <td>
                        <span class="badge bg-secondary">
                            {{ $p->items_count ?? $p->items->count() }} items
                        </span>
                    </td>
                    <td class="fw-semibold text-success">
                        ₱{{ number_format($p->total_cost, 2) }}
                    </td>
                    <td>{{ $p->creator->name ?? '—' }}</td>
                    <td>
                        <a href="{{ route('purchases.show', $p) }}"
                           class="btn btn-sm btn-outline-primary btn-action">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        @if(request('search'))
                            No results for "<strong>{{ request('search') }}</strong>".
                            <a href="{{ route('purchases.index') }}">Clear search</a>
                        @else
                            No purchases yet.
                            <a href="{{ route('purchases.create') }}">Record one now</a>
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex justify-content-between align-items-center">
    <div class="text-muted small">
        Showing {{ $purchases->firstItem() ?? 0 }}–{{ $purchases->lastItem() ?? 0 }}
        of {{ $purchases->total() }} results
    </div>
    {{ $purchases->links() }}
</div>

@endsection
