@extends('layouts.app')
@section('title', $title)

@section('content')

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show py-2 small mb-3" role="alert">
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@php
    $presetsForForm = match ($viewKey) {
        'expenses' => array_merge(
            \App\Support\DailyCashflowCategories::presetsForType('EXPENSES'),
            \App\Support\DailyCashflowCategories::presetsForType('PURCHASES'),
        ),
        default => \App\Support\DailyCashflowCategories::presetsForType($defaultType),
    };
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h5 mb-0 fw-bold" style="color:#0d47a1;">{{ $title }}</h1>
        <p class="text-muted small mb-0">
            @if($viewKey === 'savings')
                Savings and investments reduce <strong>total cash available</strong> the same way as on the daily sheet (cash moved out of the till / set aside).
            @elseif($viewKey === 'discretionary')
                Personal and <strong>sa balay</strong> (at-home) spending belongs here — not under fixed expenses.
            @else
                Each line is the same <strong>daily cash ledger</strong> record: edit here or on the day view — both stay in sync.
            @endif
        </p>
    </div>
    <div class="d-flex flex-wrap gap-1">
        <a href="{{ route('daily-cash.today') }}" class="btn btn-sm btn-outline-secondary">Daily ledger</a>
        <a href="{{ route('daily-cash.index', ['tab' => 'monthly', 'year' => $year]) }}" class="btn btn-sm btn-outline-secondary">Monthly</a>
        <a href="{{ route('daily-cash.index', ['tab' => 'annual']) }}" class="btn btn-sm btn-outline-secondary">Annual</a>
    </div>
</div>

{{-- Statement tabs --}}
<ul class="nav nav-pills flex-wrap gap-1 mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $viewKey === 'income' ? 'active' : '' }}" style="{{ $viewKey === 'income' ? 'background:#6f42c1;' : '' }}"
           href="{{ route('daily-cash.statements.income', ['year' => $year]) }}">Income Statement</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $viewKey === 'expenses' ? 'active' : '' }}" style="{{ $viewKey === 'expenses' ? 'background:#0d47a1;' : '' }}"
           href="{{ route('daily-cash.statements.expenses', ['year' => $year]) }}">Expenses Statement</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $viewKey === 'discretionary' ? 'active' : '' }}" style="{{ $viewKey === 'discretionary' ? 'background:#198754;' : '' }}"
           href="{{ route('daily-cash.statements.discretionary', ['year' => $year]) }}">Discretionary Statement</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $viewKey === 'savings' ? 'active' : '' }}" style="{{ $viewKey === 'savings' ? 'background:#6f42c1;' : '' }}"
           href="{{ route('daily-cash.statements.savings', ['year' => $year]) }}">Savings Statement</a>
    </li>
</ul>

<div class="d-flex align-items-center gap-2 mb-3">
    <span class="fw-bold small">Year:</span>
    @foreach($years as $y)
        <a href="{{ route('daily-cash.statements.'.$viewKey, ['year' => $y]) }}"
           class="btn btn-sm {{ $year == $y ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $y }}</a>
    @endforeach
</div>

