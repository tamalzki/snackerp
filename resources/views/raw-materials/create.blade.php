@extends('layouts.app')
@section('title', 'Add Raw Material')
@section('content')

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Add Raw Material
            </div>
            <div class="card-body">
                <form action="{{ route('raw-materials.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               placeholder="e.g. Sugar, Flour, Plastic Bag">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category"
                                class="form-select @error('category') is-invalid @enderror">
                            <option value="">-- Select Category --</option>
                            <option value="ingredients"
                                {{ old('category') == 'ingredients' ? 'selected' : '' }}>
                                🧂 Ingredients
                            </option>
                            <option value="packaging"
                                {{ old('category') == 'packaging' ? 'selected' : '' }}>
                                📦 Packaging
                            </option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Unit</label>
                        <select name="unit"
                                class="form-select @error('unit') is-invalid @enderror">
                            <option value="">-- Select Unit --</option>
                            @foreach(['kg', 'grams', 'liters', 'pcs'] as $unit)
                                <option value="{{ $unit }}"
                                    {{ old('unit') == $unit ? 'selected' : '' }}>
                                    {{ $unit }}
                                </option>
                            @endforeach
                        </select>
                        @error('unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cost per Unit (₱)</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="cost_per_unit"
                                   class="form-control @error('cost_per_unit') is-invalid @enderror"
                                   step="0.0001" min="0"
                                   value="{{ old('cost_per_unit', '0.00') }}"
                                   placeholder="0.00">
                        </div>
                        @error('cost_per_unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            This will be updated automatically when you record purchases.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Low Stock Alert Threshold</label>
                        <div class="input-group">
                            <input type="number" name="low_stock_threshold"
                                   class="form-control"
                                   step="0.0001" min="0"
                                   value="{{ old('low_stock_threshold', '0') }}">
                            <span class="input-group-text" id="unitLabel">units</span>
                        </div>
                        <div class="form-text">
                            System will alert when stock drops to or below this number.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Save Material
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
@push('scripts')
<script>
    // Show the selected unit label next to threshold
    document.querySelector('[name=unit]').addEventListener('change', function() {
        document.getElementById('unitLabel').textContent = this.value || 'units';
    });
</script>
@endpush