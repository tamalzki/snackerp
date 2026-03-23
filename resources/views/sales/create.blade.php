@extends('layouts.app')
@section('title', 'Record Sale')
@section('content')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-receipt"></i> Record Sale</span>
        @if($selectedBranch)
            <span class="badge bg-success">
                <i class="bi bi-shop"></i> {{ $selectedBranch->name }}
            </span>
        @endif
    </div>
    <div class="card-body">
        <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
            @csrf

            <div class="row g-3 mb-4">

                {{-- Branch --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Branch</label>
                    @if($selectedBranch)
                        <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
                        <div class="form-control bg-light d-flex align-items-center gap-2"
                             style="cursor: not-allowed;">
                            <i class="bi bi-shop text-success"></i>
                            <span class="fw-semibold">{{ $selectedBranch->name }}</span>
                        </div>
                        <div class="form-text">
                            <i class="bi bi-lock"></i> Branch locked.
                            <a href="{{ route('sales.create') }}">Choose different branch</a>
                        </div>
                    @else
                        <select name="branch_id"
        class="form-select @error('branch_id') is-invalid @enderror"
        onchange="window.location.href='{{ route('sales.create') }}?branch_id=' + this.value"
        required>
                            <option value="">-- Select Branch --</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}"
                                    {{ old('branch_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            Select a branch to load its available stock.
                        </div>
                        @error('branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    @endif
                </div>

                {{-- Sale Date --}}
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sale Date</label>
                    <input type="date" name="sale_date"
                           class="form-control @error('sale_date') is-invalid @enderror"
                           value="{{ old('sale_date', date('Y-m-d')) }}" required>
                    @error('sale_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Notes --}}
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Notes (optional)</label>
                    <input type="text" name="notes" class="form-control"
                           value="{{ old('notes') }}"
                           placeholder="e.g. Walk-in customer, bulk order">
                </div>

            </div>

            @if($selectedBranch)

                @if($branchInventory->isEmpty())
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        No stock available in <strong>{{ $selectedBranch->name }}</strong>.
                        <a href="{{ route('transfers.create', ['branch_id' => $selectedBranch->id]) }}">
                            Transfer stock to this branch first.
                        </a>
                    </div>
                @else

                {{-- Stock warning --}}
                <div id="stockWarning" class="alert alert-danger d-none mb-3">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Cannot proceed:</strong>
                    <span id="stockWarningMsg"></span>
                </div>

                <div class="alert alert-info small py-2 mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Tip:</strong> Use quantity <strong>0</strong> or leave blank to skip a line — it will
                    <strong>not</strong> be saved on the sale. You’ll review lines with quantity before saving.
                </div>

                <div id="saleReviewErrorBanner" class="alert alert-danger d-none mb-3"></div>

                {{-- Items Table --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-bag-check"></i> Items Sold
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRow">
                        <i class="bi bi-plus"></i> Add Item
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width:28%">Product</th>
                                <th style="width:13%">Branch Stock</th>
                                <th style="width:10%">Unit</th>
                                <th style="width:13%">Qty Sold</th>
                                <th style="width:13%">Unit Price (₱)</th>
                                <th style="width:12%">Cost Snapshot</th>
                                <th style="width:10%">Total</th>
                                <th style="width:5%"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            {{-- Rendered by JS --}}
                        </tbody>
                    </table>
                </div>

                {{-- Summary --}}
                <div class="row justify-content-end mb-4">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Total Sales:</span>
                                    <span class="fw-bold text-success">
                                        ₱<span id="totalSales">0.00</span>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted">Total Cost:</span>
                                    <span class="fw-bold">
                                        ₱<span id="totalCost">0.00</span>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mt-1 border-top pt-1">
                                    <span class="text-muted">Gross Profit:</span>
                                    <span class="fw-bold text-primary">
                                        ₱<span id="grossProfit">0.00</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-check-lg"></i> Review &amp; confirm sale
                    </button>
                    <a href="{{ route('sales.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>

                @endif
            @else
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Please select a branch above to load available products for sale.
                </div>
            @endif

        </form>

        @if($selectedBranch && $branchInventory->isNotEmpty())
        <div class="modal fade" id="saleReviewModal" tabindex="-1" aria-labelledby="saleReviewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="saleReviewModalLabel">
                            <i class="bi bi-clipboard-check text-success"></i> Review sale
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-2">
                            Only lines with quantity <strong>&gt; 0</strong> are recorded. Products with
                            <strong>0</strong> or blank quantity are <strong>not</strong> included in this sale.
                        </p>
                        <div id="saleReviewSkippedAlert" class="alert alert-warning small py-2 d-none"></div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Branch stock</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Unit price</th>
                                        <th class="text-end">Line total</th>
                                    </tr>
                                </thead>
                                <tbody id="saleReviewTableBody"></tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end">Total sales</th>
                                        <th class="text-end text-success" id="saleReviewTotalSales">₱0.00</th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-end">Total cost</th>
                                        <th class="text-end" id="saleReviewTotalCost">₱0.00</th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-end">Gross profit</th>
                                        <th class="text-end text-primary" id="saleReviewGrossProfit">₱0.00</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-pencil"></i> Back to edit
                        </button>
                        <button type="button" class="btn btn-primary" id="confirmSaleSubmit">
                            <i class="bi bi-check-lg"></i> Save sale
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection
@push('scripts')
<script>
const branchProducts = @json($branchProducts);

let rowIndex = 0;

function escapeHtml(s) {
    if (s == null) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function formatQtyDisplay(q) {
    const n = Number(q);
    if (!Number.isFinite(n)) return '0';
    return Number.isInteger(n) ? String(n) : n.toFixed(4).replace(/\.?0+$/, '');
}

function hasAtLeastOneSaleLine() {
    for (const row of document.querySelectorAll('.item-row')) {
        const sel = row.querySelector('.product-select');
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const priceInput = row.querySelector('.price-input');
        const price = parseFloat(priceInput.value);
        if (!sel?.value || qty <= 0) continue;
        if (priceInput.value === '' || Number.isNaN(price) || price < 0) continue;
        return true;
    }
    return false;
}

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.product-select'))
        .map(s => s.value).filter(v => v !== '');
}

function buildOptions(currentValue = '') {
    const selected = getSelectedIds();
    return `<option value="">-- Select Product --</option>` +
        branchProducts.map(p => {
            const isSelected = String(p.id) === String(currentValue);
            const isDisabled = selected.includes(String(p.id)) && !isSelected;
            return `<option value="${p.id}"
                            data-stock="${p.stock}"
                            data-cost="${p.cost_snapshot}"
                            data-price="${p.selling_price}"
                            ${isSelected ? 'selected' : ''}
                            ${isDisabled ? 'disabled' : ''}>
                        ${p.name} — ${parseFloat(p.stock).toFixed(2)} in branch
                    </option>`;
        }).join('');
}

function refreshAllSelects() {
    document.querySelectorAll('.product-select').forEach(sel => {
        const cur = sel.value;
        sel.innerHTML = buildOptions(cur);
    });
}

function buildRow(index) {
    return `<tr class="item-row" data-index="${index}">
        <td>
            <select name="items[${index}][finished_product_id]"
                    class="form-select product-select">
                ${buildOptions()}
            </select>
        </td>
        <td>
            <span class="stock-label small fw-semibold text-muted"
                  data-stock="0">—</span>
        </td>
        <td>
            <span class="unit-label text-muted small">pcs</span>
        </td>
        <td>
            <input type="number" name="items[${index}][quantity]"
                   class="form-control qty-input"
                   step="0.0001" min="0" placeholder="0">
        </td>
        <td>
            <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" name="items[${index}][unit_price]"
                       class="form-control price-input"
                       step="0.0001" min="0" placeholder="0.00">
            </div>
        </td>
        <td>
            <span class="cost-label text-muted small" data-cost="0">—</span>
        </td>
        <td class="fw-semibold line-total text-end">₱0.00</td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>`;
}

function initRows() {
    const body = document.getElementById('itemsBody');
    if (!body) return;
    body.innerHTML = '';
    body.insertAdjacentHTML('beforeend', buildRow(0));
    rowIndex = 1;
    refreshAllSelects();
}

function calcRow(row) {
    const qty   = parseFloat(row.querySelector('.qty-input').value) || 0;
    const price = parseFloat(row.querySelector('.price-input').value) || 0;
    const cost  = parseFloat(row.querySelector('.cost-label').dataset.cost || 0);
    const stock = parseFloat(row.querySelector('.stock-label').dataset.stock || 0);

    row.querySelector('.line-total').textContent = '₱' + (qty * price).toFixed(2);
    calcTotals();
    checkAllStock();
}

function calcTotals() {
    let totalSales = 0;
    let totalCost  = 0;

    document.querySelectorAll('.item-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const cost  = parseFloat(row.querySelector('.cost-label').dataset.cost || 0);
        totalSales += qty * price;
        totalCost  += qty * cost;
    });

    document.getElementById('totalSales').textContent  = totalSales.toFixed(2);
    document.getElementById('totalCost').textContent   = totalCost.toFixed(2);
    document.getElementById('grossProfit').textContent = (totalSales - totalCost).toFixed(2);
}

function checkAllStock() {
    const banner = document.getElementById('stockWarning');
    const msg    = document.getElementById('stockWarningMsg');
    const btn    = document.getElementById('submitBtn');
    if (!banner || !btn) return;

    const errors = [];

    document.querySelectorAll('.item-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('.qty-input').value) || 0;
        const stock = parseFloat(row.querySelector('.stock-label').dataset.stock || 0);
        const sel   = row.querySelector('.product-select');
        const name  = sel.options[sel.selectedIndex]?.text?.split('—')[0]?.trim() || 'Unknown';

        if (sel.value && qty > stock) {
            errors.push(`<strong>${escapeHtml(name)}</strong>: needs ${qty.toFixed(2)}, only ${stock.toFixed(2)} in branch.`);
        }
    });

    if (errors.length > 0) {
        banner.classList.remove('d-none');
        msg.innerHTML = '<ul class="mb-0 mt-1">' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
        btn.disabled  = true;
        btn.classList.replace('btn-primary', 'btn-secondary');
    } else {
        banner.classList.add('d-none');
        msg.innerHTML = '';
        btn.disabled  = false;
        btn.classList.replace('btn-secondary', 'btn-primary');
    }
}

function validateForReview() {
    const banner = document.getElementById('saleReviewErrorBanner');
    if (banner) {
        banner.classList.add('d-none');
        banner.innerHTML = '';
    }

    const errors = [];

    document.querySelectorAll('.item-row').forEach(row => {
        const sel = row.querySelector('.product-select');
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const priceInput = row.querySelector('.price-input');
        const price = parseFloat(priceInput.value);
        const stock = parseFloat(row.querySelector('.stock-label').dataset.stock || 0);
        const name = sel.options[sel.selectedIndex]?.text?.split('—')[0]?.trim() || 'Unknown';

        if (!sel.value && qty > 0) {
            errors.push('Select a product for every row with quantity greater than 0.');
        }
        if (sel.value && qty > 0) {
            if (priceInput.value === '' || Number.isNaN(price) || price < 0) {
                errors.push(`Enter a valid unit price for ${name}.`);
            }
            if (qty > stock) {
                errors.push(`${name}: quantity exceeds branch stock (${stock.toFixed(2)} available).`);
            }
        }
    });

    if (!hasAtLeastOneSaleLine()) {
        errors.push('Add at least one product with quantity greater than 0 and a valid unit price.');
    }

    const unique = [...new Set(errors)];
    if (unique.length > 0 && banner) {
        banner.innerHTML =
            '<i class="bi bi-exclamation-triangle-fill me-1"></i><ul class="mb-0 mt-1">' +
            unique.map(e => `<li>${escapeHtml(e)}</li>`).join('') +
            '</ul>';
        banner.classList.remove('d-none');
        banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }
    return true;
}

function buildReviewModal() {
    const tbody = document.getElementById('saleReviewTableBody');
    const skippedAlert = document.getElementById('saleReviewSkippedAlert');
    if (!tbody) return;

    tbody.innerHTML = '';

    let skipped = 0;
    let totalSales = 0;
    let totalCost = 0;

    document.querySelectorAll('.item-row').forEach(row => {
        const sel = row.querySelector('.product-select');
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const cost = parseFloat(row.querySelector('.cost-label').dataset.cost || 0);
        const stock = parseFloat(row.querySelector('.stock-label').dataset.stock || 0);
        const name = sel.options[sel.selectedIndex]?.text?.split('—')[0]?.trim() || '—';

        if (qty <= 0) {
            skipped++;
            return;
        }

        if (!sel.value) {
            return;
        }

        const lineTotal = qty * price;
        totalSales += lineTotal;
        totalCost += qty * cost;

        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td class="fw-semibold">${escapeHtml(name)}</td>
                <td class="text-center text-muted">${formatQtyDisplay(stock)}</td>
                <td class="text-center fw-bold">${formatQtyDisplay(qty)}</td>
                <td class="text-end">₱${price.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 4 })}</td>
                <td class="text-end">₱${lineTotal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            </tr>`);
    });

    const totalSalesEl = document.getElementById('saleReviewTotalSales');
    const totalCostEl = document.getElementById('saleReviewTotalCost');
    const grossEl = document.getElementById('saleReviewGrossProfit');
    if (totalSalesEl) {
        totalSalesEl.textContent = '₱' + totalSales.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    if (totalCostEl) {
        totalCostEl.textContent = '₱' + totalCost.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    if (grossEl) {
        grossEl.textContent = '₱' + (totalSales - totalCost).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    if (skippedAlert) {
        if (skipped > 0) {
            skippedAlert.innerHTML = `<i class="bi bi-info-circle me-1"></i>
                <strong>${skipped}</strong> line(s) have no quantity and will <strong>not</strong> be included in this sale.`;
            skippedAlert.classList.remove('d-none');
        } else {
            skippedAlert.classList.add('d-none');
            skippedAlert.innerHTML = '';
        }
    }
}

// Product selected
document.getElementById('itemsBody')?.addEventListener('change', function(e) {
    if (e.target.classList.contains('product-select')) {
        const row      = e.target.closest('.item-row');
        const selected = e.target.options[e.target.selectedIndex];
        const stock    = selected.dataset.stock || '0';
        const cost     = selected.dataset.cost  || '0';
        const price    = selected.dataset.price || '0';

        const stockLabel         = row.querySelector('.stock-label');
        stockLabel.textContent   = parseFloat(stock).toFixed(2) + ' pcs';
        stockLabel.dataset.stock = stock;
        stockLabel.className     = 'stock-label small fw-semibold ' +
            (parseFloat(stock) <= 0 ? 'text-danger' : 'text-success');

        row.querySelector('.cost-label').textContent   = '₱' + parseFloat(cost).toFixed(4);
        row.querySelector('.cost-label').dataset.cost  = cost;
        row.querySelector('.price-input').value        = parseFloat(price).toFixed(4);
        row.querySelector('.qty-input').value          = '';
        row.querySelector('.line-total').textContent   = '₱0.00';

        refreshAllSelects();
        calcTotals();
        checkAllStock();
    }
});

document.getElementById('itemsBody')?.addEventListener('input', function(e) {
    if (e.target.classList.contains('qty-input') ||
        e.target.classList.contains('price-input')) {
        calcRow(e.target.closest('.item-row'));
    }
});

document.getElementById('itemsBody')?.addEventListener('click', function(e) {
    if (e.target.closest('.remove-row')) {
        if (document.querySelectorAll('.item-row').length > 1) {
            e.target.closest('.item-row').remove();
            refreshAllSelects();
            calcTotals();
            checkAllStock();
        } else {
            alert('At least one item is required.');
        }
    }
});

document.getElementById('addRow')?.addEventListener('click', function() {
    document.getElementById('itemsBody')
        .insertAdjacentHTML('beforeend', buildRow(rowIndex));
    rowIndex++;
    refreshAllSelects();
    checkAllStock();
});

document.getElementById('submitBtn')?.addEventListener('click', function () {
    if (!validateForReview()) return;
    buildReviewModal();
    const el = document.getElementById('saleReviewModal');
    if (el) {
        bootstrap.Modal.getOrCreateInstance(el).show();
    }
});

document.getElementById('confirmSaleSubmit')?.addEventListener('click', function () {
    document.getElementById('saleForm')?.submit();
});

initRows();
</script>
@endpush