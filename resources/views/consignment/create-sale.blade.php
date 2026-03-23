@extends('layouts.app')
@section('title', 'Record Sales — ' . ($receivable->dr_number ?? 'DR #' . $receivable->id))
@section('content')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-receipt"></i> Record Sales —
            <span class="font-monospace fw-bold">
                {{ $receivable->dr_number ?? '#' . $receivable->id }}
            </span>
            | {{ $receivable->branch->name }}
        </span>
        <a href="{{ route('consignment.show', $receivable) }}"
           class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    <div class="card-body">

        @if($errors->any())
            <div class="alert alert-danger mb-3">
                <ul class="mb-0">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('consignment.sale.store', $receivable) }}"
              method="POST" id="saleForm">
            @csrf

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        Sale period from <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="sale_date_from"
                           class="form-control @error('sale_date_from') is-invalid @enderror"
                           value="{{ old('sale_date_from', date('Y-m-d')) }}" required>
                    @error('sale_date_from')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        Sale period to <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="sale_date_to"
                           class="form-control @error('sale_date_to') is-invalid @enderror"
                           value="{{ old('sale_date_to', date('Y-m-d')) }}" required>
                    @error('sale_date_to')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Same as “from” for a single day; use a range for weekly totals.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Notes (optional)</label>
                    <input type="text" name="notes"
                           class="form-control"
                           value="{{ old('notes') }}"
                           placeholder="e.g. Weekend sales, daily remittance">
                </div>
            </div>

            <div class="alert alert-info small py-2 mb-3">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Tip:</strong> Leave quantity blank or enter <strong>0</strong> to skip a product — it will
                <strong>not</strong> be saved. You’ll review all lines with quantity before saving.
            </div>
            <div class="alert alert-light border small py-2 mb-3">
                <i class="bi bi-cash-coin text-success me-1"></i>
                <strong>Remittance:</strong> On the review screen, enter <strong>cash remitted</strong> for this sale
                (normally the same as <strong>total sales</strong>). That cash is what the branch turns in to the
                warehouse for this entry.
            </div>

            <div id="saleReviewErrorBanner" class="alert alert-danger d-none mb-3"></div>

            {{-- Products Table --}}
            <div class="table-responsive mb-4"
                 style="max-height:400px; overflow-y:auto;">
                <table class="table table-sm mb-0" style="min-width:700px;">
                    <thead style="position:sticky; top:0; background:#004d3b;">
                        <tr>
                            <th style="padding:10px 14px; color:#fff; font-weight:500; font-size:0.82rem; border:none; width:30%;">Product</th>
                            <th style="padding:10px 14px; color:#fff; font-weight:500; font-size:0.82rem; border:none; width:15%;">Branch Stock</th>
                            <th style="padding:10px 14px; color:#fff; font-weight:500; font-size:0.82rem; border:none; width:15%;">Unit Price</th>
                            <th style="padding:10px 14px; color:#fff; font-weight:500; font-size:0.82rem; border:none; width:15%;">Qty Sold</th>
                            <th style="padding:10px 14px; color:#fff; font-weight:500; font-size:0.82rem; border:none; width:15%;">Total</th>
                            <th style="padding:10px 14px; border:none; width:5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="saleBody"></tbody>
                    <tfoot>
                        <tr style="background:#f0fdf4; border-top:2px solid #007A5E;">
                            <th colspan="4" style="padding:10px 14px; text-align:right; border:none;">
                                Total Sales Amount:
                            </th>
                            <th style="padding:10px 14px; color:#007A5E; font-size:1rem; border:none;"
                                id="grandTotal">₱0.00</th>
                            <th style="border:none;"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <input type="hidden" name="remitted_amount" id="formRemittedAmount" value="0">
            <input type="hidden" name="remittance_reference" id="formRemittanceReference" value="">

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" id="submitSaleBtn">
                    <i class="bi bi-check-lg"></i> Review &amp; confirm sales
                </button>
                <a href="{{ route('consignment.show', $receivable) }}"
                   class="btn btn-secondary">Cancel</a>
            </div>
        </form>

        <div class="modal fade" id="consignmentSaleReviewModal" tabindex="-1"
             aria-labelledby="consignmentSaleReviewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="consignmentSaleReviewModalLabel">
                            <i class="bi bi-clipboard-check text-success"></i> Review recorded sales
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-2">
                            Only products with quantity <strong>&gt; 0</strong> will be saved and deducted from branch stock.
                            Lines with <strong>0</strong> or blank quantity are <strong>not</strong> included.
                        </p>
                        <div id="consignmentSaleSkippedAlert" class="alert alert-warning small py-2 d-none"></div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Branch stock</th>
                                        <th class="text-center">Qty sold</th>
                                        <th class="text-end">Unit price</th>
                                        <th class="text-end">Line total</th>
                                    </tr>
                                </thead>
                                <tbody id="consignmentSaleReviewTableBody"></tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end">Total sales</th>
                                        <th class="text-end text-success" id="consignmentSaleReviewGrandTotal">₱0.00</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-2">
                            <i class="bi bi-cash-stack text-success"></i> Cash remittance (same entry)
                        </h6>
                        <p class="text-muted small mb-3">
                            Record the cash the branch is remitting for <strong>this sale</strong>. Normally this matches
                            <strong>total sales</strong> above. The remittance is dated <strong>sale period to</strong>
                            (end of the range).
                        </p>
                        <div class="row g-2 mb-2">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold mb-0">Cash remitted (₱)</label>
                                <input type="number" class="form-control" id="modalRemittedAmount"
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold mb-0">Reference (optional)</label>
                                <input type="text" class="form-control" id="modalRemittanceRef"
                                       maxlength="100" placeholder="e.g. OR number, deposit slip">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-pencil"></i> Back to edit
                        </button>
                        <button type="button" class="btn btn-primary" id="confirmConsignmentSaleSubmit">
                            <i class="bi bi-check-lg"></i> Save sales
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
const deliveredProducts = @json($deliveredProducts);

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

