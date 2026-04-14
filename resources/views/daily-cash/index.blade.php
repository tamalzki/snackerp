@extends('layouts.app')
@section('title', 'Daily Cash Flow')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif
@if(session('warning'))
<div class="alert alert-warning alert-dismissible fade show py-2 small" role="alert">
    {{ session('warning') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif
@if($errors->has('subcategory'))
<div class="alert alert-danger py-2 small" role="alert">{{ $errors->first('subcategory') }}</div>
@endif

{{-- Top bar --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('daily-cash.today') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-calendar-check"></i> Go to Today
    </a>
    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#newDayModal">
        <i class="bi bi-plus-circle"></i> Open Specific Date
    </button>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'daily' ? 'active' : '' }}"
           href="{{ route('daily-cash.index', ['tab' => 'daily']) }}">
            <i class="bi bi-calendar3"></i> Daily Log
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'monthly' ? 'active' : '' }}"
           href="{{ route('daily-cash.index', ['tab' => 'monthly', 'year' => $filterYear]) }}">
            <i class="bi bi-calendar-month"></i> Monthly
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'annual' ? 'active' : '' }}"
           href="{{ route('daily-cash.index', ['tab' => 'annual']) }}">
            <i class="bi bi-bar-chart-line"></i> Annual
        </a>
    </li>
</ul>

<div class="d-flex flex-wrap gap-1 mb-3 small">
    <span class="text-muted align-self-center me-1">Statements:</span>
    <a href="{{ route('daily-cash.statements.income', ['year' => $filterYear ?? now()->year]) }}" class="btn btn-sm btn-outline-primary">Income</a>
    <a href="{{ route('daily-cash.statements.expenses', ['year' => $filterYear ?? now()->year]) }}" class="btn btn-sm btn-outline-primary">Expenses</a>
    <a href="{{ route('daily-cash.statements.discretionary', ['year' => $filterYear ?? now()->year]) }}" class="btn btn-sm btn-outline-success">Discretionary</a>
    <a href="{{ route('daily-cash.statements.savings', ['year' => $filterYear ?? now()->year]) }}" class="btn btn-sm btn-outline-secondary">Savings</a>
</div>

{{-- ===== DAILY LOG ===== --}}
@if($tab === 'daily')
<div class="card">
    <div class="card-header d-flex flex-column flex-sm-row align-items-sm-center gap-1">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-journal-text text-success"></i> Daily Cash Journal
        </div>
        <span class="text-muted small ms-sm-auto">Cash period starts March 1 each year (no carry from February into the new March).</span>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th class="text-end">Starting Cash</th>
                    <th class="text-end text-success">Income</th>
                    <th class="text-end text-danger">Expenses</th>
                    <th class="text-end">Savings</th>
                    <th class="text-end text-primary">Net</th>
                    <th class="text-end">Available Cash</th>
                    <th class="text-center">Entries</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($days as $day)
                @php
                    $isToday  = $day->date->isToday();
                    $net      = $day->net();
                    $avail    = (float)$day->opening_balance + $net;
                @endphp
                <tr class="{{ $isToday ? 'table-success' : '' }}">
                    <td>
                        <strong>{{ $day->date->format('M d, Y') }}</strong>
                        @if($isToday)<span class="badge bg-success ms-1">Today</span>@endif
                    </td>
                    <td class="text-muted small">{{ $day->date->format('l') }}</td>
                    <td class="text-end">₱{{ number_format($day->opening_balance, 2) }}</td>
                    <td class="text-end text-success">₱{{ number_format($day->income(), 2) }}</td>
                    <td class="text-end text-danger">₱{{ number_format($day->expenses(), 2) }}</td>
                    <td class="text-end">₱{{ number_format($day->savings(), 2) }}</td>
                    <td class="text-end fw-bold {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                        ₱{{ number_format($net, 2) }}
                    </td>
                    <td class="text-end fw-bold {{ $avail >= 0 ? 'text-success' : 'text-danger' }}">
                        ₱{{ number_format($avail, 2) }}
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary">{{ $day->entries->count() }}</span>
                    </td>
                    <td>
                        <a href="{{ route('daily-cash.show', $day) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Open
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">No records yet. Click "Go to Today" to start.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($days->hasPages())
<div class="mt-3">{{ $days->links() }}</div>
@endif
@endif

