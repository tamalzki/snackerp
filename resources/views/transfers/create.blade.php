@extends('layouts.app')
@section('title', 'New Delivery')
@section('content')

<form action="{{ route('transfers.store') }}" method="POST" id="transferForm">
@csrf

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Please fix the following before saving:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>
                    @if(str_contains(strtolower($error), 'branch id'))
                        Please select a <strong>Branch</strong> to deliver to.
                    @elseif(str_contains(strtolower($error), 'transfer date'))
                        Please enter a <strong>Delivery Date</strong>.
                    @elseif(str_contains(strtolower($error), 'dr number'))
                        Please check the <strong>DR #</strong> field.
                    @elseif(str_contains(strtolower($error), 'items'))
                        Please add at least one product with a valid quantity.
                    @else
                        {{ $error }}
                    @endif
                </li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- HEADER --}}
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-truck"></i> New Delivery — Warehouse to Branch
    </div>
    <div class="card-body">
        <div class="row g-3">

            {{-- DR # --}}
            <div class="col-md-2">
                <label class="form-label fw-semibold">DR #</label>
                <input type="text" name="dr_number"
                       class="form-control @error('dr_number') is-invalid @enderror"
                       value="{{ old('dr_number') }}"
                       placeholder="e.g. DR-001">
                @error('dr_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Date --}}
            <div class="col-md-2">
                <label class="form-label fw-semibold">Delivery Date</label>
                <input type="date" name="transfer_date"
                       class="form-control @error('transfer_date') is-invalid @enderror"
                       value="{{ old('transfer_date', date('Y-m-d')) }}" required>
                @error('transfer_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Branch --}}
            <div class="col-md-3">
                <label class="form-label fw-semibold">Deliver To Branch</label>
                @if(isset($selectedBranch) && $selectedBranch)
                    <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
                    <div class="form-control bg-light d-flex align-items-center gap-2">
                        <i class="bi bi-shop text-success"></i>
                        <span class="fw-semibold">{{ $selectedBranch->name }}</span>
                    </div>
                    <div class="form-text">
                        <a href="{{ route('transfers.create') }}">Choose different branch</a>
                    </div>
                @else
                    <select name="branch_id"
                            class="form-select @error('branch_id') is-invalid @enderror"
                            required>
                        <option value="">-- Select Branch --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}"
                                {{ old('branch_id') == $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                @endif
            </div>

            {{-- Notes --}}
            <div class="col-md-5">
                <label class="form-label fw-semibold">Notes</label>
                <input type="text" name="notes" class="form-control"
                       value="{{ old('notes') }}"
                       placeholder="e.g. Weekly delivery, special order, etc.">
            </div>

        </div>
    </div>
</div>

{{-- PRODUCTS --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <span><i class="bi bi-bag-check"></i> Products</span>
            <button type="button" class="btn btn-sm btn-outline-warning" id="resetTable">
                <i class="bi bi-arrow-counterclockwise"></i> Reset Table
            </button>
        </div>
        {{-- Search --}}
        <div class="input-group" style="max-width:280px;">
            <span class="input-group-text bg-white py-1">
                <i class="bi bi-search text-muted small"></i>
            </span>
            <input type="text" id="productSearch"
                   class="form-control form-control-sm border-start-0"
                   placeholder="Search product...">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSearch">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>

    {{-- Validation error banner (e.g. no lines, exceeds stock) --}}
    <div id="qtyErrorBanner" class="alert alert-danger m-3 d-none"></div>

    <div class="alert alert-info mx-3 mt-3 mb-0 small py-2">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Tip:</strong> Leave quantity at <strong>0</strong> or blank to skip a product — it will
        <strong>not</strong> be on this delivery. You’ll get a summary to review before saving.
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" id="productsTable" style="border-collapse: separate; border-spacing: 0;">
    <thead>
        <tr style="background: #004d3b;">
            <th style="width:32%; padding: 12px 16px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Product</th>
            <th style="width:13%; padding: 12px 16px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Warehouse Stock</th>
            <th style="width:14%; padding: 12px 16px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Qty to Deliver</th>
            <th style="width:13%; padding: 12px 16px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Remaining After</th>
            <th style="width:10%; padding: 12px 16px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Cost/Unit</th>
            <th style="width:10%; padding: 12px 16px; color:#fff; font-weight:500; font-size:0.82rem; border:none;">Line Value</th>
            <th style="width:4%; padding: 12px 16px; border:none;"></th>
        </tr>
    </thead>
                <tbody id="itemsBody">
                    {{-- JS rendered --}}
                </tbody>
                <tfoot>
    <tr style="background:#f0fdf4; border-top: 2px solid #007A5E;">
        <th colspan="5" style="padding:12px 16px; text-align:right; font-size:0.88rem; color:#374151; border:none;">
            Total Delivery Value:
        </th>
        <th style="padding:12px 16px; font-size:1rem; color:#007A5E; border:none;" id="grandTotal">
            ₱0.00
        </th>
        <th style="border:none;"></th>
    </tr>
</tfoot>
            </table>
        </div>
    </div>

    <div class="card-footer d-flex gap-2">
        <button type="button" class="btn btn-primary" id="submitBtn">
            <i class="bi bi-check-lg"></i> Review &amp; confirm delivery
        </button>
        <a href="{{ route('transfers.index') }}" class="btn btn-secondary"
   onclick="formDirty = false;">Cancel</a>
    </div>
</div>

</form>

{{-- Pre-submit review: only qty &gt; 0 lines are saved; 0 qty excluded --}}
<div class="modal fade" id="deliveryReviewModal" tabindex="-1" aria-labelledby="deliveryReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deliveryReviewModalLabel">
                    <i class="bi bi-clipboard-check text-success"></i> Review delivery
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-2">
                    Only products with a quantity <strong>greater than zero</strong> will be included on this delivery.
                    Lines with <strong>0</strong> or blank quantity are <strong>not</strong> transferred.
                </p>
                <div id="reviewSkippedAlert" class="alert alert-warning small py-2 d-none"></div>
                <div class="table-responsive border rounded">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Warehouse stock</th>
                                <th class="text-center">Qty to deliver</th>
                                <th class="text-end">Line value (cost)</th>
                            </tr>
                        </thead>
                        <tbody id="reviewModalTableBody"></tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Total delivery value (cost)</th>
                                <th class="text-end text-success" id="reviewModalGrandTotal">₱0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-pencil"></i> Back to edit
                </button>
                <button type="button" class="btn btn-primary" id="confirmDeliverySubmit">
                    <i class="bi bi-check-lg"></i> Save delivery
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
const allProducts = @json($warehouseProducts);
let removedIds = new Set();

function escapeHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function formatQtyDisplay(n) {
    const x = parseFloat(n);
    if (isNaN(x)) return '0';
    if (Math.abs(x - Math.round(x)) < 1e-9) return String(Math.round(x));
    let t = x.toFixed(4);
    return t.replace(/\.?0+$/, '') || '0';
}

function updateGrandTotal() {
    let total = 0;
    document.querySelectorAll('.item-row:not(.d-none)').forEach(row => {
        const qty  = parseFloat(row.querySelector('.qty-input').value) || 0;
        const cost = parseFloat(row.dataset.cost) || 0;
        total += qty * cost;
    });
    document.getElementById('grandTotal').textContent =
        '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function calcRow(row) {
    const qty       = parseFloat(row.querySelector('.qty-input').value) || 0;
    const cost      = parseFloat(row.dataset.cost) || 0;
    const stock     = parseFloat(row.dataset.stock) || 0;
    const remaining = stock - qty;

    const remLabel = row.querySelector('.remaining-label');
    if (qty > 0) {
        remLabel.textContent = formatQtyDisplay(remaining);
        const remColor = remaining < 0 ? '#dc2626' : remaining === 0 ? '#d97706' : '#007A5E';
        remLabel.style.color = remColor;
        remLabel.style.fontWeight = '600';
        remLabel.style.fontSize = '0.85rem';
    } else {
        remLabel.textContent = '—';
        remLabel.style.color = '#6b7280';
        remLabel.style.fontWeight = 'normal';
    }

    const lineVal = qty * cost;
    row.querySelector('.line-value').textContent =
        lineVal > 0
            ? '₱' + lineVal.toLocaleString('en-PH', { minimumFractionDigits: 2 })
            : '—';

    const qtyInput = row.querySelector('.qty-input');
    if (qty > stock) {
        qtyInput.classList.add('is-invalid');
    } else {
        qtyInput.classList.remove('is-invalid');
    }

    updateGrandTotal();
}

function buildRow(product, index) {
    const stockColor = product.current_stock <= 0 ? '#dc2626' : '#007A5E';
    const rowBg      = index % 2 === 0 ? '#ffffff' : '#f9fafb';
    return `<tr class="item-row"
                data-index="${index}"
                data-id="${product.id}"
                data-stock="${product.current_stock}"
                data-cost="${product.average_cost}"
                data-name="${product.name.toLowerCase()}"
                style="background:${rowBg}; border-bottom: 1px solid #e5e7eb;">
        <td style="padding: 10px 16px; vertical-align:middle; font-size:0.88rem; color:#111827; border:none;">
            <span class="product-label fw-semibold">${escapeHtml(product.name)}</span>
            <input type="hidden"
                   name="items[${index}][finished_product_id]"
                   value="${product.id}"
                   class="product-id-input">
        </td>
        <td style="padding: 10px 16px; vertical-align:middle; border:none;">
            <span style="font-size:0.85rem; font-weight:600; color:${stockColor};">
                ${formatQtyDisplay(product.current_stock)}
                <span style="font-weight:400; color:#6b7280; font-size:0.78rem;">pcs</span>
            </span>
        </td>
        <td style="padding: 8px 16px; vertical-align:middle; border:none;">
            <input type="number"
                   name="items[${index}][quantity]"
                   class="form-control form-control-sm qty-input"
                   style="max-width:100px; border-radius:8px; border:1.5px solid #e5e7eb; font-size:0.88rem;"
                   step="1" min="0" placeholder="0"
                   data-index="${index}">
        </td>
        <td style="padding: 10px 16px; vertical-align:middle; border:none;">
            <span class="remaining-label" style="font-size:0.85rem; color:#6b7280;">—</span>
        </td>
        <td style="padding: 10px 16px; vertical-align:middle; border:none;">
            <span style="font-size:0.82rem; color:#6b7280;">
                ₱${parseFloat(product.average_cost).toFixed(4)}
            </span>
        </td>
        <td style="padding: 10px 16px; vertical-align:middle; border:none;">
            <span class="line-value" style="font-size:0.85rem; color:#6b7280;">—</span>
        </td>
        <td style="padding: 10px 16px; vertical-align:middle; text-align:center; border:none;">
            <button type="button"
                    class="btn btn-sm btn-outline-danger remove-row"
                    style="padding: 3px 7px; border-radius:6px;"
                    title="Remove this product"
                    data-id="${product.id}">
                <i class="bi bi-x-circle"></i>
            </button>
        </td>
    </tr>`;

}

function initTable() {
    const body = document.getElementById('itemsBody');
    body.innerHTML = '';
    allProducts.forEach((p, i) => {
        body.insertAdjacentHTML('beforeend', buildRow(p, i));
    });
    document.getElementById('productSearch').value = '';
    document.getElementById('qtyErrorBanner').classList.add('d-none');
    updateGrandTotal();
}

// Search
document.getElementById('productSearch').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.item-row').forEach(row => {
        const name = row.dataset.name || '';
        row.classList.toggle('d-none', q !== '' && !name.includes(q));
    });
});

document.getElementById('clearSearch').addEventListener('click', function () {
    document.getElementById('productSearch').value = '';
    document.querySelectorAll('.item-row').forEach(row => row.classList.remove('d-none'));
});

// Qty input
document.getElementById('itemsBody').addEventListener('input', function (e) {
    if (e.target.classList.contains('qty-input')) {
        calcRow(e.target.closest('.item-row'));
    }
});

// Enter key — jump to next row
document.getElementById('itemsBody').addEventListener('keydown', function (e) {
    if (e.target.classList.contains('qty-input') && e.key === 'Enter') {
        e.preventDefault();
        const allInputs = Array.from(
            document.querySelectorAll('.item-row:not(.d-none) .qty-input:not(:disabled)')
        );
        const currentIndex = allInputs.indexOf(e.target);
        if (currentIndex !== -1 && currentIndex < allInputs.length - 1) {
            allInputs[currentIndex + 1].focus();
            allInputs[currentIndex + 1].select();
        }
    }
});

// Remove row
document.getElementById('itemsBody').addEventListener('click', function (e) {
    const btn = e.target.closest('.remove-row');
    if (btn) {
        const row = btn.closest('.item-row');
        row.querySelector('.product-id-input').disabled = true;
        row.querySelector('.qty-input').disabled        = true;
        row.classList.add('d-none');
        removedIds.add(btn.dataset.id);
        updateGrandTotal();
        document.getElementById('qtyErrorBanner').classList.add('d-none');
    }
});

// Reset table — no confirm, just reset
document.getElementById('resetTable').addEventListener('click', function () {
    removedIds = new Set();
    initTable();
});

// Warn on browser refresh / F5 / tab close
let formDirty = false;

document.getElementById('itemsBody').addEventListener('input', function () {
    formDirty = true;
});

window.addEventListener('beforeunload', function (e) {
    if (formDirty) {
        e.preventDefault();
        e.returnValue = '';
    }
});

function validateForReview() {
    const banner = document.getElementById('qtyErrorBanner');
    banner.classList.add('d-none');

    const form = document.getElementById('transferForm');
    const branchHidden = form.querySelector('input[name="branch_id"][type="hidden"]');
    const branchSelect = form.querySelector('select[name="branch_id"]');
    if (!branchHidden?.value && (!branchSelect || !branchSelect.value)) {
        banner.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Select a branch</strong> to deliver to before continuing.`;
        banner.classList.remove('d-none');
        banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }

    let hasPositive = false;
    let hasOverStock = false;

    document.querySelectorAll('.item-row').forEach(row => {
        const qtyIn = row.querySelector('.qty-input');
        if (!qtyIn || qtyIn.disabled) return;

        const qty = parseFloat(qtyIn.value) || 0;
        const stock = parseFloat(row.dataset.stock) || 0;
        if (qty > 0) {
            hasPositive = true;
            if (qty > stock) hasOverStock = true;
        }
    });

    if (!hasPositive) {
        banner.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>
            Enter a quantity <strong>greater than zero</strong> for at least one product.`;
        banner.classList.remove('d-none');
        banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }

    if (hasOverStock) {
        banner.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>
            One or more lines exceed <strong>warehouse stock</strong>. Reduce quantities (highlighted in red) before continuing.`;
        banner.classList.remove('d-none');
        banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }

    return true;
}

function buildReviewModal() {
    const tbody = document.getElementById('reviewModalTableBody');
    const skippedAlert = document.getElementById('reviewSkippedAlert');
    tbody.innerHTML = '';

    let skipped = 0;
    let grand = 0;

    document.querySelectorAll('.item-row').forEach(row => {
        const qtyIn = row.querySelector('.qty-input');
        if (!qtyIn || qtyIn.disabled) return;

        const qty = parseFloat(qtyIn.value) || 0;
        const stock = parseFloat(row.dataset.stock) || 0;
        const cost = parseFloat(row.dataset.cost) || 0;
        const name = row.querySelector('.product-label')?.textContent?.trim() || '—';

        if (qty <= 0) {
            skipped++;
            return;
        }

        const line = qty * cost;
        grand += line;

        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td class="fw-semibold">${escapeHtml(name)}</td>
                <td class="text-center text-muted">${formatQtyDisplay(stock)}</td>
                <td class="text-center fw-bold">${formatQtyDisplay(qty)}</td>
                <td class="text-end">₱${line.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
            </tr>`);
    });

    document.getElementById('reviewModalGrandTotal').textContent =
        '₱' + grand.toLocaleString('en-PH', { minimumFractionDigits: 2 });

    if (skipped > 0) {
        skippedAlert.innerHTML = `<i class="bi bi-info-circle me-1"></i>
            <strong>${skipped}</strong> product line(s) have no quantity and will <strong>not</strong> be on this delivery.`;
        skippedAlert.classList.remove('d-none');
    } else {
        skippedAlert.classList.add('d-none');
    }
}

document.getElementById('submitBtn').addEventListener('click', function () {
    if (!validateForReview()) return;
    buildReviewModal();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('deliveryReviewModal')).show();
});

document.getElementById('confirmDeliverySubmit').addEventListener('click', function () {
    formDirty = false;
    document.getElementById('transferForm').submit();
});

// Init
initTable();
</script>
@endpush