@extends('layouts.app')
@section('title', 'New Purchase')
@section('content')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cart-plus"></i> Record Raw Material Purchase</span>
        @if($selectedMaterial)
            <span class="badge bg-success">
                <i class="bi bi-bookmark-check"></i> Purchasing: {{ $selectedMaterial->name }}
            </span>
        @endif
    </div>
    <div class="card-body">
        <form action="{{ route('purchases.store') }}" method="POST" id="purchaseForm">
            @csrf

            {{-- Header --}}
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Supplier Name</label>
                    <input type="text" name="supplier_name"
                           class="form-control @error('supplier_name') is-invalid @enderror"
                           value="{{ old('supplier_name') }}"
                           placeholder="e.g. ABC Supplies Co.">
                    @error('supplier_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Purchase Date</label>
                    <input type="date" name="purchase_date"
                           class="form-control @error('purchase_date') is-invalid @enderror"
                           value="{{ old('purchase_date', date('Y-m-d')) }}">
                    @error('purchase_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Items Table --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0">Purchase Items</h6>
                @if(!$selectedMaterial)
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRow">
                        <i class="bi bi-plus"></i> Add Item
                    </button>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:30%">Material</th>
                            <th style="width:13%">Category</th>
                            <th style="width:12%">Current Stock</th>
                            <th style="width:10%">Unit</th>
                            <th style="width:13%">Qty to Purchase</th>
                            <th style="width:14%">Cost / Unit (₱)</th>
                            <th style="width:13%">Line Total</th>
                            @if(!$selectedMaterial)
                            <th style="width:5%"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="itemsBody">

                        @if($selectedMaterial)
                        {{-- Locked single row --}}
                        <tr class="item-row" data-index="0">
                            <td>
                                <input type="hidden"
                                       name="items[0][raw_material_id]"
                                       value="{{ $selectedMaterial->id }}">
                                <div class="form-control bg-light d-flex align-items-center gap-2"
                                     style="cursor:not-allowed;">
                                    <i class="bi bi-boxes text-primary"></i>
                                    <span class="fw-semibold">{{ $selectedMaterial->name }}</span>
                                    <span class="badge bg-{{ $selectedMaterial->category === 'ingredients' ? 'primary' : 'info text-dark' }} ms-auto">
                                        {{ $selectedMaterial->category === 'ingredients' ? '🧂 Ingredients' : '📦 Packaging' }}
                                    </span>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-lock"></i> Material locked.
                                    <a href="{{ route('purchases.create') }}">Purchase different material</a>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $selectedMaterial->category === 'ingredients' ? 'primary' : 'info text-dark' }}">
                                    {{ $selectedMaterial->category === 'ingredients' ? '🧂 Ingredients' : '📦 Packaging' }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-semibold text-primary">
                                    {{ qty_fmt($selectedMaterial->stock_quantity) }}
                                    {{ $selectedMaterial->unit }}
                                </span>
                            </td>
                            <td>{{ $selectedMaterial->unit }}</td>
                            <td>
                                <input type="number" name="items[0][quantity]"
                                       class="form-control qty-input"
                                       step="0.0001" min="0.0001"
                                       placeholder="0" required>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="items[0][cost_per_unit]"
                                           class="form-control cpu-input"
                                           step="0.0001" min="0.0001"
                                           value="{{ number_format($selectedMaterial->cost_per_unit, 4) }}"
                                           placeholder="0.00" required>
                                </div>
                            </td>
                            <td class="fw-semibold line-total text-end align-middle">₱0.00</td>
                        </tr>

                        @else
                        {{-- Free select first row --}}
                        <tr class="item-row" data-index="0">
                            <td>
                                <select name="items[0][raw_material_id]"
                                        class="form-select material-select" required>
                                    <option value="">-- Select Material --</option>
                                    @foreach($materials as $m)
                                        <option value="{{ $m->id }}"
                                                data-unit="{{ $m->unit }}"
                                                data-category="{{ $m->category }}"
                                                data-stock="{{ $m->stock_quantity }}"
                                                data-cost="{{ $m->cost_per_unit }}">
                                            {{ $m->name }} — {{ $m->category }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td><span class="category-label text-muted small">—</span></td>
                            <td><span class="stock-label text-muted small">—</span></td>
                            <td><span class="unit-label text-muted small">—</span></td>
                            <td>
                                <input type="number" name="items[0][quantity]"
                                       class="form-control qty-input"
                                       step="0.0001" min="0.0001"
                                       placeholder="0" required>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="items[0][cost_per_unit]"
                                           class="form-control cpu-input"
                                           step="0.0001" min="0.0001"
                                           placeholder="0.00" required>
                                </div>
                            </td>
                            <td class="fw-semibold line-total text-end">₱0.00</td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger remove-row">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endif

                    </tbody>
                </table>
            </div>

            {{-- Grand Total --}}
            <div class="text-end mb-4">
                <div class="d-inline-block bg-light rounded px-4 py-2">
                    <span class="text-muted">Grand Total:</span>
                    <span class="fw-bold fs-5 text-success ms-2">
                        ₱<span id="grandTotal">0.00</span>
                    </span>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Confirm Purchase & Update Stock
                </button>
                <a href="{{ route('purchases.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>
const materials = @json($materials);
let rowIndex = 1;

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.material-select'))
        .map(s => s.value).filter(v => v !== '');
}

function buildOptions(currentValue = '') {
    const selected = getSelectedIds();
    return `<option value="">-- Select Material --</option>` +
        materials.map(m => {
            const isSelected = String(m.id) === String(currentValue);
            const isDisabled = selected.includes(String(m.id)) && !isSelected;
            return `<option value="${m.id}"
                            data-unit="${m.unit}"
                            data-category="${m.category}"
                            data-stock="${m.stock_quantity}"
                            data-cost="${m.cost_per_unit}"
                            ${isSelected ? 'selected' : ''}
                            ${isDisabled ? 'disabled' : ''}>
                        ${m.name} — ${m.category}
                    </option>`;
        }).join('');
}

function refreshAllSelects() {
    document.querySelectorAll('.material-select').forEach(sel => {
        const cur = sel.value;
        sel.innerHTML = buildOptions(cur);
    });
}

function calcRow(row) {
    const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
    const cpu = parseFloat(row.querySelector('.cpu-input').value) || 0;
    row.querySelector('.line-total').textContent = '₱' + (qty * cpu).toFixed(2);
    calcGrandTotal();
}

function calcGrandTotal() {
    let total = 0;
    document.querySelectorAll('.line-total').forEach(el => {
        total += parseFloat(el.textContent.replace('₱', '')) || 0;
    });
    document.getElementById('grandTotal').textContent = total.toFixed(2);
}

document.getElementById('itemsBody').addEventListener('change', function(e) {
    if (e.target.classList.contains('material-select')) {
        const row      = e.target.closest('.item-row');
        const selected = e.target.options[e.target.selectedIndex];
        const unit     = selected.dataset.unit     || '—';
        const cat      = selected.dataset.category || '—';
        const stock    = selected.dataset.stock    || '0';
        const cost     = selected.dataset.cost     || '0';

        row.querySelector('.unit-label').textContent     = unit;
        row.querySelector('.stock-label').textContent    = parseFloat(stock).toFixed(2) + ' ' + unit;
        row.querySelector('.category-label').textContent =
            cat === 'ingredients' ? '🧂 Ingredients' : '📦 Packaging';
        row.querySelector('.cpu-input').value = parseFloat(cost).toFixed(4);
        row.querySelector('.qty-input').value = '';
        row.querySelector('.line-total').textContent = '₱0.00';

        refreshAllSelects();
        calcGrandTotal();
    }
});

document.getElementById('itemsBody').addEventListener('input', function(e) {
    if (e.target.classList.contains('qty-input') ||
        e.target.classList.contains('cpu-input')) {
        calcRow(e.target.closest('.item-row'));
    }
});

document.getElementById('itemsBody').addEventListener('click', function(e) {
    if (e.target.closest('.remove-row')) {
        if (document.querySelectorAll('.item-row').length > 1) {
            e.target.closest('.item-row').remove();
            refreshAllSelects();
            calcGrandTotal();
        } else {
            alert('At least one item is required.');
        }
    }
});

const addRowBtn = document.getElementById('addRow');
if (addRowBtn) {
    addRowBtn.addEventListener('click', function() {
        const newRow = document.createElement('tr');
        newRow.className     = 'item-row';
        newRow.dataset.index = rowIndex;
        newRow.innerHTML = `
            <td>
                <select name="items[${rowIndex}][raw_material_id]"
                        class="form-select material-select" required>
                    ${buildOptions()}
                </select>
            </td>
            <td><span class="category-label text-muted small">—</span></td>
            <td><span class="stock-label text-muted small">—</span></td>
            <td><span class="unit-label text-muted small">—</span></td>
            <td>
                <input type="number" name="items[${rowIndex}][quantity]"
                       class="form-control qty-input"
                       step="0.0001" min="0.0001" placeholder="0" required>
            </td>
            <td>
                <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" name="items[${rowIndex}][cost_per_unit]"
                           class="form-control cpu-input"
                           step="0.0001" min="0.0001" placeholder="0.00" required>
                </div>
            </td>
            <td class="fw-semibold line-total text-end">₱0.00</td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                    <i class="bi bi-trash"></i>
                </button>
            </td>`;
        document.getElementById('itemsBody').appendChild(newRow);
        rowIndex++;
        refreshAllSelects();
    });
}
</script>
@endpush