{{-- ===== MONTHLY ===== --}}
@if($tab === 'monthly')
<div class="d-flex align-items-center gap-2 mb-3">
    <span class="fw-bold small">Year:</span>
    @foreach($years as $y)
    <a href="{{ route('daily-cash.index', ['tab' => 'monthly', 'year' => $y]) }}"
       class="btn btn-sm {{ $filterYear == $y ? 'btn-primary' : 'btn-outline-secondary' }}">
        {{ $y }}
    </a>
    @endforeach
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-body p-0">
        @include('daily-cash._monthly-matrix', ['matrix' => $monthlyMatrix])
    </div>
</div>
@endif

{{-- ===== ANNUAL ===== --}}
@if($tab === 'annual')
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-bar-chart-line text-success"></i> Annual Summary
    </div>
    <div class="card-body p-0">
        @include('daily-cash._summary-table', ['rows' => $annual, 'emptyMsg' => 'No annual data yet.'])
    </div>
</div>
@endif

{{-- New day modal --}}
<div class="modal fade" id="newDayModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="{{ route('daily-cash.store') }}"
              onsubmit="return confirm('Opening this date sets its starting balance from prior days in the period and may update opening balances on later dates. Continue?')">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Open Specific Date</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm"
                           value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-sm">Open</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Recategorize subcategory (monthly / annual line groups) --}}
<div class="modal fade" id="subcategoryOverrideModal" tabindex="-1" aria-labelledby="subcategoryOverrideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('daily-cash.subcategory-override') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="subcategoryOverrideModalLabel">Recategorize line group</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body small">
                    <p class="text-muted mb-2">This updates every ledger line in the selected <strong>year</strong> that matches the same type, description, and current subcategory bucket. Pick a <strong>subcategory</strong> from the list for this entry type, or <strong>Auto</strong> to infer from the description text.</p>
                    <label class="form-label fw-semibold mb-1" for="subcat-select">Designated subcategory</label>
                    <select name="subcategory_key" id="subcat-select" class="form-select form-select-sm"></select>
                    <input type="hidden" name="year" id="subcat-year" value="">
                    <input type="hidden" name="type" id="subcat-type" value="">
                    <input type="hidden" name="description_norm" id="subcat-description-norm" value="">
                    <input type="hidden" name="line_subcategory_key" id="subcat-line-key" value="">
                    <input type="hidden" name="tab" id="subcat-tab" value="monthly">
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const opts = @json($subcategoryOptionsByType ?? []);
    const labelsByType = @json($subcategoryLabelsByType ?? []);
    const modal = document.getElementById('subcategoryOverrideModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (ev) {
        const btn = ev.relatedTarget;
        if (!btn || !btn.classList.contains('js-daily-cash-subcat-edit')) return;
        const type = btn.getAttribute('data-type') || '';
        document.getElementById('subcat-year').value = btn.getAttribute('data-year') || '';
        document.getElementById('subcat-type').value = type;
        document.getElementById('subcat-description-norm').value = btn.getAttribute('data-description-norm') || '';
        document.getElementById('subcat-line-key').value = btn.getAttribute('data-line-subcategory-key') || '';
        document.getElementById('subcat-tab').value = btn.getAttribute('data-tab') || 'monthly';
        const sel = document.getElementById('subcat-select');
        const list = opts[type] || [{ key: '', label: 'Auto — match description keywords' }];
        sel.innerHTML = '';
        list.forEach(function (o) {
            const opt = document.createElement('option');
            opt.value = o.key;
            opt.textContent = o.label;
            sel.appendChild(opt);
        });
        const cur = btn.getAttribute('data-line-subcategory-key') || '';
        let matched = false;
        for (let i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === cur) {
                sel.selectedIndex = i;
                matched = true;
                break;
            }
        }
        if (!matched && cur !== '') {
            const labels = labelsByType[type] || {};
            const opt = document.createElement('option');
            opt.value = cur;
            opt.textContent = labels[cur] || cur;
            sel.appendChild(opt);
            sel.value = cur;
        }
    });
})();
</script>
@endpush
