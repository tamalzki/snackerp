@extends('layouts.app')
@section('title', 'Finished Products')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Finished Products</h5>
    <a href="{{ route('finished-products.create') }}" class="btn btn-primary btn-sm btn-action">
        <i class="bi bi-plus-lg"></i> Add Product
    </a>
</div>

{{-- Search + Type Filter --}}
<form method="GET" action="{{ route('finished-products.index') }}"
      class="d-flex gap-2 mb-4 align-items-center flex-wrap">
    <div class="input-group" style="max-width:360px">
        <span class="input-group-text bg-white">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" name="search" class="form-control border-start-0"
               placeholder="Search products..."
               value="{{ $search }}">
        @if($search)
            <a href="{{ route('finished-products.index', ['type' => $type]) }}"
               class="btn btn-outline-secondary">
                <i class="bi bi-x"></i>
            </a>
        @endif
    </div>

    <div class="btn-group">
        <a href="{{ route('finished-products.index', ['search' => $search]) }}"
           class="btn btn-sm {{ !$type ? 'btn-dark' : 'btn-outline-secondary' }}">
            All
        </a>
        <a href="{{ route('finished-products.index', ['search' => $search, 'type' => 'manufactured']) }}"
           class="btn btn-sm {{ $type === 'manufactured' ? 'btn-primary' : 'btn-outline-secondary' }}">
            <i class="bi bi-gear-wide-connected"></i> Manufactured
        </a>
        <a href="{{ route('finished-products.index', ['search' => $search, 'type' => 'resale']) }}"
           class="btn btn-sm {{ $type === 'resale' ? 'btn-success' : 'btn-outline-secondary' }}">
            <i class="bi bi-cart-check"></i> Resale
        </a>
    </div>

    <button type="submit" class="btn btn-primary btn-sm">Search</button>
</form>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Total Products</div>
                <div class="fw-bold fs-4">{{ $products->total() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Total Warehouse Stock</div>
                <div class="fw-bold fs-4 text-primary">
                    {{ qty_fmt($products->sum('current_stock')) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Warehouse Value</div>
                <div class="fw-bold fs-4 text-success">
                    ₱{{ number_format($products->sum(fn($p) => $p->current_stock * $p->average_cost), 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Low Stock Alerts</div>
                <div class="fw-bold fs-4 text-danger">
                    {{ $products->filter(fn($p) => $p->isLowStock())->count() }}
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
                    <th>Product Name</th>
                    <th>Type</th>
                    <th>Warehouse Stock</th>
                    <th>Average Cost</th>
                    <th>Stock Value</th>
                    <th>Selling Price</th>
                    <th>Margin</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($products as $p)
                <tr>
                    <td class="text-muted small">{{ $p->id }}</td>
                    <td class="fw-semibold">{{ $p->name }}</td>
                    <td>
                        @if($p->isManufactured())
                            <span class="badge bg-primary">
                                <i class="bi bi-gear-wide-connected"></i> Manufactured
                            </span>
                        @else
                            <span class="badge bg-success">
                                <i class="bi bi-cart-check"></i> Resale
                            </span>
                        @endif
                    </td>
                    <td>{{ qty_fmt($p->current_stock) }}</td>
                    <td>₱{{ number_format($p->average_cost, 4) }}</td>
                    <td>₱{{ number_format($p->current_stock * $p->average_cost, 2) }}</td>
                    <td>₱{{ number_format($p->selling_price, 2) }}</td>
                    <td>
                        @if($p->average_cost > 0 && $p->selling_price > 0)
                            @php $margin = (($p->selling_price - $p->average_cost) / $p->selling_price) * 100; @endphp
                            <span class="badge {{ $margin >= 30 ? 'bg-success' : ($margin >= 10 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ number_format($margin, 1) }}%
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($p->isLowStock())
                            <span class="badge bg-danger">
                                <i class="bi bi-exclamation-triangle"></i> Low Stock
                            </span>
                        @else
                            <span class="badge bg-success">OK</span>
                        @endif
                    </td>
                    <td>
                        @if($p->isManufactured())
                            <a href="{{ route('production.create', ['product_id' => $p->id]) }}"
                               class="btn btn-sm btn-outline-primary btn-action">
                                <i class="bi bi-gear-wide-connected"></i> Make Batch
                            </a>
                        @else
                            <a href="{{ route('finished-products.restock', $p) }}"
                               class="btn btn-sm btn-outline-success btn-action">
                                <i class="bi bi-plus-circle"></i> Restock
                            </a>
                        @endif
                        <a href="{{ route('finished-products.adjust', $p) }}"
                           class="btn btn-sm btn-outline-warning btn-action">
                            <i class="bi bi-sliders"></i> Adjust
                        </a>
                        <a href="{{ route('finished-products.show', $p) }}"
                           class="btn btn-sm btn-outline-secondary btn-action">
                            <i class="bi bi-eye"></i> View
                        </a>
                        <a href="{{ route('finished-products.edit', $p) }}"
                           class="btn btn-sm btn-outline-secondary btn-action">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form action="{{ route('finished-products.destroy', $p) }}"
                              method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this product?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger btn-action">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        @if($search)
                            No results for "<strong>{{ $search }}</strong>".
                            <a href="{{ route('finished-products.index') }}">Clear search</a>
                        @else
                            No finished products yet.
                            <a href="{{ route('finished-products.create') }}">Add one now</a>
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
        Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}
        of {{ $products->total() }} results
    </div>
    {{ $products->links() }}
</div>

@endsection