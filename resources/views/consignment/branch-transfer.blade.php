@extends('layouts.app')
@section('title', 'Transfer Products — '.$branch->name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1"><i class="bi bi-arrow-left-right"></i> Transfer Products (Branch → Branch)</h5>
        <small class="text-muted">From <strong>{{ $branch->name }}</strong> — creates a <strong>new DR</strong> for the destination branch.</small>
    </div>
    <a href="{{ route('consignment.branch', $branch) }}" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

@if($destinationBranches->isEmpty())
    <div class="alert alert-warning">
        There is no other active branch to receive stock. Add another branch first.
    </div>
@elseif($inventory->isEmpty())
    <div class="alert alert-warning">
        This branch has no on-hand finished products to transfer.
    </div>
@else
<form id="branchTransferForm" action="{{ route('consignment.branch-transfer.store', $branch) }}" method="POST">
    @csrf

    <div class="card mb-3">
        <div class="card-header fw-semibold">Delivery details</div>
        <div class="card-body row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">New DR #</label>
                <input type="text" name="dr_number" class="form-control @error('dr_number') is-invalid @enderror"
                       value="{{ old('dr_number') }}" placeholder="e.g. DR-B2B-001">
                @error('dr_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Transfer date <span class="text-danger">*</span></label>
                <input type="date" name="transfer_date" class="form-control @error('transfer_date') is-invalid @enderror"
                       value="{{ old('transfer_date', date('Y-m-d')) }}" required>
                @error('transfer_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Destination branch <span class="text-danger">*</span></label>
                <select name="destination_branch_id" class="form-select @error('destination_branch_id') is-invalid @enderror" required>
                    <option value="">— Select branch —</option>
                    @foreach($destinationBranches as $b)
                        <option value="{{ $b->id }}" @selected(old('destination_branch_id') == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
                @error('destination_branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="2"
                          placeholder="e.g. Stock balancing, branch request, slow-moving transfer..."
                          required>{{ old('reason') }}</textarea>
                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header fw-semibold d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span>Products & quantities</span>
        </div>
        <div class="card-body border-bottom py-3">
            <label for="transferProductSearch" class="form-label fw-semibold small mb-1">Search products</label>
            <div class="input-group" style="max-width: 420px;">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="search" id="transferProductSearch" class="form-control border-start-0"
                       placeholder="Type to filter by product name..."
                       autocomplete="off">
                <button type="button" class="btn btn-outline-secondary" id="transferProductSearchClear" title="Clear search">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="form-text" id="transferProductSearchHint"></div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="transferProductsTable">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Available</th>
                        <th style="width:160px;">Qty to transfer</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($inventory as $idx => $inv)
                    @php $fp = $inv->finishedProduct; @endphp
                    <tr class="transfer-product-row" data-search-text="{{ Str::lower($fp->name) }}">
                        <td class="fw-semibold transfer-product-name">{{ $fp->name }}</td>
                        <td class="text-center text-muted">{{ qty_fmt($inv->stock_quantity) }} pcs</td>
                        <td>
                            <input type="hidden" name="items[{{ $idx }}][finished_product_id]" value="{{ $inv->finished_product_id }}">
                            <input type="number" name="items[{{ $idx }}][quantity]"
                                   class="form-control form-control-sm transfer-qty-input"
                                   min="0" step="any"
                                   max="{{ $inv->stock_quantity }}"
                                   value="{{ old('items.'.$idx.'.quantity', '0') }}"
                                   placeholder="0"
                                   inputmode="decimal">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">
            Enter quantity only for lines you want to include. At least one line must be &gt; 0.
        </div>
    </div>

    @error('items')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <button type="submit" class="btn btn-primary btn-action">
        <i class="bi bi-check-lg"></i> Transfer &amp; create DR
    </button>
</form>
@endif

@endsection

@push('scripts')
<script>
(function () {
    const searchInput = document.getElementById('transferProductSearch');
    const searchClear = document.getElementById('transferProductSearchClear');
    const searchHint = document.getElementById('transferProductSearchHint');
    const rows = document.querySelectorAll('.transfer-product-row');
    const qtyInputs = document.querySelectorAll('.transfer-qty-input');

    if (!searchInput || !rows.length) return;

    function visibleQtyInputs() {
        return Array.from(qtyInputs).filter(function (inp) {
            const tr = inp.closest('.transfer-product-row');
            return tr && !tr.classList.contains('d-none');
        });
    }

    function applySearch() {
        const q = searchInput.value.trim().toLowerCase();
        let shown = 0;
        rows.forEach(function (row) {
            const text = (row.dataset.searchText || '').toLowerCase();
            const match = q === '' || text.includes(q);
            row.classList.toggle('d-none', !match);
            if (match) shown++;
        });
        if (searchHint) {
            searchHint.textContent = q
                ? (shown ? shown + ' product(s) match.' : 'No products match — try another term.')
                : '';
        }
    }

    searchInput.addEventListener('input', applySearch);
    searchClear.addEventListener('click', function () {
        searchInput.value = '';
        applySearch();
        searchInput.focus();
    });

    qtyInputs.forEach(function (input) {
        input.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();

            const visible = visibleQtyInputs();
            const idx = visible.indexOf(input);

            if (idx >= 0 && idx < visible.length - 1) {
                const next = visible[idx + 1];
                next.focus();
                if (typeof next.select === 'function') next.select();
            } else {
                const submitBtn = document.querySelector('#branchTransferForm button[type="submit"]');
                if (submitBtn) submitBtn.focus();
            }
        });
    });
})();
</script>
@endpush
