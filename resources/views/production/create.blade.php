@extends('layouts.app')
@section('title', 'New Production Batch')
@section('content')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-gear-wide-connected"></i> Record Production Batch</span>
        <span id="recipeBadge" class="badge bg-success d-none">
            <i class="bi bi-bookmark-check"></i> Recipe loaded from last batch
        </span>
    </div>
    <div class="card-body">
        <form action="{{ route('production.store') }}" method="POST" id="productionForm">
            @csrf

            {{-- Error Messages --}}
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Please fill in all required fields:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>
                                @if(str_contains(strtolower($error), 'finished product'))
                                    Please select a <strong>Finished Product</strong>.
                                @elseif(str_contains(strtolower($error), 'production date'))
                                    Please enter a <strong>Production Date</strong>.
                                @elseif(str_contains(strtolower($error), 'expected output'))
                                    Please enter the <strong>Expected Output</strong> quantity.
                                @elseif(str_contains(strtolower($error), 'actual output'))
                                    Please enter the <strong>Actual Output</strong> quantity.
                                @elseif(str_contains(strtolower($error), 'expiry'))
                                    Please enter an <strong>Expiry Date</strong>.
                                @elseif(str_contains(strtolower($error), 'items'))
                                    Please add at least one <strong>Raw Material</strong> with a valid quantity.
                                @else
                                    {{ $error }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- ROW 1: Product, Dates --}}
            <div class="row g-3 mb-3">

                {{-- Finished Product --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Finished Product <span class="text-danger">*</span>
                    </label>
                    @if($selectedProduct)
                        <input type="hidden" name="finished_product_id"
                               value="{{ $selectedProduct->id }}"
                               id="productSelect">
                        <div class="form-control bg-light d-flex align-items-center gap-2"
                             style="cursor: not-allowed;">
                            <i class="bi bi-bag-check text-success"></i>
                            <span class="fw-semibold">{{ $selectedProduct->name }}</span>
                            <span class="badge bg-secondary ms-auto">
                                Stock: {{ qty_fmt($selectedProduct->current_stock) }}
                            </span>
                        </div>
                        <div class="form-text">
                            <i class="bi bi-lock"></i> Product locked.
                            <a href="{{ route('production.create') }}">Choose different product</a>
                        </div>
                    @else
                        <select name="finished_product_id"
                                id="productSelect"
                                class="form-select @error('finished_product_id') is-invalid @enderror"
                                required>
                            <option value="">-- Select Product --</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}"
                                    {{ old('finished_product_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }}
                                    (Stock: {{ qty_fmt($p->current_stock) }})
                                </option>
                            @endforeach
                        </select>
                        @error('finished_product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    @endif
                </div>

                {{-- Production Date --}}
                <div class="col-md-2">
                    <label class="form-label fw-semibold">
                        Production Date <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="production_date"
                           class="form-control @error('production_date') is-invalid @enderror"
                           value="{{ old('production_date', date('Y-m-d')) }}" required>
                    @error('production_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Expiry Date --}}
                <div class="col-md-2">
                    <label class="form-label fw-semibold">
                        Expiry Date <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="expiry_date"
                           class="form-control @error('expiry_date') is-invalid @enderror"
                           value="{{ old('expiry_date') }}" required>
                    @error('expiry_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            {{-- ROW 2: Output Fields --}}
            <div class="row g-3 mb-4">

                {{-- Expected Output --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        Expected Output <span class="text-danger">*</span>
                    </label>
                    <input type="number" name="expected_output_qty"
                           id="expectedQty"
                           class="form-control @error('expected_output_qty') is-invalid @enderror"
                           step="0.0001" min="0.0001"
                           value="{{ old('expected_output_qty') }}"
                           placeholder="0" required>
                    @error('expected_output_qty')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Actual Output --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        Actual Output <span class="text-danger">*</span>
                    </label>
                    <input type="number" name="actual_output_qty"
                           id="actualQty"
                           class="form-control @error('actual_output_qty') is-invalid @enderror"
                           step="0.0001" min="0.0001"
                           value="{{ old('actual_output_qty') }}"
                           placeholder="0" required>
                    @error('actual_output_qty')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text text-primary fw-semibold">
                        ↑ Goes to warehouse stock
                    </div>
                </div>

                {{-- Rejects --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        Rejects
                        <span class="text-muted fw-normal" style="font-size:0.75rem;">
                            (optional)
                        </span>
                    </label>
                    <input type="number" name="reject_qty"
                           class="form-control"
                           step="0.0001" min="0"
                           value="{{ old('reject_qty', 0) }}"
                           placeholder="0">
                    <div class="form-text text-muted">Not added to stock</div>
                </div>

            </div>

            {{-- Raw Materials Header --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-boxes"></i> Raw Materials Used
                </h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-warning d-none"
                            id="editRecipeBtn">
                        <i class="bi bi-pencil"></i> Edit Recipe
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary"
                            id="addRow">
                        <i class="bi bi-plus"></i> Add Material
                    </button>
                </div>
            </div>

            {{-- Recipe loading indicator --}}
            <div id="recipeLoading" class="alert alert-info py-2 d-none mb-3">
                <i class="bi bi-hourglass-split"></i> Loading last recipe...
            </div>

            {{-- No recipe notice --}}
            <div id="noRecipeNotice" class="alert alert-secondary py-2 d-none mb-3">
                <i class="bi bi-info-circle"></i>
                No previous batch found for this product. Please add raw materials manually.
            </div>

            {{-- Stock Warning --}}
            <div id="stockWarning" class="alert alert-danger d-none mb-3">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Cannot proceed:</strong>
                <span id="stockWarningMsg"></span>
            </div>

            {{-- Items Table --}}
            <div class="table-responsive mb-4" style="max-height:420px; overflow-y:auto;">
                <table class="table table-bordered table-sm mb-0" id="itemsTable"
                       style="min-width:900px;">
                    <thead style="position:sticky; top:0; z-index:1;">
                        <tr style="background:#004d3b;">
                            <th style="width:25%; padding:10px 12px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Material</th>
                            <th style="width:10%; padding:10px 12px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Category</th>
                            <th style="width:12%; padding:10px 12px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Available Stock</th>
                            <th style="width:6%;  padding:10px 12px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Unit</th>
                            <th style="width:13%; padding:10px 12px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Qty Used</th>
                            <th style="width:13%; padding:10px 12px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Remaining After</th>
                            <th style="width:11%; padding:10px 12px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Cost/Unit</th>
                            <th style="width:8%;  padding:10px 12px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Line Cost</th>
                            <th style="width:4%;  padding:10px 12px; border:none;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                    </tbody>
                </table>
            </div>

            {{-- Cost Summary --}}
            <div class="row justify-content-end mb-4">
                <div class="col-md-4">
                    <div class="card" style="border:1.5px solid #007A5E; background:#f0fdf4;">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Total Raw Material Cost:</span>
                                <span class="fw-bold">₱<span id="totalCost">0.00</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="bi bi-check-lg"></i> Save Production Batch
                </button>
                <a href="{{ route('production.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>
function formatQty(n) {
    const x = parseFloat(n);
    if (isNaN(x)) return '0';
    if (Math.abs(x - Math.round(x)) < 1e-9) return String(Math.round(x));
    let s = x.toFixed(4);
    return s.replace(/\.?0+$/, '') || '0';
}
const allMaterials   = @json($allMaterials);
const lastBatchItems = @json($lastBatchItems);
const lastRecipeUrl  = "{{ route('production.last-recipe', ':id') }}";
let rowIndex         = 0;
let recipeMode       = false;

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.material-select'))
        .map(s => s.value).filter(v => v !== '');
}

function buildOptions(currentValue = '') {
    const selected = getSelectedIds();
    return `<option value="">-- Select Material --</option>` +
        allMaterials.map(m => {
            const isSelected = String(m.id) === String(currentValue);
            const isDisabled = selected.includes(String(m.id)) && !isSelected;
            return `<option value="${m.id}"
                        data-unit="${m.unit}"
                        data-category="${m.category}"
                        data-stock="${m.stock_quantity}"
                        data-cost="${m.cost_per_unit}"
                        ${isSelected ? 'selected' : ''}
                        ${isDisabled  ? 'disabled' : ''}>
                    ${m.name} — ${formatQty(m.stock_quantity)} ${m.unit}
                </option>`;
        }).join('');
}

function refreshAllSelects() {
    document.querySelectorAll('.material-select').forEach(sel => {
        const cur = sel.value;
        sel.innerHTML = buildOptions(cur);
    });
}

function buildRow(index, material = null, qty = '', locked = false) {
    const matId     = material ? (material.raw_material_id || material.id) : '';
    const rowBg     = index % 2 === 0 ? '#ffffff' : '#f9fafb';
    const stockColor = material
        ? (material.stock_quantity <= 0 ? '#dc2626' : '#007A5E')
        : '#6b7280';

    return `<tr class="item-row" data-index="${index}"
                style="background:${rowBg}; border-bottom:1px solid #e5e7eb;">
        <td style="padding:8px 12px; vertical-align:middle; border:none;">
            <select name="items[${index}][raw_material_id]"
                    class="form-select form-select-sm material-select ${locked ? 'pe-none bg-light' : ''}"
                    required>
                ${buildOptions(matId)}
            </select>
        </td>
        <td style="padding:8px 12px; vertical-align:middle; border:none;">
            <span class="category-label" style="font-size:0.8rem; color:#6b7280;">
                ${material
                    ? (material.category === 'ingredients' ? '🧂 Ingredients' : '📦 Packaging')
                    : '—'}
            </span>
        </td>
        <td style="padding:8px 12px; vertical-align:middle; border:none;">
            <span class="stock-label"
                  style="font-size:0.85rem; font-weight:600; color:${stockColor};"
                  data-stock="${material ? material.stock_quantity : 0}">
                ${material
                    ? formatQty(material.stock_quantity) + ' ' + material.unit
                    : '—'}
            </span>
        </td>
        <td style="padding:8px 12px; vertical-align:middle; border:none;">
            <span class="unit-label" style="font-size:0.82rem; color:#6b7280;">
                ${material ? material.unit : '—'}
            </span>
        </td>
        <td style="padding:6px 12px; vertical-align:middle; border:none;">
            <input type="number" name="items[${index}][quantity_used]"
                   class="form-control form-control-sm qty-input"
                   style="max-width:110px; border-radius:8px; border:1.5px solid #e5e7eb;"
                   step="0.0001" min="0.0001"
                   value="${qty}"
                   placeholder="0"
                   ${locked ? 'readonly style="background:#f8f9fa; max-width:110px; border-radius:8px;"' : ''}
                   required>
        </td>
        <td style="padding:8px 12px; vertical-align:middle; border:none;">
            <span class="remaining-label" style="font-size:0.82rem; color:#6b7280;">—</span>
        </td>
        <td style="padding:8px 12px; vertical-align:middle; border:none;">
            <span class="cost-label" style="font-size:0.82rem; color:#6b7280;"
                  data-cost="${material ? material.cost_per_unit : 0}">
                ${material ? '₱' + parseFloat(material.cost_per_unit).toFixed(4) : '—'}
            </span>
        </td>
        <td style="padding:8px 12px; vertical-align:middle; text-align:right; border:none;">
            <span class="line-cost" style="font-size:0.85rem; font-weight:600;">₱0.00</span>
        </td>
        <td style="padding:8px 12px; vertical-align:middle; text-align:center; border:none;">
            <button type="button"
                    class="btn btn-sm btn-outline-danger remove-row ${locked ? 'd-none' : ''}"
                    style="padding:3px 7px; border-radius:6px;">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>`;
}

function renderRows(items, locked = true) {
    const body = document.getElementById('itemsBody');
    body.innerHTML = '';
    rowIndex = 0;

    if (items.length > 0) {
        items.forEach(item => {
            body.insertAdjacentHTML('beforeend',
                buildRow(rowIndex, item, item.quantity_used, locked));
            calcRow(body.querySelector(`[data-index="${rowIndex}"]`));
            rowIndex++;
        });
        document.getElementById('editRecipeBtn').classList.remove('d-none');
        document.getElementById('recipeBadge').classList.remove('d-none');
        document.getElementById('noRecipeNotice').classList.add('d-none');
        recipeMode = true;
    } else {
        body.insertAdjacentHTML('beforeend', buildRow(rowIndex, null, '', false));
        rowIndex++;
        document.getElementById('editRecipeBtn').classList.add('d-none');
        document.getElementById('recipeBadge').classList.add('d-none');
        recipeMode = false;
    }

    refreshAllSelects();
    calcTotals();
    checkAllStock();
}

function loadLastRecipe(productId) {
    if (!productId) {
        renderRows([]);
        return;
    }

    const loading = document.getElementById('recipeLoading');
    const notice  = document.getElementById('noRecipeNotice');
    loading.classList.remove('d-none');
    notice.classList.add('d-none');
    document.getElementById('recipeBadge').classList.add('d-none');

    const url = lastRecipeUrl.replace(':id', productId);

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(items => {
            loading.classList.add('d-none');
            if (items.length > 0) {
                renderRows(items, true);
            } else {
                notice.classList.remove('d-none');
                renderRows([], false);
            }
        })
        .catch(() => {
            loading.classList.add('d-none');
            renderRows([], false);
        });
}

const productSelect = document.getElementById('productSelect');
if (productSelect && productSelect.tagName === 'SELECT') {
    productSelect.addEventListener('change', function () {
        loadLastRecipe(this.value);
    });
}

function calcRow(row) {
    const qty       = parseFloat(row.querySelector('.qty-input').value) || 0;
    const cost      = parseFloat(row.querySelector('.cost-label').dataset.cost || 0);
    const stock     = parseFloat(row.querySelector('.stock-label').dataset.stock || 0);
    const remaining = stock - qty;
    const unit      = row.querySelector('.unit-label').textContent.trim();

    const lineEl = row.querySelector('.line-cost');
    lineEl.textContent = '₱' + (qty * cost).toFixed(2);
    lineEl.style.color = qty > 0 ? '#111827' : '#6b7280';

    const remLabel = row.querySelector('.remaining-label');
    if (qty > 0) {
        remLabel.textContent      = formatQty(remaining) + ' ' + unit;
        remLabel.style.fontWeight = '600';
        remLabel.style.color      = remaining < 0
            ? '#dc2626' : remaining === 0 ? '#d97706' : '#007A5E';
    } else {
        remLabel.textContent      = '—';
        remLabel.style.fontWeight = 'normal';
        remLabel.style.color      = '#6b7280';
    }

    calcTotals();
    checkAllStock();
}

function calcTotals() {
    let total = 0;
    document.querySelectorAll('.line-cost').forEach(el => {
        total += parseFloat(el.textContent.replace('₱', '')) || 0;
    });
    document.getElementById('totalCost').textContent = total.toFixed(2);
}

function checkAllStock() {
    const rows   = document.querySelectorAll('.item-row');
    const banner = document.getElementById('stockWarning');
    const msg    = document.getElementById('stockWarningMsg');
    const btn    = document.getElementById('submitBtn');
    const errors = [];

    rows.forEach(row => {
        const qty   = parseFloat(row.querySelector('.qty-input').value) || 0;
        const stock = parseFloat(row.querySelector('.stock-label').dataset.stock || 0);
        const sel   = row.querySelector('.material-select');
        const name  = sel.options[sel.selectedIndex]?.text?.split('—')[0]?.trim() || 'Unknown';

        if (sel.value && qty > stock) {
            errors.push(`<strong>${name}</strong>: needs ${formatQty(qty)}, only ${formatQty(stock)} available.`);
        }
        if (sel.value && qty > 0 && stock <= 0) {
            errors.push(`<strong>${name}</strong> is OUT OF STOCK.`);
        }
    });

    if (errors.length > 0) {
        banner.classList.remove('d-none');
        msg.innerHTML = '<ul class="mb-0 mt-1">' +
            errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
        btn.disabled = true;
        btn.classList.replace('btn-primary', 'btn-secondary');
    } else {
        banner.classList.add('d-none');
        msg.innerHTML = '';
        btn.disabled  = false;
        btn.classList.replace('btn-secondary', 'btn-primary');
    }
}

document.getElementById('itemsBody').addEventListener('change', function(e) {
    if (e.target.classList.contains('material-select')) {
        const row   = e.target.closest('.item-row');
        const sel   = e.target.options[e.target.selectedIndex];
        const unit  = sel.dataset.unit     || '—';
        const cat   = sel.dataset.category || '—';
        const stock = sel.dataset.stock    || '0';
        const cost  = sel.dataset.cost     || '0';

        row.querySelector('.unit-label').textContent     = unit;
        row.querySelector('.cost-label').textContent     = '₱' + parseFloat(cost).toFixed(4);
        row.querySelector('.cost-label').dataset.cost    = cost;
        row.querySelector('.category-label').textContent =
            cat === 'ingredients' ? '🧂 Ingredients' : '📦 Packaging';

        const stockLabel         = row.querySelector('.stock-label');
        stockLabel.textContent   = formatQty(stock) + ' ' + unit;
        stockLabel.dataset.stock = stock;
        stockLabel.style.color   = parseFloat(stock) <= 0 ? '#dc2626' : '#007A5E';

        row.querySelector('.qty-input').value             = '';
        row.querySelector('.remaining-label').textContent = '—';
        row.querySelector('.line-cost').textContent       = '₱0.00';

        refreshAllSelects();
        calcTotals();
        checkAllStock();
    }
});

document.getElementById('itemsBody').addEventListener('input', function(e) {
    if (e.target.classList.contains('qty-input')) {
        calcRow(e.target.closest('.item-row'));
    }
});

document.getElementById('actualQty').addEventListener('input', function () { calcTotals(); });

document.getElementById('itemsBody').addEventListener('click', function(e) {
    if (e.target.closest('.remove-row')) {
        if (document.querySelectorAll('.item-row').length > 1) {
            e.target.closest('.item-row').remove();
            refreshAllSelects();
            calcTotals();
            checkAllStock();
        } else {
            alert('At least one raw material is required.');
        }
    }
});

document.getElementById('addRow').addEventListener('click', function() {
    document.getElementById('itemsBody')
        .insertAdjacentHTML('beforeend', buildRow(rowIndex, null, '', false));
    rowIndex++;
    refreshAllSelects();
    checkAllStock();
});

document.getElementById('editRecipeBtn').addEventListener('click', function() {
    if (confirm('Unlock the recipe to make changes? Fields will become editable.')) {
        document.querySelectorAll('.material-select').forEach(s => {
            s.classList.remove('pe-none', 'bg-light');
        });
        document.querySelectorAll('.qty-input').forEach(i => {
            i.readOnly         = false;
            i.style.background = '';
        });
        document.querySelectorAll('.remove-row').forEach(b => {
            b.classList.remove('d-none');
        });
        this.classList.add('d-none');
        refreshAllSelects();
        checkAllStock();
    }
});

// Init
@if($selectedProduct && $lastBatchItems->count())
    renderRows(@json($lastBatchItems), true);
@elseif($selectedProduct && !$lastBatchItems->count())
    renderRows([], false);
    document.getElementById('noRecipeNotice').classList.remove('d-none');
@else
    renderRows([], false);
@endif
</script>
@endpush