<div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span class="fw-bold small text-uppercase"><i class="bi bi-list-ul text-success"></i> Line items ({{ $entries->count() }})</span>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#statementAddModal">
            <i class="bi bi-plus-lg"></i> Add entry
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle" style="font-size:0.84rem;">
                <thead>
                    <tr>
                        <th class="ps-3">Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end pe-3" style="width:200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        @php
                            $stmtPf = \App\Support\DailyCashflowCategories::entryToFormPreset($entry);
                            $stmtFormType = $entry->type;
                            if ($entry->type === 'INCOME' && ($entry->category ?? '') === \App\Support\DailyCashflowCategories::CASH_FROM_BANK) {
                                $stmtFormType = 'CASH_FROM_BANK';
                            }
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted">{{ $entry->day->date->format('M d, Y') }}</td>
                            <td>
                                <span class="badge bg-secondary" style="font-size:0.65rem;">{{ $entry->type }}</span>
                                <span class="small">{{ \App\Support\DailyCashflowCategories::label($entry) }}</span>
                            </td>
                            <td>{{ $entry->description }}</td>
                            <td class="text-end fw-semibold">₱{{ number_format($entry->amount, 2) }}</td>
                            <td class="text-end pe-2" style="white-space:nowrap;">
                                <button type="button"
                                        class="btn btn-outline-primary py-0 px-2 js-statement-edit-entry"
                                        style="font-size:0.75rem;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#statementEditModal"
                                        data-edit-url="{{ route('daily-cash.entries.update', [$entry->day, $entry]) }}"
                                        data-entry-type="{{ $stmtFormType }}"
                                        data-description="{{ $entry->description }}"
                                        data-amount="{{ number_format((float) $entry->amount, 2, '.', '') }}"
                                        data-preset="{{ $stmtPf['preset'] }}"
                                        data-custom="{{ $stmtPf['custom'] }}"
                                        data-subcategory="{{ $entry->subcategory_override ?? '' }}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <a href="{{ route('daily-cash.show', $entry->day) }}" class="btn btn-outline-secondary py-0 px-2" style="font-size:0.75rem;">
                                    <i class="bi bi-calendar3"></i> Day
                                </a>
                                <form method="POST" action="{{ route('daily-cash.entries.destroy', [$entry->day, $entry]) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this entry? Opening balances on later days in the period will be recalculated.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger py-0 px-2" style="font-size:0.75rem;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No entries for {{ $year }}. Use <strong>Add entry</strong>.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="statementAddModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('daily-cash.statements.store-entry') }}"
              onsubmit="return confirm('This will add a line to the daily ledger for the chosen date and may update opening balances on later days in the cash period. Continue?');">
            @csrf
            <input type="hidden" name="statement" value="{{ $viewKey }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Add entry — {{ $title }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Date</label>
                            <input type="date" name="entry_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        @if($viewKey === 'expenses')
                        <div class="col-12">
                            <label class="form-label small fw-bold">Expense type</label>
                            <select name="entry_type" id="stmtEntryType" class="form-select form-select-sm">
                                <option value="EXPENSES">Operating / fixed expenses</option>
                                <option value="PURCHASES">Purchases</option>
                            </select>
                        </div>
                        @endif
                        <div class="col-12">
                            <label class="form-label small fw-bold">Subcategory <span class="text-muted fw-normal">(optional)</span></label>
                            <select name="subcategory_key" id="stmtSubcategory" class="form-select form-select-sm"></select>
                            <div class="form-text small text-muted">Leave the default to skip — <strong>Uncategorized</strong> or description keywords apply; use the Monthly / Annual <strong>pencil</strong> to set buckets later.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Optional ledger tag</label>
                            <select name="category_preset" class="form-select form-select-sm" id="stmtCategoryPreset">
                                <option value="none">— Optional / uncategorized —</option>
                                @foreach($presetsForForm as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                                @if($viewKey === 'income')
                                    <option value="income_plus">Income + custom…</option>
                                @endif
                                @if($viewKey === 'discretionary')
                                    <option value="discretionary_plus">Discretionary + custom…</option>
                                @endif
                                @if($viewKey === 'savings')
                                    <option value="savings_plus">Savings + custom…</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-12 d-none" id="stmtCustomCategoryWrap">
                            <label class="form-label small fw-bold">Custom category name</label>
                            <input type="text" name="category_custom_piece" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <input type="text" name="description" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Amount (₱)</label>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control form-control-sm" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit: PUT daily-cash.entries.update — same row as daily ledger --}}
<div class="modal fade" id="statementEditModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="statementEditForm"
              onsubmit="return confirm('Update this daily ledger line? Closing balances and opening amounts on later days in the cash period will be recalculated. Continue?');">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Edit entry — {{ $title }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        @if($viewKey === 'income')
                            <div class="col-12">
                                <label class="form-label small fw-bold">Type</label>
                                <select name="type" id="stmtEditType" class="form-select form-select-sm" required>
                                    <option value="INCOME">Income</option>
                                    <option value="CASH_FROM_BANK">Cash from Bank — Withdrawals</option>
                                </select>
                            </div>
                        @elseif($viewKey === 'expenses')
                            <div class="col-12">
                                <label class="form-label small fw-bold">Type</label>
                                <select name="type" id="stmtEditType" class="form-select form-select-sm" required>
                                    <option value="EXPENSES">Operating / fixed expenses</option>
                                    <option value="PURCHASES">Purchases</option>
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="type" id="stmtEditTypeHidden" value="{{ $defaultType }}">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">Type</label>
                                <div class="form-control form-control-sm bg-light">{{ \App\Models\DailyCashEntry::$types[$defaultType] ?? $defaultType }}</div>
                            </div>
                        @endif
                        <div class="col-12">
                            <label class="form-label small fw-bold">Subcategory <span class="text-muted fw-normal">(optional)</span></label>
                            <select name="subcategory_key" id="stmtEditSubcategory" class="form-select form-select-sm"></select>
                            <div class="form-text small text-muted">Optional; default leaves classification to keywords / Uncategorized or inline edits.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Optional ledger tag</label>
                            <select name="category_preset" class="form-select form-select-sm" id="stmtEditCategoryPreset">
                                <option value="none">— Optional / uncategorized —</option>
                                @foreach($presetsForForm as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                                @if($viewKey === 'income')
                                    <option value="income_plus">Income + custom…</option>
                                @endif
                                @if($viewKey === 'discretionary')
                                    <option value="discretionary_plus">Discretionary + custom…</option>
                                @endif
                                @if($viewKey === 'savings')
                                    <option value="savings_plus">Savings + custom…</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-12 d-none" id="stmtEditCustomCategoryWrap">
                            <label class="form-label small fw-bold">Custom category name</label>
                            <input type="text" name="category_custom_piece" id="stmtEditCategoryCustomPiece" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <input type="text" name="description" id="stmtEditDesc" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Amount (₱)</label>
                            <input type="number" name="amount" id="stmtEditAmount" step="0.01" min="0.01" class="form-control form-control-sm" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
window.statementEntryFormMeta = @json($statementEntryFormMeta ?? []);

function statementFillSubcategory(selectEl, ledgerType) {
    if (!selectEl || !window.statementEntryFormMeta) return;
    const pack = window.statementEntryFormMeta[ledgerType] || { groups: [], labels: {} };
    const groups = pack.groups || [];
    const labels = pack.labels || {};
    const prev = selectEl.value;
    selectEl.innerHTML = '';
    const auto = document.createElement('option');
    auto.value = 'auto';
    auto.textContent = 'No subcategory now (default — optional)';
    selectEl.appendChild(auto);
    for (let i = 0; i < groups.length; i++) {
        const g = groups[i];
        const og = document.createElement('optgroup');
        og.label = g.label || g.key || '';
        const keys = g.subcategory_keys || [];
        for (let j = 0; j < keys.length; j++) {
            const sk = keys[j];
            const o = document.createElement('option');
            o.value = sk;
            o.textContent = labels[sk] || sk;
            og.appendChild(o);
        }
        selectEl.appendChild(og);
    }
    const allowed = Array.from(selectEl.options).map(function (o) { return o.value; });
    selectEl.value = allowed.indexOf(prev) !== -1 ? prev : 'auto';
}

(function () {
    const sel = document.getElementById('stmtCategoryPreset');
    const wrap = document.getElementById('stmtCustomCategoryWrap');
    const subSel = document.getElementById('stmtSubcategory');
    const entryType = document.getElementById('stmtEntryType');
    if (!sel || !wrap) return;
    function ledgerTypeForAdd() {
        @if($viewKey === 'expenses')
        return entryType && entryType.value === 'PURCHASES' ? 'PURCHASES' : 'EXPENSES';
        @else
        return @json($defaultType);
        @endif
    }
    function syncAddSubcat() {
        if (subSel) statementFillSubcategory(subSel, ledgerTypeForAdd());
    }
    function sync() {
        const v = sel.value;
        const show = v === 'income_plus' || v === 'discretionary_plus' || v === 'savings_plus';
        wrap.classList.toggle('d-none', !show);
    }
    sel.addEventListener('change', sync);
    if (entryType) entryType.addEventListener('change', syncAddSubcat);
    document.getElementById('statementAddModal')?.addEventListener('show.bs.modal', syncAddSubcat);
    sync();
    syncAddSubcat();
})();
(function () {
    const modal = document.getElementById('statementEditModal');
    const form = document.getElementById('statementEditForm');
    if (!modal || !form) return;
    const typeSelect = document.getElementById('stmtEditType');
    const typeHidden = document.getElementById('stmtEditTypeHidden');
    const editSub = document.getElementById('stmtEditSubcategory');
    const editPreset = document.getElementById('stmtEditCategoryPreset');
    const editWrap = document.getElementById('stmtEditCustomCategoryWrap');
    const editCustom = document.getElementById('stmtEditCategoryCustomPiece');
    function syncEditCustom() {
        if (!editPreset || !editWrap) return;
        const v = editPreset.value;
        const show = v === 'income_plus' || v === 'discretionary_plus' || v === 'savings_plus';
        editWrap.classList.toggle('d-none', !show);
    }
    function editLedgerType() {
        if (typeSelect) {
            return typeSelect.value === 'CASH_FROM_BANK' ? 'INCOME' : typeSelect.value;
        }
        return typeHidden ? typeHidden.value : @json($defaultType);
    }
    function refillEditSub() {
        if (!editSub) return;
        const bank = typeSelect && typeSelect.value === 'CASH_FROM_BANK';
        editSub.disabled = bank;
        statementFillSubcategory(editSub, editLedgerType());
    }
    if (editPreset) {
        editPreset.addEventListener('change', syncEditCustom);
    }
    if (typeSelect) {
        typeSelect.addEventListener('change', function () {
            const bank = typeSelect.value === 'CASH_FROM_BANK';
            if (editPreset) editPreset.disabled = bank;
            if (editSub) editSub.disabled = bank;
            refillEditSub();
        });
    }
    modal.addEventListener('show.bs.modal', function (ev) {
        const btn = ev.relatedTarget;
        if (!btn || !btn.classList.contains('js-statement-edit-entry')) return;
        form.action = btn.getAttribute('data-edit-url') || '#';
        const t = btn.getAttribute('data-entry-type') || '';
        if (typeSelect) {
            typeSelect.value = t;
        }
        const desc = document.getElementById('stmtEditDesc');
        const amt = document.getElementById('stmtEditAmount');
        if (desc) desc.value = btn.getAttribute('data-description') || '';
        if (amt) amt.value = btn.getAttribute('data-amount') || '';
        const bank = t === 'CASH_FROM_BANK';
        if (editPreset) editPreset.disabled = bank;
        if (editSub) editSub.disabled = bank;
        refillEditSub();
        const subRaw = btn.getAttribute('data-subcategory') || '';
        if (editSub && !bank) {
            editSub.value = subRaw ? subRaw : 'auto';
            if (!Array.from(editSub.options).some(function (o) { return o.value === editSub.value; })) {
                editSub.value = 'auto';
            }
        }
        if (editPreset) {
            const p = btn.getAttribute('data-preset') || 'none';
            editPreset.value = p;
            if (!Array.from(editPreset.options).some(function (o) { return o.value === p; })) {
                editPreset.value = 'none';
            }
        }
        if (editCustom) editCustom.value = btn.getAttribute('data-custom') || '';
        syncEditCustom();
    });
})();
</script>
@endpush
@endsection
