@extends('layouts.app')
@section('title', 'Purchase #' . $purchase->id)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Purchase #{{ $purchase->id }}</h5>
    <a href="{{ route('purchases.index') }}" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

{{-- Purchase Header Info --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Supplier</div>
                <div class="fw-bold">{{ $purchase->supplier_name }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Purchase Date</div>
                <div class="fw-bold">{{ $purchase->purchase_date->format('F d, Y') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Recorded By</div>
                <div class="fw-bold">{{ $purchase->creator->name ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Items --}}
<div class="card">
    <div class="card-header">Purchase Items</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Quantity</th>
                    <th>Cost / Unit</th>
                    <th class="text-end">Line Total</th>
                </tr>
            </thead>
            <tbody>
            @foreach($purchase->items as $item)
                <tr>
                    <td class="fw-semibold">{{ $item->rawMaterial->name }}</td>
                    <td>
                        @if($item->rawMaterial->category === 'ingredients')
                            <span class="badge bg-primary">🧂 Ingredients</span>
                        @else
                            <span class="badge bg-info text-dark">📦 Packaging</span>
                        @endif
                    </td>
                    <td>{{ $item->rawMaterial->unit }}</td>
                    <td>{{ qty_fmt($item->quantity) }}</td>
                    <td>₱{{ number_format($item->cost_per_unit, 4) }}</td>
                    <td class="text-end fw-semibold">
                        ₱{{ number_format($item->total_cost, 2) }}
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr class="table-dark">
                    <th colspan="5" class="text-end">Grand Total</th>
                    <th class="text-end">₱{{ number_format($purchase->total_cost, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection