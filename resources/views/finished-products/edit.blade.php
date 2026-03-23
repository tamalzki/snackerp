@extends('layouts.app')
@section('title', 'Edit Finished Product')
@section('content')

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil"></i> Edit: {{ $finishedProduct->name }}
            </div>
            <div class="card-body">
                <form action="{{ route('finished-products.update', $finishedProduct) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Product Name</label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $finishedProduct->name) }}">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Selling Price (₱)</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="selling_price"
                                   class="form-control @error('selling_price') is-invalid @enderror"
                                   step="0.01" min="0"
                                   value="{{ old('selling_price', $finishedProduct->selling_price) }}">
                        </div>
                        @error('selling_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Warehouse Stock</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-light"
                                   value="{{ qty_fmt($finishedProduct->current_stock) }}"
                                   disabled>
                            <span class="input-group-text">pcs</span>
                        </div>
                        <div class="form-text">Managed through production and transfers only.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Average Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="text" class="form-control bg-light"
                                   value="{{ number_format($finishedProduct->average_cost, 4) }}"
                                   disabled>
                        </div>
                        <div class="form-text">Auto-calculated from production batches.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Low Stock Alert Threshold</label>
                        <div class="input-group">
                            <input type="number" name="low_stock_threshold"
                                   class="form-control"
                                   step="0.0001" min="0"
                                   value="{{ old('low_stock_threshold', $finishedProduct->low_stock_threshold) }}">
                            <span class="input-group-text">pcs</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Update
                        </button>
                        <a href="{{ route('finished-products.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection