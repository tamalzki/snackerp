@extends('layouts.app')
@section('title', 'Production Batches')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Production Batches</h5>
    <a href="{{ route('production.create') }}" class="btn btn-primary btn-sm btn-action">
        <i class="bi bi-plus-lg"></i> New Batch
    </a>
</div>

<x-search-bar action="{{ route('production.index') }}" placeholder="Search by product or creator..." />

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Date</th>
                    <th>Expected</th>
                    <th>Actual Output</th>
                    <th>Rejects</th>
                    <th>Reject Rate</th>
                    <th>Cost / Unit</th>
                    <th>Total Cost</th>
                    <th>Expiry</th>
                    <th>By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($batches as $b)
                <tr>
                    <td class="text-muted small">{{ $b->id }}</td>
                    <td class="fw-semibold">{{ $b->finishedProduct->name }}</td>
                    <td>{{ $b->production_date->format('M d, Y') }}</td>
                    <td>{{ qty_fmt($b->expected_output_qty) }}</td>
                    <td class="text-success fw-semibold">
                        {{ qty_fmt($b->actual_output_qty) }}
                    </td>
                    <td class="{{ $b->reject_qty > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                        {{ qty_fmt($b->reject_qty) }}
                    </td>
                    <td>
                        @php $rate = $b->reject_rate; @endphp
                        <span class="badge {{ $rate == 0 ? 'bg-success' : ($rate <= 5 ? 'bg-warning text-dark' : 'bg-danger') }}">
                            {{ $rate }}%
                        </span>
                    </td>
                    <td>₱{{ number_format($b->cost_per_unit, 4) }}</td>
                    <td>₱{{ number_format($b->total_raw_material_cost, 2) }}</td>
                    <td>
    @if($b->expiry_date)
        @if($b->isExpired())
            <span class="badge bg-danger">
                <i class="bi bi-x-circle"></i>
                Expired {{ $b->expiry_date->format('M d, Y') }}
            </span>
        @elseif($b->isExpiringSoon())
            <span class="badge bg-warning text-dark">
                <i class="bi bi-exclamation-triangle"></i>
                {{ $b->expiry_date->format('M d, Y') }}
            </span>
        @else
            <span class="badge bg-success">
                {{ $b->expiry_date->format('M d, Y') }}
            </span>
        @endif
    @else
        <span class="text-muted">—</span>
    @endif
</td>
                    <td class="text-muted small">{{ $b->creator->name ?? '—' }}</td>
                    <td>
                        <a href="{{ route('production.show', $b) }}"
                           class="btn btn-sm btn-outline-primary btn-action">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center text-muted py-4">
                        @if($search)
                            No results for "<strong>{{ $search }}</strong>".
                            <a href="{{ route('production.index') }}">Clear search</a>
                        @else
                            No production batches yet.
                            <a href="{{ route('production.create') }}">Create one now</a>
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
        Showing {{ $batches->firstItem() ?? 0 }}–{{ $batches->lastItem() ?? 0 }}
        of {{ $batches->total() }} results
    </div>
    {{ $batches->links() }}
</div>

@endsection