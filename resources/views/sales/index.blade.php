@extends('layouts.app')
@section('title', 'Sales')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Sales</h5>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('consignment.index') }}" class="btn btn-outline-secondary btn-sm btn-action">
            <i class="bi bi-arrow-left-right"></i> Branch → branch transfers
        </a>
        <a href="{{ route('sales.create') }}" class="btn btn-primary btn-sm btn-action">
            <i class="bi bi-plus-lg"></i> New Sale
        </a>
    </div>
</div>

<div class="alert alert-light border small mb-4 py-2">
    <i class="bi bi-info-circle text-primary"></i>
    To move stock between branches with a <strong>new DR</strong>, open <strong>Consignment</strong> → your branch ledger → <strong>Transfer Products</strong>.
</div>

<form method="GET" action="{{ route('sales.index') }}" class="d-flex gap-2 mb-4">
    <div class="input-group" style="max-width: 400px;">
        <span class="input-group-text bg-white">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" name="search" class="form-control border-start-0"
               placeholder="Search by branch or notes..."
               value="{{ request('search') }}">
        @if(request('search'))
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
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
                    <th>Branch</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total Sales</th>
                    <th>Total Cost</th>
                    <th>Gross Profit</th>
                    <th>Margin</th>
                    <th>By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($sales as $s)
                <tr>
                    <td class="text-muted small">{{ $s->id }}</td>
                    <td class="fw-semibold">{{ $s->branch->name }}</td>
                    <td>{{ $s->sale_date->format('M d, Y') }}</td>
                    <td>
                        <span class="badge bg-secondary">
                            {{ $s->items->count() }} items
                        </span>
                    </td>
                    <td class="fw-semibold text-success">
                        ₱{{ number_format($s->total_amount, 2) }}
                    </td>
                    <td class="text-muted">
                        ₱{{ number_format($s->total_cost, 2) }}
                    </td>
                    <td class="fw-semibold {{ $s->gross_profit >= 0 ? 'text-success' : 'text-danger' }}">
                        ₱{{ number_format($s->gross_profit, 2) }}
                    </td>
                    <td>
                        @php $margin = $s->margin; @endphp
                        <span class="badge {{ $margin >= 30 ? 'bg-success' : ($margin >= 10 ? 'bg-warning text-dark' : 'bg-danger') }}">
                            {{ number_format($margin, 1) }}%
                        </span>
                    </td>
                    <td class="text-muted small">{{ $s->creator->name ?? '—' }}</td>
                    <td>
                        <a href="{{ route('sales.show', $s) }}"
                           class="btn btn-sm btn-outline-primary btn-action">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        @if($search)
                            No results for "<strong>{{ $search }}</strong>".
                            <a href="{{ route('sales.index') }}">Clear search</a>
                        @else
                            No sales yet.
                            <a href="{{ route('sales.create') }}">Record one now</a>
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
        Showing {{ $sales->firstItem() ?? 0 }}–{{ $sales->lastItem() ?? 0 }}
        of {{ $sales->total() }} results
    </div>
    {{ $sales->links() }}
</div>

@endsection