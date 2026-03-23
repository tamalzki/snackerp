@extends('layouts.app')
@section('title', $branch->name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">
        <i class="bi bi-shop"></i> {{ $branch->name }}
        @if($branch->is_active)
            <span class="badge bg-success ms-2 fs-6">Active</span>
        @else
            <span class="badge bg-secondary ms-2 fs-6">Inactive</span>
        @endif
    </h5>
    <div class="d-flex gap-2">
    <a href="{{ route('sales.create', ['branch_id' => $branch->id]) }}"
       class="btn btn-success btn-sm btn-action">
        <i class="bi bi-receipt"></i> Record Sale
    </a>
    <a href="{{ route('transfers.create', ['branch_id' => $branch->id]) }}"
       class="btn btn-primary btn-sm btn-action">
        <i class="bi bi-arrow-left-right"></i> Transfer Stock
    </a>
    <a href="{{ route('branches.edit', $branch) }}"
       class="btn btn-outline-secondary btn-sm btn-action">
        <i class="bi bi-pencil"></i> Edit
    </a>
    <a href="{{ route('branches.index') }}"
       class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>
</div>

@if($branch->address)
<p class="text-muted mb-4">
    <i class="bi bi-geo-alt"></i> {{ $branch->address }}
</p>
@endif

{{-- Inventory --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-boxes"></i> Branch Inventory</span>
        <span class="badge bg-secondary">
            {{ $branch->inventory->count() }} products
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Stock in Branch</th>
                    <th>Cost Snapshot</th>
                    <th>Stock Value</th>
                    <th>Selling Price</th>
                </tr>
            </thead>
            <tbody>
            @forelse($branch->inventory as $inv)
                <tr>
                    <td class="fw-semibold">{{ $inv->finishedProduct->name }}</td>
                    <td>
                        <span class="fw-bold {{ $inv->stock_quantity <= 0 ? 'text-danger' : 'text-success' }}">
                            {{ qty_fmt($inv->stock_quantity) }}
                        </span>
                    </td>
                    <td>₱{{ number_format($inv->cost_snapshot, 4) }}</td>
                    <td>₱{{ number_format($inv->stock_quantity * $inv->cost_snapshot, 2) }}</td>
                    <td>₱{{ number_format($inv->finishedProduct->selling_price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        No stock in this branch yet.
                        <a href="{{ route('transfers.create', ['branch_id' => $branch->id]) }}">
                            Transfer stock now
                        </a>
                    </td>
                </tr>
            @endforelse
            </tbody>
            @if($branch->inventory->count())
            <tfoot>
                <tr class="table-dark">
                    <th colspan="3" class="text-end">Total Branch Value</th>
                    <th>
                        ₱{{ number_format($branch->inventory->sum(fn($i) => $i->stock_quantity * $i->cost_snapshot), 2) }}
                    </th>
                    <th></th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@endsection