function updateGrandTotal() {
    let total = 0;
    document.querySelectorAll('.sale-row:not(.d-none)').forEach(row => {
        total += parseFloat(row.querySelector('.line-total').dataset.value || 0);
    });
    document.getElementById('grandTotal').textContent =
        '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function buildRow(p, index) {
    const stockColor = p.branch_stock <= 0 ? '#dc2626' : '#007A5E';
    const rowBg      = index % 2 === 0 ? '#ffffff' : '#f9fafb';
    return `<tr class="sale-row" data-id="${p.id}" data-branch-stock="${parseFloat(p.branch_stock)}"
                style="background:${rowBg}; border-bottom:1px solid #e5e7eb;">
        <td style="padding:8px 14px; vertical-align:middle; border:none; font-weight:600; font-size:0.88rem;">
            ${p.name}
            <input type="hidden" name="items[${index}][finished_product_id]" value="${p.id}">
        </td>
        <td style="padding:8px 14px; vertical-align:middle; border:none;">
            <span style="font-weight:600; font-size:0.85rem; color:${stockColor};">
                ${parseFloat(p.branch_stock).toFixed(2)} pcs
            </span>
        </td>
        <td style="padding:6px 14px; vertical-align:middle; border:none;">
            <input type="number" name="items[${index}][unit_price]"
                   class="form-control form-control-sm unit-price"
                   style="max-width:100px; border-radius:8px;"
                   step="0.01" min="0"
                   value="${parseFloat(p.selling_price).toFixed(2)}">
        </td>
        <td style="padding:6px 14px; vertical-align:middle; border:none;">
            <input type="number" name="items[${index}][qty_sold]"
                   class="form-control form-control-sm qty-sold"
                   style="max-width:100px; border-radius:8px;"
                   step="0.0001" min="0" placeholder="0"
                   data-index="${index}">
        </td>
        <td style="padding:8px 14px; vertical-align:middle; border:none;">
            <span class="line-total fw-semibold"
                  style="font-size:0.85rem; color:#6b7280;"
                  data-value="0">—</span>
        </td>
        <td style="padding:8px 14px; vertical-align:middle; text-align:center; border:none;">
            <button type="button" class="btn btn-sm btn-outline-danger remove-sale-row"
                    style="padding:3px 7px; border-radius:6px;">
                <i class="bi bi-x-circle"></i>
            </button>
        </td>
    </tr>`;
}

function initTable() {
    const body = document.getElementById('saleBody');
    body.innerHTML = '';
    deliveredProducts.forEach((p, i) => {
        body.insertAdjacentHTML('beforeend', buildRow(p, i));
    });
    updateGrandTotal();
}

function calcLine(row) {
    const qty   = parseFloat(row.querySelector('.qty-sold').value)   || 0;
    const price = parseFloat(row.querySelector('.unit-price').value) || 0;
    const total = qty * price;
    const el    = row.querySelector('.line-total');
    el.textContent   = total > 0
        ? '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2 })
        : '—';
    el.dataset.value = total;
    el.style.color   = total > 0 ? '#007A5E' : '#6b7280';
    updateGrandTotal();
}

document.getElementById('saleBody').addEventListener('input', function(e) {
    if (e.target.classList.contains('qty-sold') ||
        e.target.classList.contains('unit-price')) {
        calcLine(e.target.closest('.sale-row'));
    }
});

document.getElementById('saleBody').addEventListener('keydown', function(e) {
    if (e.target.classList.contains('qty-sold') && e.key === 'Enter') {
        e.preventDefault();
        const inputs = Array.from(
            document.querySelectorAll('.sale-row:not(.d-none) .qty-sold:not(:disabled)')
        );
        const idx = inputs.indexOf(e.target);
        if (idx !== -1 && idx < inputs.length - 1) {
            inputs[idx + 1].focus();
            inputs[idx + 1].select();
        }
    }
});

document.getElementById('saleBody').addEventListener('click', function(e) {
    const btn = e.target.closest('.remove-sale-row');
    if (btn) {
        const row = btn.closest('.sale-row');
        row.querySelectorAll('input').forEach(i => i.disabled = true);
        row.classList.add('d-none');
        updateGrandTotal();
    }
});

function validateSaleForReview() {
    const banner = document.getElementById('saleReviewErrorBanner');
    if (banner) {
        banner.classList.add('d-none');
        banner.innerHTML = '';
    }

    const errors = [];
    let hasPositiveQty = false;

    document.querySelectorAll('.sale-row:not(.d-none)').forEach(row => {
        const qtyIn = row.querySelector('.qty-sold:not(:disabled)');
        const priceIn = row.querySelector('.unit-price:not(:disabled)');
        if (!qtyIn || !priceIn) return;

        const qty = parseFloat(qtyIn.value) || 0;
        const stock = parseFloat(row.dataset.branchStock) || 0;
        const price = parseFloat(priceIn.value);
        const name = row.querySelector('td')?.childNodes[0]?.textContent?.trim() || 'Product';

        if (qty > 0) {
            hasPositiveQty = true;
            if (priceIn.value === '' || Number.isNaN(price) || price < 0) {
                errors.push(`Enter a valid unit price for ${name}.`);
            }
            if (qty > stock) {
                errors.push(`${name}: quantity sold (${formatQtyDisplay(qty)}) exceeds branch stock (${formatQtyDisplay(stock)}).`);
            }
        }
    });

    if (!hasPositiveQty) {
        errors.push('Enter a quantity greater than zero for at least one product.');
    }

    const fromEl = document.querySelector('input[name="sale_date_from"]');
    const toEl = document.querySelector('input[name="sale_date_to"]');
    if (fromEl && toEl && fromEl.value && toEl.value && fromEl.value > toEl.value) {
        errors.push('Sale period “from” must be on or before “to”.');
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

function buildConsignmentSaleReviewModal() {
    const tbody = document.getElementById('consignmentSaleReviewTableBody');
    const skippedAlert = document.getElementById('consignmentSaleSkippedAlert');
    const grandEl = document.getElementById('consignmentSaleReviewGrandTotal');
    if (!tbody) return;

    tbody.innerHTML = '';
    let skipped = 0;
    let grand = 0;

    document.querySelectorAll('.sale-row:not(.d-none)').forEach(row => {
        const qtyIn = row.querySelector('.qty-sold:not(:disabled)');
        if (!qtyIn) return;

        const qty = parseFloat(qtyIn.value) || 0;
        const price = parseFloat(row.querySelector('.unit-price')?.value) || 0;
        const stock = parseFloat(row.dataset.branchStock) || 0;
        const name = row.querySelector('td')?.childNodes[0]?.textContent?.trim() || '—';

        if (qty <= 0) {
            skipped++;
            return;
        }

        const line = qty * price;
        grand += line;

        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td class="fw-semibold">${escapeHtml(name)}</td>
                <td class="text-center text-muted">${formatQtyDisplay(stock)}</td>
                <td class="text-center fw-bold">${formatQtyDisplay(qty)}</td>
                <td class="text-end">₱${price.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 4 })}</td>
                <td class="text-end">₱${line.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            </tr>`);
    });

    if (grandEl) {
        grandEl.textContent = '₱' + grand.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const remitInput = document.getElementById('modalRemittedAmount');
    if (remitInput) {
        remitInput.value = grand > 0 ? grand.toFixed(2) : '';
    }

    if (skippedAlert) {
        if (skipped > 0) {
            skippedAlert.innerHTML = `<i class="bi bi-info-circle me-1"></i>
                <strong>${skipped}</strong> product line(s) have no quantity and will <strong>not</strong> be saved on this sale.`;
            skippedAlert.classList.remove('d-none');
        } else {
            skippedAlert.classList.add('d-none');
            skippedAlert.innerHTML = '';
        }
    }
}

document.getElementById('submitSaleBtn').addEventListener('click', function() {
    if (!validateSaleForReview()) return;
    buildConsignmentSaleReviewModal();
    bootstrap.Modal.getOrCreateInstance(
        document.getElementById('consignmentSaleReviewModal')
    ).show();
});

document.getElementById('confirmConsignmentSaleSubmit').addEventListener('click', function() {
    const raw = document.getElementById('modalRemittedAmount')?.value;
    const amt = raw === '' || raw === undefined ? 0 : parseFloat(raw);
    const safe = Number.isFinite(amt) && amt >= 0 ? amt : 0;
    document.getElementById('formRemittedAmount').value = String(safe);
    document.getElementById('formRemittanceReference').value =
        document.getElementById('modalRemittanceRef')?.value?.trim() || '';
    document.getElementById('saleForm').submit();
});

initTable();
</script>
@endpush