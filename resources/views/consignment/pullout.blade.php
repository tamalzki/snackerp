@extends('layouts.app')
@section('title', 'Pull Out — ' . ($receivable->dr_number ?? 'DR #' . $receivable->id))
@section('content')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-arrow-return-left"></i> Pull Out —
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

        <div class="alert alert-warning mb-4">
            <i class="bi bi-info-circle me-1"></i>
            Pulled out products will be <strong>returned to warehouse stock</strong>
            and the <strong>receivable balance will be reduced</strong> accordingly.
        </div>

        <form action="{{ route('consignment.pullout.store', $receivable) }}"
              method="POST" id="pulloutForm">
            @csrf

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        Pull Out Date <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="pullout_date"
                           class="form-control"
                           value="{{ old('pullout_date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        Reason <span class="text-danger">*</span>
                    </label>
                    <select name="reason" class="form-select" required>
                        <option value="">-- Select Reason --</option>
                        <option value="expired"  {{ old('reason') == 'expired' ? 'selected' : '' }}>
                            ❌ Expired
                        </option>
                        <option value="bo"       {{ old('reason') == 'bo' ? 'selected' : '' }}>
                            📦 Bad Order (BO)
                        </option>
                        <option value="other"    {{ old('reason') == 'other' ? 'selected' : '' }}>
                            🔄 Other
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Notes (optional)</label>
                    <input type="text" name="notes"
                           class="form-control"
                           value="{{ old('notes') }}"
                           placeholder="Additional details">
                </div>
            </div>

            {{-- Products Table --}}
            <div class="table-responsive mb-4"
                 style="max-height:400px; overflow-y:auto;">
                <table class="table table-sm mb-0" style="min-width:650px;">
                    <thead style="position:sticky; top:0; background:#004d3b;">
                        <tr>
                            <th style="padding:10px 14px; color:#fff; font-weight:500; font-size:0.82rem; border:none; width:35%;">Product</th>
                            <th style="padding:10px 14px; color:#fff; font-weight:500; font-size:0.82rem; border:none; width:20%;">Branch Stock</th>
                            <th style="padding:10px 14px; color:#fff; font-weight:500; font-size:0.82rem; border:none; width:20%;">Qty to Pull Out</th>
                            <th style="padding:10px 14px; color:#fff; font-weight:500; font-size:0.82rem; border:none; width:20%;">Return Value</th>
                            <th style="padding:10px 14px; border:none; width:5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="pulloutBody"></tbody>
                    <tfoot>
                        <tr style="background:#fff8e1; border-top:2px solid #d97706;">
                            <th colspan="3" style="padding:10px 14px; text-align:right; border:none;">
                                Total Return Value:
                            </th>
                            <th style="padding:10px 14px; color:#d97706; font-size:1rem; border:none;"
                                id="pulloutTotal">₱0.00</th>
                            <th style="border:none;"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div id="pulloutErrorBanner" class="alert alert-danger d-none mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Please enter quantity for at least one product to pull out.
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-warning" id="submitPulloutBtn">
                    <i class="bi bi-arrow-return-left"></i> Confirm Pull Out
                </button>
                <a href="{{ route('consignment.show', $receivable) }}"
                   class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
@push('scripts')
<script>
const pulloutProducts = @json($pulloutProducts);

function updatePulloutTotal() {
    let total = 0;
    document.querySelectorAll('.pullout-row:not(.d-none)').forEach(row => {
        total += parseFloat(row.querySelector('.return-value').dataset.value || 0);
    });
    document.getElementById('pulloutTotal').textContent =
        '₱' + total.toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

function buildRow(p, index) {
    const stockColor = p.branch_stock <= 0 ? '#dc2626' : '#007A5E';
    const rowBg      = index % 2 === 0 ? '#ffffff' : '#f9fafb';
    return `<tr class="pullout-row" data-id="${p.id}"
                data-price="${p.selling_price}"
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
            <input type="number" name="items[${index}][quantity]"
                   class="form-control form-control-sm pullout-qty"
                   style="max-width:100px; border-radius:8px;"
                   step="1" min="0" placeholder="0"
                   data-index="${index}">
        </td>
        <td style="padding:8px 14px; vertical-align:middle; border:none;">
            <span class="return-value fw-semibold"
                  style="font-size:0.85rem; color:#6b7280;"
                  data-value="0">—</span>
        </td>
        <td style="padding:8px 14px; vertical-align:middle; text-align:center; border:none;">
            <button type="button" class="btn btn-sm btn-outline-danger remove-pullout-row"
                    style="padding:3px 7px; border-radius:6px;">
                <i class="bi bi-x-circle"></i>
            </button>
        </td>
    </tr>`;
}

function initTable() {
    const body = document.getElementById('pulloutBody');
    body.innerHTML = '';
    pulloutProducts.forEach((p, i) => {
        body.insertAdjacentHTML('beforeend', buildRow(p, i));
    });
    updatePulloutTotal();
}

document.getElementById('pulloutBody').addEventListener('input', function(e) {
    if (e.target.classList.contains('pullout-qty')) {
        const row   = e.target.closest('.pullout-row');
        const qty   = parseFloat(e.target.value) || 0;
        const price = parseFloat(row.dataset.price) || 0;
        const val   = qty * price;
        const el    = row.querySelector('.return-value');
        el.textContent   = val > 0
            ? '₱' + val.toLocaleString('en-PH', { minimumFractionDigits: 2 })
            : '—';
        el.dataset.value = val;
        el.style.color   = val > 0 ? '#d97706' : '#6b7280';
        updatePulloutTotal();
    }
});

document.getElementById('pulloutBody').addEventListener('keydown', function(e) {
    if (e.target.classList.contains('pullout-qty') && e.key === 'Enter') {
        e.preventDefault();
        const inputs = Array.from(
            document.querySelectorAll('.pullout-row:not(.d-none) .pullout-qty:not(:disabled)')
        );
        const idx = inputs.indexOf(e.target);
        if (idx !== -1 && idx < inputs.length - 1) {
            inputs[idx + 1].focus();
            inputs[idx + 1].select();
        }
    }
});

document.getElementById('pulloutBody').addEventListener('click', function(e) {
    const btn = e.target.closest('.remove-pullout-row');
    if (btn) {
        const row = btn.closest('.pullout-row');
        row.querySelectorAll('input').forEach(i => i.disabled = true);
        row.classList.add('d-none');
        updatePulloutTotal();
    }
});

document.getElementById('submitPulloutBtn').addEventListener('click', function() {
    const hasItem = Array.from(
        document.querySelectorAll('.pullout-row:not(.d-none) .pullout-qty:not(:disabled)')
    ).some(i => parseFloat(i.value) > 0);

    const banner = document.getElementById('pulloutErrorBanner');
    if (!hasItem) {
        banner.classList.remove('d-none');
        banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    banner.classList.add('d-none');
    document.getElementById('pulloutForm').submit();
});

initTable();
</script>
@endpush