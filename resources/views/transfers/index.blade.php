@extends('layouts.app')
@section('title', 'Deliveries')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Deliveries</h5>
    <a href="{{ route('transfers.create') }}" class="btn btn-primary btn-sm btn-action">
        <i class="bi bi-plus-lg"></i> New Delivery
    </a>
</div>

<form method="GET" action="{{ route('transfers.index') }}" class="d-flex gap-2 mb-4">
    <div class="input-group" style="max-width: 440px;">
        <span class="input-group-text bg-white">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" name="search" class="form-control border-start-0"
               placeholder="Search by DR#, branch or notes..."
               value="{{ request('search') }}">
        @if(request('search'))
            <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary">
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
                    <th>DR #</th>
                    <th>Branch</th>
                    <th>Date</th>
                    <th>Products</th>
                    <th>Total Qty</th>
                    <th>Delivery Value</th>
                    <th>Notes</th>
                    <th>By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($transfers as $t)
                <tr>
                    <td>
                        <span class="badge bg-dark font-monospace">
                            {{ $t->dr_number ?? '—' }}
                        </span>
                    </td>
                    <td>
                        @if($t->source_branch_id)
                            <span class="fw-semibold">{{ $t->sourceBranch->name ?? '—' }}</span>
                            <i class="bi bi-arrow-right text-muted"></i>
                            <span class="fw-semibold">{{ $t->branch->name }}</span>
                            <span class="badge bg-info text-dark ms-1" style="font-size:0.65rem;">B→B</span>
                        @else
                            <span class="text-muted small">Warehouse</span>
                            <i class="bi bi-arrow-right text-muted"></i>
                            <span class="fw-semibold">{{ $t->branch->name }}</span>
                        @endif
                    </td>
                    <td>{{ $t->transfer_date->format('M d, Y') }}</td>
                    <td>
                        <span class="badge bg-secondary">
                            {{ $t->items->count() }} products
                        </span>
                    </td>
                    <td>{{ qty_fmt($t->items->sum('quantity')) }}</td>
                    <td class="fw-semibold text-success">
                        ₱{{ number_format($t->items->sum(fn($i) => $i->quantity * $i->cost_snapshot), 2) }}
                    </td>
                    <td class="text-muted small">{{ $t->notes ?? '—' }}</td>
                    <td class="text-muted small">{{ $t->creator->name ?? '—' }}</td>
                    <td>
                        <a href="{{ route('transfers.show', $t) }}"
                           class="btn btn-sm btn-outline-primary btn-action">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        @if(request('search'))
                            No results for "<strong>{{ request('search') }}</strong>".
                            <a href="{{ route('transfers.index') }}">Clear search</a>
                        @else
                            No deliveries yet.
                            <a href="{{ route('transfers.create') }}">Create one now</a>
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
        Showing {{ $transfers->firstItem() ?? 0 }}–{{ $transfers->lastItem() ?? 0 }}
        of {{ $transfers->total() }} results
    </div>
    {{ $transfers->links() }}
</div>

@endsection