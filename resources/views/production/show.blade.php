@extends('layouts.app')
@section('title', 'Production Batch #' . $production->id)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Production Batch #{{ $production->id }}</h5>
    <a href="{{ route('production.index') }}" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Product</div>
                <div class="fw-bold small">{{ $production->finishedProduct->name }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Expected</div>
                <div class="fw-bold">{{ qty_fmt($production->expected_output_qty) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Actual Output</div>
                <div class="fw-bold text-success">
                    {{ qty_fmt($production->actual_output_qty) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Rejects</div>
                <div class="fw-bold text-danger">
                    {{ qty_fmt($production->reject_qty) }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-2">
    <div class="card text-center">
        <div class="card-body py-3">
            <div class="text-muted small">Expiry Date</div>
            <div class="fw-bold">
                @if($production->expiry_date)
                    @if($production->isExpired())
                        <span class="text-danger">
                            {{ $production->expiry_date->format('M d, Y') }}
                            <br><small>EXPIRED</small>
                        </span>
                    @elseif($production->isExpiringSoon())
                        <span class="text-warning">
                            {{ $production->expiry_date->format('M d, Y') }}
                            <br><small>Expiring Soon</small>
                        </span>
                    @else
                        <span class="text-success">
                            {{ $production->expiry_date->format('M d, Y') }}
                        </span>
                    @endif
                @else
                    <span class="text-muted">—</span>
                @endif
            </div>
        </div>
    </div>
</div>

    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Reject Rate</div>
                <div class="fw-bold">{{ $production->reject_rate }}%</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Cost / Unit</div>
                <div class="fw-bold text-primary">
                    ₱{{ number_format($production->cost_per_unit, 4) }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Raw Materials Used --}}
<div class="card">
    <div class="card-header">
        Raw Materials Used
        <span class="float-end text-muted small">
            Total Cost: <strong>₱{{ number_format($production->total_raw_material_cost, 2) }}</strong>
        </span>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Qty Used</th>
                    <th>Cost Snapshot</th>
                    <th class="text-end">Line Cost</th>
                </tr>
            </thead>
            <tbody>
            @foreach($production->items as $item)
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
                    <td>{{ qty_fmt($item->quantity_used) }}</td>
                    <td>₱{{ number_format($item->cost_snapshot, 4) }}</td>
                    <td class="text-end fw-semibold">
                        ₱{{ number_format($item->total_cost, 2) }}
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr class="table-dark">
                    <th colspan="5" class="text-end">Total Raw Material Cost</th>
                    <th class="text-end">
                        ₱{{ number_format($production->total_raw_material_cost, 2) }}
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection