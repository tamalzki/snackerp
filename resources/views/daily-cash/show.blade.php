@extends('layouts.app')
@section('title', $dailyCash->date->format('F d, Y') . ' — Daily Cash')

@section('content')

@php
    $carriedOpeningBalance = (float) $dailyCash->opening_balance;
    $totalAvailableCash = round($carriedOpeningBalance + $dailyCash->net(), 2);
@endphp

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show py-2 small mb-2" role="alert">
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- Title row; then [←][one week strip][→]; strip ends at today (no future days) --}}
<div class="card border-0 shadow-sm mb-2">
    <div class="card-body py-2 px-2 px-sm-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
            <div class="text-center text-md-start">
                <span class="fw-semibold fs-6" style="color:#0d6b4f;">{{ $dailyCash->date->format('l') }}</span>
                <span class="text-muted small ms-1">{{ $dailyCash->date->format('F j, Y') }}</span>
                @if($dailyCash->date->isToday())
                    <span class="text-success fw-semibold ms-2">Today</span>
                @endif
            </div>
            <div class="d-flex flex-wrap align-items-center gap-1">
                <a href="{{ route('daily-cash.index', ['tab' => 'daily']) }}" class="btn btn-sm btn-outline-secondary">All days</a>
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('daily-cash.index', ['tab' => 'monthly']) }}" class="btn btn-outline-secondary">Monthly</a>
                    <a href="{{ route('daily-cash.index', ['tab' => 'annual']) }}" class="btn btn-outline-secondary">Annual</a>
                    <a href="{{ route('daily-cash.today') }}" class="btn btn-success" title="Open today’s cash">Go to today</a>
                </div>
            </div>
        </div>
        @if($weekRangeLabel !== '')
        <div class="text-muted small mb-2 text-center text-md-start">{{ $weekRangeLabel }}</div>
        @endif

        <div class="d-flex align-items-center gap-1">
            @if($prevWeekNav)
            <a href="{{ $prevWeekNav['url'] }}" class="btn btn-outline-secondary btn-sm flex-shrink-0 align-self-center" style="width:2.25rem;" title="Previous week ({{ $prevWeekNav['label'] }})"><i class="bi bi-chevron-left"></i></a>
            @else
            <span class="btn btn-outline-secondary btn-sm disabled opacity-50 flex-shrink-0 align-self-center" style="width:2.25rem;"><i class="bi bi-chevron-left"></i></span>
            @endif

            <div class="flex-grow-1 min-w-0 overflow-x-auto" style="-webkit-overflow-scrolling:touch;">
                <div class="d-flex flex-nowrap gap-1 pb-1">
                    @foreach($weekStrip as $weekDay)
                        @php
                            $key = $weekDay['date']->format('Y-m-d');
                            $isActive = $key === $dailyCash->date->format('Y-m-d');
                            $hasDay = $weekDay['day'] !== null;
                            $inRange = $weekDay['inRange'];
                            $href = $inRange
                                ? ($hasDay ? route('daily-cash.show', $weekDay['day']) : route('daily-cash.open-date', ['date' => $key]))
                                : '#';
                        @endphp
                        <div class="flex-shrink-0" style="min-width:5.5rem;">
                        @if($inRange)
                            <a href="{{ $href }}"
                               class="btn btn-sm w-100 px-1 py-1 rounded-2 lh-sm {{ $isActive ? 'btn-primary' : ($hasDay ? 'btn-outline-secondary' : 'btn-outline-secondary border-dashed') }}"
                               title="{{ $weekDay['date']->format('l, F j, Y') }}">
                                <span class="d-block fw-semibold text-center text-nowrap" style="font-size:0.68rem;">{{ $weekDay['date']->format('F j') }}</span>
                            </a>
                        @else
                            <span class="btn btn-sm btn-light disabled w-100 px-1 py-1 rounded-2 opacity-50 lh-sm" style="pointer-events:none;" title="Outside range">
                                <span class="d-block text-center text-nowrap" style="font-size:0.68rem;">{{ $weekDay['date']->format('F j') }}</span>
                            </span>
                        @endif
                        </div>
                    @endforeach
                </div>
            </div>

            @if($nextWeekNav)
            <a href="{{ $nextWeekNav['url'] }}" class="btn btn-outline-secondary btn-sm flex-shrink-0 align-self-center" style="width:2.25rem;" title="Next week ({{ $nextWeekNav['label'] }})"><i class="bi bi-chevron-right"></i></a>
            @else
            <span class="btn btn-outline-secondary btn-sm disabled opacity-50 flex-shrink-0 align-self-center" style="width:2.25rem;"><i class="bi bi-chevron-right"></i></span>
            @endif
        </div>
    </div>
</div>

<div class="alert alert-warning alert-dismissible fade show mb-2 py-2 small" role="alert">
    <i class="bi bi-link-45deg"></i>
    <strong>Carry-forward chain.</strong>
    Adding or changing entries, recording a deposit, or correcting starting cash on this date updates this day’s closing and <strong>automatically adjusts opening balances on all later dates</strong> in the same cash period (through today). You will be asked to confirm before each save.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<div class="row g-3">

    {{-- LEFT: Daily Summary --}}
    <div class="col-lg-3">

        {{-- Total Available Cash = carried opening (prior days in period) + today’s net --}}
        <div class="card mb-3">
            <div class="card-header py-2 d-flex align-items-center gap-2">
                <i class="bi bi-wallet2 text-success"></i>
                <span class="fw-bold small">TOTAL AVAILABLE CASH</span>
            </div>
            <div class="card-body py-3 text-center">
                {{-- Big amount --}}
                <div style="font-size:1.8rem;font-weight:700;color:#007A5E;">
                    ₱{{ number_format($totalAvailableCash, 2) }}
                </div>
                <div class="text-muted small mt-1">Starting cash + today’s net. <strong>Cash from Bank — Withdrawals</strong> (add entry) increases this total; <strong>Deposit to Bank</strong> reduces it because cash moved out of the till.</div>
                <button data-bs-toggle="modal" data-bs-target="#editCashModal"
                        class="btn btn-xs btn-outline-secondary mt-1"
                        style="font-size:0.72rem;padding:1px 8px;">
                    <i class="bi bi-pencil"></i> Edit Starting Cash
                </button>
            </div>
        </div>

        {{-- Summary table --}}
        <div class="card">
            <div class="card-header py-2">
                <span class="fw-bold small text-uppercase">Daily Summary</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:0.8rem;">
                    <tbody>
                        <tr class="border-bottom">
                            <td class="ps-3 text-muted small">Carried forward</td>
                            <td class="text-end pe-3">₱{{ number_format($carriedOpeningBalance, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-3"><span class="badge bg-primary" style="font-size:0.65rem;">Capital</span></td>
                            <td class="text-end pe-3">₱{{ number_format($dailyCash->capital(), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-3"><span class="badge bg-success" style="font-size:0.65rem;">Income</span>
                                <span class="d-block text-muted" style="font-size:0.65rem;">excl. bank withdrawals</span>
                            </td>
                            <td class="text-end pe-3 text-success fw-bold">₱{{ number_format($dailyCash->incomeExcludingBankWithdrawals(), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-3 small text-muted">Cash from Bank — Withdrawals</td>
                            <td class="text-end pe-3 text-success">₱{{ number_format($dailyCash->cashFromBankWithdrawals(), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-3"><span class="badge bg-danger" style="font-size:0.65rem;">Expenses</span></td>
                            <td class="text-end pe-3 text-danger">₱{{ number_format($dailyCash->expenses(), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-3"><span class="badge bg-secondary" style="font-size:0.65rem;">Discret.</span></td>
                            <td class="text-end pe-3">₱{{ number_format($dailyCash->discretionary(), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-3"><span class="badge bg-info text-dark" style="font-size:0.65rem;">Savings</span>
                                <span class="d-block text-muted" style="font-size:0.65rem;">reduces cash available</span>
                            </td>
                            <td class="text-end pe-3">₱{{ number_format($dailyCash->savings(), 2) }}</td>
                        </tr>
                        @if($dailyCash->other() > 0)
                        <tr>
                            <td class="ps-3"><span class="badge bg-dark" style="font-size:0.65rem;">Other</span></td>
                            <td class="text-end pe-3">₱{{ number_format($dailyCash->other(), 2) }}</td>
                        </tr>
                        @endif
                        <tr style="background:#f0f9f6;border-top:2px solid #007A5E;">
                            <td class="ps-3 fw-bold">NET</td>
                            @php $net = $dailyCash->net(); @endphp
                            <td class="text-end pe-3 fw-bold {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                                ₱{{ number_format($net, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Deposit to Bank --}}
        <div class="mt-3 d-grid">
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#depositModal">
                <i class="bi bi-bank"></i> Deposit Total Cash to Bank
            </button>
        </div>

        {{-- Deposit history for this day --}}
        @if($dayDeposits->count())
        <div class="card mt-3">
            <div class="card-header py-2">
                <span class="fw-bold small text-uppercase">
                    <i class="bi bi-clock-history text-primary"></i> Deposits Today
                </span>
            </div>
            <div class="card-body p-0">
                @foreach($dayDeposits as $dep)
                <div class="d-flex justify-content-between align-items-start px-3 py-2
                    {{ !$loop->last ? 'border-bottom' : '' }}"
                    style="font-size:0.78rem;">
                    <div>
                        <div class="fw-bold text-primary">₱{{ number_format($dep->amount, 2) }}</div>
                        <div class="text-muted">{{ $dep->source_name }}</div>
                        @if($dep->reference)
                            <div class="text-muted" style="font-size:0.72rem;">Ref: {{ $dep->reference }}</div>
                        @endif
                    </div>
                    <a href="{{ route('deposits.show', $dep) }}"
                       class="btn btn-xs btn-outline-secondary p-0 px-1 ms-2" style="font-size:0.7rem;">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
                @endforeach
                <div class="px-3 py-2 border-top d-flex justify-content-between"
                     style="background:#f0f9f6;font-size:0.78rem;">
                    <span class="fw-bold">Total Deposited</span>
                    <span class="fw-bold text-primary">₱{{ number_format($dayDeposits->sum('amount'), 2) }}</span>
                </div>
            </div>
        </div>
        @endif

        {{-- Notes --}}
        @if($dailyCash->notes)
        <div class="card mt-3">
            <div class="card-body py-2 small text-muted">
                <i class="bi bi-sticky"></i> {{ $dailyCash->notes }}
            </div>
        </div>
        @endif

    </div>

    {{-- RIGHT: Itemized Entries --}}
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold small text-uppercase">
                    <i class="bi bi-table text-success"></i>
                    {{ $dailyCash->date->format('l, F d Y') }} — Entries
                </span>
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <div class="btn-group btn-group-sm d-none d-md-inline-flex" role="group">
                        <a href="{{ route('daily-cash.statements.income', ['year' => $dailyCash->date->year]) }}" class="btn btn-outline-secondary" title="Income Statement">Inc.</a>
                        <a href="{{ route('daily-cash.statements.expenses', ['year' => $dailyCash->date->year]) }}" class="btn btn-outline-secondary" title="Expenses">Exp.</a>
                        <a href="{{ route('daily-cash.statements.discretionary', ['year' => $dailyCash->date->year]) }}" class="btn btn-outline-secondary" title="Discretionary">Discr.</a>
                        <a href="{{ route('daily-cash.statements.savings', ['year' => $dailyCash->date->year]) }}" class="btn btn-outline-secondary" title="Savings">Sav.</a>
                    </div>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEntryModal">
                        <i class="bi bi-plus-lg"></i> Add Entry
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle" style="font-size:0.82rem;">
                        <thead>
                            <tr>
                                <th class="ps-3" style="width:120px;">Type</th>
                                <th>Description</th>
                                <th class="text-end" style="width:100px;">Capital</th>
                                <th class="text-end" style="width:100px;">Income</th>
                                <th class="text-end" style="width:100px;">Expenses</th>
                                <th class="text-end" style="width:100px;">Discret.</th>
                                <th class="text-end" style="width:100px;">Savings</th>
                                <th class="text-end" style="width:100px;">Other</th>
                                <th style="width:140px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailyCash->entries as $entry)
                            @php
                                $color = \App\Models\DailyCashEntry::$typeColors[$entry->type] ?? 'secondary';
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-{{ $color }}" style="font-size:0.65rem;">{{ $entry->type }}</span>
                                    @if($entry->type === 'INCOME' && $entry->category === \App\Support\DailyCashflowCategories::CASH_FROM_BANK)
                                        <span class="d-block text-muted mt-1" style="font-size:0.62rem;">Bank withdrawal</span>
                                    @endif
                                </td>
                                <td>{{ $entry->description }}</td>
                                <td class="text-end">{{ $entry->type === 'CAPITAL' ? '₱'.number_format($entry->amount,2) : '' }}</td>
                                <td class="text-end text-success">{{ $entry->type === 'INCOME' ? '₱'.number_format($entry->amount,2) : '' }}</td>
                                <td class="text-end text-danger">{{ in_array($entry->type, ['EXPENSES','PURCHASES']) ? '₱'.number_format($entry->amount,2) : '' }}</td>
                                <td class="text-end">{{ $entry->type === 'DISCRETIONARY' ? '₱'.number_format($entry->amount,2) : '' }}</td>
                                <td class="text-end text-info">{{ $entry->type === 'SAVINGS' ? '₱'.number_format($entry->amount,2) : '' }}</td>
                                <td class="text-end text-secondary">{{ $entry->type === 'OTHER' ? '₱'.number_format($entry->amount,2) : '' }}</td>
                                <td class="text-end pe-2" style="white-space:nowrap;">
                                    <button class="btn btn-outline-secondary py-0 px-2"
                                            style="font-size:0.75rem;"
                                            onclick="editEntry({{ $entry->id }}, {{ json_encode($entry->type) }}, {{ json_encode($entry->description) }}, {{ json_encode((float) $entry->amount) }}, {{ json_encode($entry->category) }}, {{ json_encode($entry->subcategory_override ?? '') }})">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <form method="POST"
                                          action="{{ route('daily-cash.entries.destroy', [$dailyCash, $entry]) }}"
                                          class="d-inline"
                                          onsubmit="return dailyCashConfirmDeleteEntry()">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger py-0 px-2"
                                                style="font-size:0.75rem;">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    No entries yet — click <strong>Add Entry</strong> to start logging.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($dailyCash->entries->count() > 0)
                        <tfoot style="background:#f0f9f6;border-top:2px solid #007A5E;">
                            <tr class="fw-bold">
                                <td colspan="2" class="ps-3 text-uppercase small">Totals</td>
                                <td class="text-end">₱{{ number_format($dailyCash->capital(), 2) }}</td>
                                <td class="text-end text-success">₱{{ number_format($dailyCash->income(), 2) }}</td>
                                <td class="text-end text-danger">₱{{ number_format($dailyCash->expenses(), 2) }}</td>
                                <td class="text-end">₱{{ number_format($dailyCash->discretionary(), 2) }}</td>
                                <td class="text-end text-info">₱{{ number_format($dailyCash->savings(), 2) }}</td>
                                <td class="text-end text-secondary">₱{{ number_format($dailyCash->totalByType('OTHER'), 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Add Entry Modal --}}
<div class="modal fade" id="addEntryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('daily-cash.entries.store', $dailyCash) }}"
              onsubmit="return dailyCashConfirmCarryImpact()">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-plus-circle text-success"></i> Add Entry</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Type</label>
                            <select name="type" id="addEntryType" class="form-select form-select-sm" required>
                                @foreach(\App\Models\DailyCashEntry::$types as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @if($val === 'INCOME')
                                <option value="CASH_FROM_BANK">Cash from Bank — Withdrawals (adds to cash on hand)</option>
                                @endif
                                @endforeach
                            </select>
                            <div class="form-text">Use <strong>Cash from Bank — Withdrawals</strong> for ATM or counter withdrawals that increase cash on hand. Use <strong>Deposit to Bank</strong> (below the summary) when cash leaves the till for the bank.</div>
                        </div>
                        <div class="col-12 js-add-entry-classify">
                            <label class="form-label small fw-bold">Subcategory <span class="text-muted fw-normal">(optional)</span></label>
                            <select name="subcategory_key" id="addEntrySubcategory" class="form-select form-select-sm"></select>
                            <div class="form-text">Leave the default to skip this step — the line can show as <strong>Uncategorized</strong> or match <strong>keywords</strong> in the description. You can always set or change the bucket later with the <strong>pencil</strong> on Monthly / Annual.</div>
                        </div>
                        <div class="col-12 js-add-entry-classify">
                            <label class="form-label small fw-bold">Optional ledger tag</label>
                            <select name="category_preset" id="addEntryCategoryPreset" class="form-select form-select-sm">
                                <option value="none">— None —</option>
                                @foreach(\App\Support\DailyCashflowCategories::presetsForType('INCOME') as $val => $label)
                                    @if($val !== 'cash_from_bank')
                                    <option value="{{ $val }}" data-preset-type="INCOME">{{ $label }}</option>
                                    @endif
                                @endforeach
                                @foreach(\App\Support\DailyCashflowCategories::presetsForType('EXPENSES') as $val => $label)
                                    <option value="{{ $val }}" data-preset-type="EXPENSES" class="d-none">{{ $label }}</option>
                                @endforeach
                                @foreach(\App\Support\DailyCashflowCategories::presetsForType('PURCHASES') as $val => $label)
                                    <option value="{{ $val }}" data-preset-type="PURCHASES" class="d-none">{{ $label }}</option>
                                @endforeach
                                @foreach(\App\Support\DailyCashflowCategories::presetsForType('DISCRETIONARY') as $val => $label)
                                    <option value="{{ $val }}" data-preset-type="DISCRETIONARY" class="d-none">{{ $label }}</option>
                                @endforeach
                                @foreach(\App\Support\DailyCashflowCategories::presetsForType('SAVINGS') as $val => $label)
                                    <option value="{{ $val }}" data-preset-type="SAVINGS" class="d-none">{{ $label }}</option>
                                @endforeach
                                @foreach(\App\Support\DailyCashflowCategories::presetsForType('CAPITAL') as $val => $label)
                                    <option value="{{ $val }}" data-preset-type="CAPITAL" class="d-none">{{ $label }}</option>
                                @endforeach
                                <option value="income_plus" data-preset-type="INCOME" class="d-none">Income + custom…</option>
                                <option value="discretionary_plus" data-preset-type="DISCRETIONARY" class="d-none">Discretionary + custom…</option>
                                <option value="savings_plus" data-preset-type="SAVINGS" class="d-none">Savings + custom…</option>
                            </select>
                        </div>
                        <div class="col-12 d-none" id="addEntryCustomCategoryWrap">
                            <label class="form-label small fw-bold">Custom category name</label>
                            <input type="text" name="category_custom_piece" id="addEntryCategoryCustom" class="form-control form-control-sm" placeholder="e.g. SIDE BUSINESS">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <input type="text" name="description" class="form-control form-control-sm"
                                   placeholder="e.g. BDO ATM, COUNTER WITHDRAWAL…" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Amount (₱)</label>
                            <input type="number" name="amount" step="0.01" min="0.01"
                                   class="form-control form-control-sm" placeholder="0.00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Add Entry</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit Total Available Cash Modal --}}
<div class="modal fade" id="editCashModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" action="{{ route('daily-cash.update', $dailyCash) }}"
              onsubmit="return dailyCashConfirmCarryImpact()">
            @csrf @method('PUT')
            <input type="hidden" name="total_cash_mode" value="1">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-wallet2 text-success"></i> Correct Total Available Cash</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small fw-bold">Actual Total Available Cash (₱)</label>
                    <input type="number" name="total_cash" step="0.01" min="0"
                           value="{{ old('total_cash', (float) $totalAvailableCash) }}"
                           class="form-control @error('total_cash') is-invalid @enderror"
                           placeholder="Enter actual cash on hand"
                           required>
                    @error('total_cash')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div class="form-text mt-1">Enter the actual cash on hand. We set today’s starting balance so it matches; later days in the period update from the new closing total.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Deposit to Bank Modal --}}
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('daily-cash.deposit', $dailyCash) }}"
              onsubmit="return dailyCashConfirmCarryImpact()">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-bank text-primary"></i> Deposit to Bank</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="source_type" value="bank">
                    <div class="alert alert-info py-2 small mb-3">
                        <strong>Reduces Total Available Cash.</strong> This records cash leaving your till; a <strong>Savings</strong> line is added so today’s on-hand cash matches reality. Your bank balance increases.
                    </div>
                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label small fw-bold">Bank Account</label>
                            <select name="source_id" class="form-select form-select-sm" required>
                                @forelse($bankAccounts as $bank)
                                <option value="{{ $bank->id }}">
                                    {{ $bank->bank_name }}{{ $bank->account_name ? ' — '.$bank->account_name : '' }}
                                    (Balance: ₱{{ number_format($bank->balance, 2) }})
                                </option>
                                @empty
                                <option disabled>No bank accounts — add one in Finance → Bank Accounts</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Amount (₱)</label>
                            <input type="number" name="amount" step="0.01" min="0.01"
                                   class="form-control form-control-sm @error('amount') is-invalid @enderror"
                                   placeholder="0.00" value="{{ old('amount') }}" required>
                            @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Total available cash today: <strong>₱{{ number_format($totalAvailableCash, 2) }}</strong></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Reference <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="reference" class="form-control form-control-sm"
                                   placeholder="e.g. BDO slip #001">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Notes <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2"
                                      placeholder="Additional details…"></textarea>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-check-lg"></i> Record Deposit
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit Entry Modal --}}
<div class="modal fade" id="editEntryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editEntryForm" onsubmit="return dailyCashConfirmCarryImpact()">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-pencil text-warning"></i> Edit Entry</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Type</label>
                            <select name="type" id="editType" class="form-select form-select-sm" required>
                                @foreach(\App\Models\DailyCashEntry::$types as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @if($val === 'INCOME')
                                <option value="CASH_FROM_BANK">Cash from Bank — Withdrawals (adds to cash on hand)</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 js-edit-entry-classify">
                            <label class="form-label small fw-bold">Subcategory <span class="text-muted fw-normal">(optional)</span></label>
                            <select name="subcategory_key" id="editEntrySubcategory" class="form-select form-select-sm"></select>
                            <div class="form-text small text-muted">Optional. Default skips an explicit bucket (keywords / Uncategorized). Use Monthly / Annual pencil to recategorize line groups.</div>
                        </div>
                        <div class="col-12 js-edit-entry-classify">
                            <label class="form-label small fw-bold">Optional ledger tag</label>
                            <select name="category_preset" id="editEntryCategoryPreset" class="form-select form-select-sm">
                                <option value="none">— None —</option>
                                @foreach(\App\Support\DailyCashflowCategories::presetsForType('INCOME') as $val => $label)
                                    @if($val !== 'cash_from_bank')
                                    <option value="{{ $val }}" data-preset-type="INCOME">{{ $label }}</option>
                                    @endif
                                @endforeach
                                @foreach(\App\Support\DailyCashflowCategories::presetsForType('EXPENSES') as $val => $label)
                                    <option value="{{ $val }}" data-preset-type="EXPENSES" class="d-none">{{ $label }}</option>
                                @endforeach
                                @foreach(\App\Support\DailyCashflowCategories::presetsForType('PURCHASES') as $val => $label)
                                    <option value="{{ $val }}" data-preset-type="PURCHASES" class="d-none">{{ $label }}</option>
                                @endforeach
                                @foreach(\App\Support\DailyCashflowCategories::presetsForType('DISCRETIONARY') as $val => $label)
                                    <option value="{{ $val }}" data-preset-type="DISCRETIONARY" class="d-none">{{ $label }}</option>
                                @endforeach
                                @foreach(\App\Support\DailyCashflowCategories::presetsForType('SAVINGS') as $val => $label)
                                    <option value="{{ $val }}" data-preset-type="SAVINGS" class="d-none">{{ $label }}</option>
                                @endforeach
                                @foreach(\App\Support\DailyCashflowCategories::presetsForType('CAPITAL') as $val => $label)
                                    <option value="{{ $val }}" data-preset-type="CAPITAL" class="d-none">{{ $label }}</option>
                                @endforeach
                                <option value="income_plus" data-preset-type="INCOME" class="d-none">Income + custom…</option>
                                <option value="discretionary_plus" data-preset-type="DISCRETIONARY" class="d-none">Discretionary + custom…</option>
                                <option value="savings_plus" data-preset-type="SAVINGS" class="d-none">Savings + custom…</option>
                            </select>
                        </div>
                        <div class="col-12 d-none" id="editEntryCustomCategoryWrap">
                            <label class="form-label small fw-bold">Custom category name</label>
                            <input type="text" name="category_custom_piece" id="editEntryCategoryCustom" class="form-control form-control-sm" placeholder="e.g. SIDE BUSINESS">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <input type="text" name="description" id="editDesc"
                                   class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Amount (₱)</label>
                            <input type="number" name="amount" id="editAmount"
                                   step="0.01" min="0.01" class="form-control form-control-sm" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
window.cashEntryFormMeta = @json($cashEntryFormMeta ?? ['groups' => [], 'labels' => []]);

function dailyCashFillSubcategorySelect(selectEl, ledgerType) {
    if (!selectEl || !window.cashEntryFormMeta || !window.cashEntryFormMeta.groups) return;
    const groups = window.cashEntryFormMeta.groups[ledgerType] || [];
    const labels = window.cashEntryFormMeta.labels[ledgerType] || {};
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

function dailyCashSyncPresetOptionsForType(selectEl, ledgerType, skipValueReset) {
    if (!selectEl) return;
    const opts = selectEl.querySelectorAll('option[data-preset-type]');
    let cur = selectEl.value;
    opts.forEach(function (o) {
        const t = o.getAttribute('data-preset-type');
        const show = t === ledgerType;
        o.classList.toggle('d-none', !show);
    });
    const allowed = Array.from(selectEl.options).filter(function (o) {
        if (o.value === 'none') return true;
        const t = o.getAttribute('data-preset-type');
        return t === ledgerType && !o.classList.contains('d-none');
    }).map(function (o) { return o.value; });
    if (!skipValueReset && allowed.indexOf(cur) === -1) {
        selectEl.value = 'none';
        cur = 'none';
    }
    const wrap = selectEl.id === 'addEntryCategoryPreset'
        ? document.getElementById('addEntryCustomCategoryWrap')
        : document.getElementById('editEntryCustomCategoryWrap');
    const customIn = selectEl.id === 'addEntryCategoryPreset'
        ? document.getElementById('addEntryCategoryCustom')
        : document.getElementById('editEntryCategoryCustom');
    if (wrap) {
        const show = cur === 'income_plus' || cur === 'discretionary_plus' || cur === 'savings_plus';
        wrap.classList.toggle('d-none', !show);
    }
    if (customIn && (cur !== 'income_plus' && cur !== 'discretionary_plus' && cur !== 'savings_plus')) {
        customIn.value = '';
    }
}

function dailyCashSetClassifyVisibility(isBank, prefix) {
    const sub = document.getElementById(prefix === 'add' ? 'addEntrySubcategory' : 'editEntrySubcategory');
    const preset = document.getElementById(prefix === 'add' ? 'addEntryCategoryPreset' : 'editEntryCategoryPreset');
    document.querySelectorAll('.js-' + prefix + '-entry-classify').forEach(function (el) {
        el.classList.toggle('d-none', isBank);
    });
    if (sub) sub.disabled = isBank;
    if (preset) preset.disabled = isBank;
}

function dailyCashConfirmCarryImpact() {
    return window.confirm(
        'This will recalculate this day’s closing balance and automatically update opening balances on all later days in this cash period (including today), so carried cash stays in sync.\n\nContinue?'
    );
}
function dailyCashConfirmDeleteEntry() {
    return window.confirm(
        'Delete this entry?\n\nThis will also recalculate this day and update opening balances on all later days in this cash period.\n\nContinue?'
    );
}
function editEntry(id, type, description, amount, category, subKey) {
    document.getElementById('editEntryForm').action =
        '{{ url("daily-cash/".$dailyCash->id."/entries") }}/' + id;
    let formType = type;
    if (type === 'INCOME' && category === 'cash_from_bank') {
        formType = 'CASH_FROM_BANK';
    }
    document.getElementById('editType').value = formType;
    document.getElementById('editDesc').value   = description;
    document.getElementById('editAmount').value = amount;
    const bank = formType === 'CASH_FROM_BANK';
    dailyCashSetClassifyVisibility(bank, 'edit');
    const ledgerType = bank ? 'INCOME' : type;
    dailyCashFillSubcategorySelect(document.getElementById('editEntrySubcategory'), ledgerType);
    const subEl = document.getElementById('editEntrySubcategory');
    if (subEl) {
        subEl.value = subKey ? subKey : 'auto';
        if (!Array.from(subEl.options).some(function (o) { return o.value === subEl.value; })) {
            subEl.value = 'auto';
        }
    }
    (function () {
        const presetEl = document.getElementById('editEntryCategoryPreset');
        if (!presetEl) return;
        let preset = 'none';
        let custom = '';
        if (category) {
            const c = String(category);
            if (c.startsWith('income:')) {
                preset = 'income_plus';
                custom = c.slice(7);
            } else if (c.startsWith('discretionary:')) {
                preset = 'discretionary_plus';
                custom = c.slice(14);
            } else if (c.startsWith('savings:')) {
                preset = 'savings_plus';
                custom = c.slice(8);
            } else {
                preset = c;
            }
        }
        dailyCashSyncPresetOptionsForType(presetEl, ledgerType, true);
        presetEl.value = preset;
        if (!Array.from(presetEl.options).some(function (o) { return o.value === presetEl.value; })) {
            presetEl.value = 'none';
        }
        const customIn = document.getElementById('editEntryCategoryCustom');
        if (customIn) customIn.value = custom;
        dailyCashSyncPresetOptionsForType(presetEl, ledgerType, false);
    })();
    new bootstrap.Modal(document.getElementById('editEntryModal')).show();
}
(function () {
    const addType = document.getElementById('addEntryType');
    const addSub = document.getElementById('addEntrySubcategory');
    const addPreset = document.getElementById('addEntryCategoryPreset');
    function syncAdd() {
        if (!addType) return;
        const bank = addType.value === 'CASH_FROM_BANK';
        dailyCashSetClassifyVisibility(bank, 'add');
        const ledgerType = bank ? 'INCOME' : addType.value;
        dailyCashFillSubcategorySelect(addSub, ledgerType);
        dailyCashSyncPresetOptionsForType(addPreset, ledgerType);
    }
    if (addType) {
        addType.addEventListener('change', syncAdd);
    }
    if (addPreset) {
        addPreset.addEventListener('change', function () { dailyCashSyncPresetOptionsForType(addPreset, addType.value === 'CASH_FROM_BANK' ? 'INCOME' : addType.value); });
    }
    const editType = document.getElementById('editType');
    const editPreset = document.getElementById('editEntryCategoryPreset');
    if (editType) {
        editType.addEventListener('change', function () {
            const bank = editType.value === 'CASH_FROM_BANK';
            dailyCashSetClassifyVisibility(bank, 'edit');
            const ledgerType = bank ? 'INCOME' : editType.value;
            dailyCashFillSubcategorySelect(document.getElementById('editEntrySubcategory'), ledgerType);
            dailyCashSyncPresetOptionsForType(editPreset, ledgerType);
        });
    }
    if (editPreset) {
        editPreset.addEventListener('change', function () {
            const bank = editType && editType.value === 'CASH_FROM_BANK';
            const ledgerType = bank ? 'INCOME' : (editType ? editType.value : 'INCOME');
            dailyCashSyncPresetOptionsForType(editPreset, ledgerType);
        });
    }
    document.getElementById('addEntryModal')?.addEventListener('show.bs.modal', syncAdd);
    syncAdd();
})();
</script>
@endpush
@endsection
