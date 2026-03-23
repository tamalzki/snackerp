@extends('layouts.app')
@section('title', 'Adjust stock — ' . $finishedProduct->name)
@section('content')

@php
    $reasonLabels = [
        'physical_count' => 'Physical count correction',
        'damage' => 'Damage / spoilage',
        'shrinkage' => 'Shrinkage / loss',
        'found' => 'Found stock',
        'data_entry' => 'Data entry correction',
        'other' => 'Other',
    ];
@endphp

<div class="row justify-content-center">
    <div class="col-md-7">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-sliders text-warning"></i>
                Adjust warehouse stock: {{ $finishedProduct->name }}
            </h5>
            <a href="{{ route('finished-products.show', $finishedProduct) }}"
               class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <div class="alert alert-info small py-2 mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Sets <strong>warehouse</strong> quantity (<code>current_stock</code>). This is separate from branch inventory.
            <strong>Average cost is not changed</strong> — use <strong>Restock</strong> for purchases (resale) or
            <strong>Make batch</strong> (manufactured) when adding stock with cost.
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body py-2">
                        <div class="text-muted small">Current warehouse stock</div>
                        <div class="fw-bold text-primary fs-5">
                            {{ qty_fmt($finishedProduct->current_stock) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body py-2">
                        <div class="text-muted small">Avg cost</div>
                        <div class="fw-bold">
                            ₱{{ number_format($finishedProduct->average_cost, 4) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body py-2">
                        <div class="text-muted small">Stock value (est.)</div>
                        <div class="fw-bold text-success">
                            ₱{{ number_format($finishedProduct->current_stock * $finishedProduct->average_cost, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-box-seam"></i> New warehouse quantity
            </div>
            <div class="card-body">
                <form action="{{ route('finished-products.adjust.store', $finishedProduct) }}"
                      method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">New quantity <span class="text-danger">*</span></label>
                        <input type="number" name="new_quantity"
                               class="form-control @error('new_quantity') is-invalid @enderror"
                               step="any" min="0"
                               value="{{ qty_fmt(old('new_quantity', $finishedProduct->current_stock)) }}"
                               required>
                        @error('new_quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Enter the counted (correct) quantity on hand at the warehouse.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <select name="reason" class="form-select @error('reason') is-invalid @enderror" required>
                            <option value="">— Select —</option>
                            @foreach($reasonLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes (optional)</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                  rows="2" maxlength="1000"
                                  placeholder="e.g. Annual inventory, damaged crate #3">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-lg"></i> Apply adjustment
                        </button>
                        <a href="{{ route('finished-products.show', $finishedProduct) }}"
                           class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
