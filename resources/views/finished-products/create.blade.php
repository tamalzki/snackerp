@extends('layouts.app')
@section('title', 'Add Finished Product')
@section('content')

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Add Finished Product
            </div>
            <div class="card-body">
                <form action="{{ route('finished-products.store') }}" method="POST">
                    @csrf

                    {{-- Type selector --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Product Type</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="type"
                                       id="typeManufactured" value="manufactured"
                                       {{ old('type', 'manufactured') === 'manufactured' ? 'checked' : '' }}
                                       onchange="toggleTypeInfo()">
                                <label class="btn btn-outline-primary w-100 d-flex flex-column align-items-center py-3"
                                       for="typeManufactured">
                                    <i class="bi bi-gear-wide-connected fs-4 mb-1"></i>
                                    <strong>Manufactured</strong>
                                    <small class="text-muted fw-normal mt-1" style="font-size:0.75rem">
                                        Made from raw materials
                                    </small>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="type"
                                       id="typeResale" value="resale"
                                       {{ old('type') === 'resale' ? 'checked' : '' }}
                                       onchange="toggleTypeInfo()">
                                <label class="btn btn-outline-success w-100 d-flex flex-column align-items-center py-3"
                                       for="typeResale">
                                    <i class="bi bi-cart-check fs-4 mb-1"></i>
                                    <strong>Resale</strong>
                                    <small class="text-muted fw-normal mt-1" style="font-size:0.75rem">
                                        Buy & sell directly
                                    </small>
                                </label>
                            </div>
                        </div>
                        @error('type')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Type info banners --}}
                    <div id="infoManufactured" class="alert alert-primary py-2 small mb-3">
                        <i class="bi bi-info-circle"></i>
                        Stock and cost are <strong>auto-calculated</strong> from Production Batches.
                    </div>
                    <div id="infoResale" class="alert alert-success py-2 small mb-3 d-none">
                        <i class="bi bi-info-circle"></i>
                        Stock is added manually via <strong>Restock</strong> entries.
                        Cost is calculated using weighted average.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Product Name</label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               placeholder="e.g. Chicharon 50g Pack">
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
                                   value="{{ old('selling_price', '0.00') }}">
                        </div>
                        @error('selling_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Low Stock Alert Threshold</label>
                        <div class="input-group">
                            <input type="number" name="low_stock_threshold"
                                   class="form-control"
                                   step="0.0001" min="0"
                                   value="{{ old('low_stock_threshold', '0') }}">
                            <span class="input-group-text">pcs</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Save Product
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
@push('scripts')
<script>
function toggleTypeInfo() {
    const isResale = document.getElementById('typeResale').checked;
    document.getElementById('infoManufactured').classList.toggle('d-none', isResale);
    document.getElementById('infoResale').classList.toggle('d-none', !isResale);
}
</script>
@endpush