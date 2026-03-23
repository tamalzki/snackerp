@extends('layouts.app')
@section('title', $finishedProduct->name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">{{ $finishedProduct->name }}</h5>
        @if($finishedProduct->isManufactured())
            <span class="badge bg-primary">
                <i class="bi bi-gear-wide-connected"></i> Manufactured
            </span>
        @else
            <span class="badge bg-success">
                <i class="bi bi-cart-check"></i> Resale
            </span>
        @endif
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($finishedProduct->isResale())
            <a href="{{ route('finished-products.restock', $finishedProduct) }}"
               class="btn btn-success btn-sm btn-action">
                <i class="bi bi-plus-circle"></i> Restock
            </a>
        @else
            <a href="{{ route('production.create', ['product_id' => $finishedProduct->id]) }}"
               class="btn btn-primary btn-sm btn-action">
                <i class="bi bi-gear-wide-connected"></i> Make Batch
            </a>
        @endif
        <a href="{{ route('finished-products.adjust', $finishedProduct) }}"
           class="btn btn-warning btn-sm btn-action text-dark">
            <i class="bi bi-sliders"></i> Adjust
        </a>
        <a href="{{ route('finished-products.edit', $finishedProduct) }}"
           class="btn btn-outline-secondary btn-sm btn-action">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('finished-products.index') }}"
           class="btn btn-secondary btn-sm btn-action">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Warehouse Stock</div>
                <div class="fw-bold fs-4 text-primary">
                    {{ qty_fmt($finishedProduct->current_stock) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Average Cost</div>
                <div class="fw-bold fs-4">
                    ₱{{ number_format($finishedProduct->average_cost, 4) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Selling Price</div>
                <div class="fw-bold fs-4 text-success">
                    ₱{{ number_format($finishedProduct->selling_price, 2) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Stock Value</div>
                <div class="fw-bold fs-4">
                    ₱{{ number_format($finishedProduct->current_stock * $finishedProduct->average_cost, 2) }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- History --}}
@if($finishedProduct->isManufactured())
<div class="card">
    <div class="card-header">Production History</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Batch #</th>
                    <th>Date</th>
                    <th>Expected</th>
                    <th>Actual Output</th>
                    <th>Rejects</th>
                    <th>Cost/Unit</th>
                    <th>Total Cost</th>
                </tr>
            </thead>
            <tbody>
            @forelse($finishedProduct->productionBatches as $batch)
                <tr>
                    <td>#{{ $batch->id }}</td>
                    <td>{{ $batch->production_date->format('M d, Y') }}</td>
                    <td>{{ qty_fmt($batch->expected_output_qty) }}</td>
                    <td class="text-success fw-semibold">
                        {{ qty_fmt($batch->actual_output_qty) }}
                    </td>
                    <td class="text-danger">
                        {{ qty_fmt($batch->reject_qty) }}
                    </td>
                    <td>₱{{ number_format($batch->cost_per_unit, 4) }}</td>
                    <td>₱{{ number_format($batch->total_raw_material_cost, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No production batches yet.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Restock History</span>
        <a href="{{ route('finished-products.restock', $finishedProduct) }}"
           class="btn btn-sm btn-success btn-action">
            <i class="bi bi-plus-circle"></i> Add Restock
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Qty Added</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Supplier</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
            @forelse($finishedProduct->restocks as $r)
                <tr>
                    <td class="text-muted small">{{ $r->id }}</td>
                    <td>{{ $r->restock_date->format('M d, Y') }}</td>
                    <td class="text-success fw-semibold">
                        +{{ qty_fmt($r->quantity) }}
                    </td>
                    <td>₱{{ number_format($r->unit_cost, 4) }}</td>
                    <td>₱{{ number_format($r->total_cost, 2) }}</td>
                    <td class="text-muted">{{ $r->supplier ?? '—' }}</td>
                    <td class="text-muted small">{{ $r->creator->name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No restocks yet.
                        <a href="{{ route('finished-products.restock', $finishedProduct) }}">
                            Add first restock
                        </a>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@php
    $adjReasonLabels = [
        'physical_count' => 'Physical count',
        'damage' => 'Damage',
        'shrinkage' => 'Shrinkage',
        'found' => 'Found stock',
        'data_entry' => 'Data entry',
        'other' => 'Other',
    ];
@endphp

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-sliders"></i> Warehouse stock adjustments</span>
        <a href="{{ route('finished-products.adjust', $finishedProduct) }}"
           class="btn btn-sm btn-outline-warning btn-action text-dark">
            <i class="bi bi-plus-lg"></i> New adjustment
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>When</th>
                    <th class="text-end">Before</th>
                    <th class="text-end">After</th>
                    <th class="text-end">Change</th>
                    <th>Reason</th>
                    <th>Notes</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
            @forelse($stockAdjustments as $adj)
                <tr>
                    <td class="text-muted small">{{ $adj->created_at->format('M d, Y H:i') }}</td>
                    <td class="text-end">{{ qty_fmt($adj->quantity_before) }}</td>
                    <td class="text-end fw-semibold">{{ qty_fmt($adj->quantity_after) }}</td>
                    <td class="text-end {{ (float) $adj->difference >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ (float) $adj->difference >= 0 ? '+' : '' }}{{ qty_fmt($adj->difference) }}
                    </td>
                    <td>{{ $adjReasonLabels[$adj->reason] ?? $adj->reason }}</td>
                    <td class="text-muted small">{{ \Illuminate\Support\Str::limit($adj->notes ?? '—', 40) }}</td>
                    <td class="text-muted small">{{ $adj->creator->name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No adjustments yet.
                        <a href="{{ route('finished-products.adjust', $finishedProduct) }}">Record one</a>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection