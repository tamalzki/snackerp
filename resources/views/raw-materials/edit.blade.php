@extends('layouts.app')
@section('title', 'Edit Raw Material')
@section('content')

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil"></i> Edit: {{ $rawMaterial->name }}
            </div>
            <div class="card-body">
                <form action="{{ route('raw-materials.update', $rawMaterial) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $rawMaterial->name) }}">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category" class="form-select">
                            <option value="ingredients"
                                {{ old('category', $rawMaterial->category) == 'ingredients' ? 'selected' : '' }}>
                                🧂 Ingredients
                            </option>
                            <option value="packaging"
                                {{ old('category', $rawMaterial->category) == 'packaging' ? 'selected' : '' }}>
                                📦 Packaging
                            </option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Unit</label>
                        <select name="unit" class="form-select">
                            @foreach(['kg', 'grams', 'liters', 'pcs'] as $unit)
                                <option value="{{ $unit }}"
                                    {{ old('unit', $rawMaterial->unit) == $unit ? 'selected' : '' }}>
                                    {{ $unit }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cost per Unit (₱)</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="cost_per_unit"
                                   class="form-control @error('cost_per_unit') is-invalid @enderror"
                                   step="0.0001" min="0"
                                   value="{{ old('cost_per_unit', $rawMaterial->cost_per_unit) }}">
                        </div>
                        @error('cost_per_unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Stock</label>
                        <div class="input-group">
                            <input type="text"
                                   class="form-control bg-light"
                                   value="{{ qty_fmt($rawMaterial->stock_quantity) }}"
                                   disabled>
                            <span class="input-group-text">{{ $rawMaterial->unit }}</span>
                        </div>
                        <div class="form-text">
                            Stock is managed through purchases and production only.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Low Stock Alert Threshold</label>
                        <div class="input-group">
                            <input type="number" name="low_stock_threshold"
                                   class="form-control"
                                   step="0.0001" min="0"
                                   value="{{ old('low_stock_threshold', $rawMaterial->low_stock_threshold) }}">
                            <span class="input-group-text">{{ $rawMaterial->unit }}</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Update
                        </button>
                        <a href="{{ route('raw-materials.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection