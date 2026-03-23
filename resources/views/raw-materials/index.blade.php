@extends('layouts.app')
@section('title', 'Raw Materials')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Raw Materials</h5>
    <a href="{{ route('raw-materials.create') }}" class="btn btn-primary btn-sm btn-action">
        <i class="bi bi-plus-lg"></i> Add Material
    </a>
</div>

{{-- Search --}}
<x-search-bar action="{{ route('raw-materials.index') }}" placeholder="Search by name, unit, category..." />

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Total Materials</div>
                <div class="fw-bold fs-4">{{ $materials->total() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Ingredients</div>
                <div class="fw-bold fs-4 text-primary">
                    {{ $materials->getCollection()->where('category', 'ingredients')->count() }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Packaging</div>
                <div class="fw-bold fs-4 text-info">
                    {{ $materials->getCollection()->where('category', 'packaging')->count() }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Low Stock Alerts</div>
                <div class="fw-bold fs-4 text-danger">
                    {{ $materials->getCollection()->filter(fn($m) => $m->isLowStock())->count() }}
                </div>
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
                    <th>Name</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Stock</th>
                    <th>Cost / Unit</th>
                    <th>Stock Value</th>
                    <th>Low Stock At</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($materials as $m)
                <tr>
                    <td class="text-muted small">{{ $m->id }}</td>
                    <td class="fw-semibold">{{ $m->name }}</td>
                    <td>
                        @if($m->category === 'ingredients')
                            <span class="badge bg-primary">🧂 Ingredients</span>
                        @else
                            <span class="badge bg-info text-dark">📦 Packaging</span>
                        @endif
                    </td>
                    <td>{{ $m->unit }}</td>
                    <td>{{ qty_fmt($m->stock_quantity) }}</td>
                    <td>₱{{ number_format($m->cost_per_unit, 2) }}</td>
                    <td>₱{{ number_format($m->stock_quantity * $m->cost_per_unit, 2) }}</td>
                    <td>{{ qty_fmt($m->low_stock_threshold) }}</td>
                    <td>
                        @if($m->isLowStock())
                            <span class="badge bg-danger">
                                <i class="bi bi-exclamation-triangle"></i> Low Stock
                            </span>
                        @else
                            <span class="badge bg-success">OK</span>
                        @endif
                    </td>
                    <td>

                        <a href="{{ route('purchases.create', ['material_id' => $m->id]) }}"
                            class="btn btn-sm btn-outline-success btn-action">
                                <i class="bi bi-cart-plus"></i> Purchase
                            </a>
                        <a href="{{ route('raw-materials.edit', $m) }}"
                           class="btn btn-sm btn-outline-secondary btn-action">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form action="{{ route('raw-materials.destroy', $m) }}"
                              method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this material?')">
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
                    <td colspan="10" class="text-center text-muted py-4">
                        @if($search)
                            No results for "<strong>{{ $search }}</strong>".
                            <a href="{{ route('raw-materials.index') }}">Clear search</a>
                        @else
                            No raw materials yet.
                            <a href="{{ route('raw-materials.create') }}">Add one now</a>
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
        Showing {{ $materials->firstItem() ?? 0 }}–{{ $materials->lastItem() ?? 0 }}
        of {{ $materials->total() }} results
    </div>
    {{ $materials->links() }}
</div>

@endsection