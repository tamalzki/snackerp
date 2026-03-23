@extends('layouts.app')
@section('title', 'Restock — ' . $finishedProduct->name)
@section('content')

<div class="row justify-content-center">
    <div class="col-md-6">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-cart-check text-success"></i>
                Restock: {{ $finishedProduct->name }}
            </h5>
            <a href="{{ route('finished-products.show', $finishedProduct) }}"
               class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        {{-- Current stock info --}}
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="card text-center">
                    <div class="card-body py-2">
                        <div class="text-muted small">Current Stock</div>
                        <div class="fw-bold text-primary">
                            {{ qty_fmt($finishedProduct->current_stock) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center">
                    <div class="card-body py-2">
                        <div class="text-muted small">Avg Cost</div>
                        <div class="fw-bold">
                            ₱{{ number_format($finishedProduct->average_cost, 4) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card text-center">
                    <div class="card-body py-2">
                        <div class="text-muted small">Selling Price</div>
                        <div class="fw-bold text-success">
                            ₱{{ number_format($finishedProduct->selling_price, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Add Stock Entry
            </div>
            <div class="card-body">
                <form action="{{ route('finished-products.restock.store', $finishedProduct) }}"
                      method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity to Add</label>
                        <div class="input-group">
                            <input type="number" name="quantity"
                                   class="form-control @error('quantity') is-invalid @enderror"
                                   step="0.0001" min="0.0001"
                                   value="{{ old('quantity') }}"
                                   placeholder="0"
                                   id="qty">
                            <span class="input-group-text">pcs</span>
                        </div>
                        @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Unit Cost (₱)</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="unit_cost"
                                   class="form-control @error('unit_cost') is-invalid @enderror"
                                   step="0.0001" min="0"
                                   value="{{ old('unit_cost') }}"
                                   placeholder="0.00"
                                   id="unitCost">
                        </div>
                        @error('unit_cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Live total cost --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Total Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="text" class="form-control bg-light fw-semibold text-success"
                                   id="totalCost" readonly value="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Restock Date</label>
                        <input type="date" name="restock_date"
                               class="form-control @error('restock_date') is-invalid @enderror"
                               value="{{ old('restock_date', date('Y-m-d')) }}" required>
                        @error('restock_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Supplier (optional)</label>
                        <input type="text" name="supplier"
                               class="form-control"
                               value="{{ old('supplier') }}"
                               placeholder="e.g. ABC Supplier">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notes (optional)</label>
                        <textarea name="notes" class="form-control"
                                  rows="2">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Add Stock
                        </button>
                        <a href="{{ route('finished-products.show', $finishedProduct) }}"
                           class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
const qty      = document.getElementById('qty');
const unitCost = document.getElementById('unitCost');
const total    = document.getElementById('totalCost');

function calcTotal() {
    const t = (parseFloat(qty.value) || 0) * (parseFloat(unitCost.value) || 0);
    total.value = t.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

qty.addEventListener('input', calcTotal);
unitCost.addEventListener('input', calcTotal);
</script>
@endpush