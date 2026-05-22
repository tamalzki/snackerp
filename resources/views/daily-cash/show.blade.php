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
                <style>
                    /* Collapsed borders so every cell shares continuous grid lines */
                    .daily-cash-worksheet {
                        border-collapse: collapse;
                        border-spacing: 0;
                    }
                    .daily-cash-worksheet thead th {
                        border: 1px solid #212529 !important;
                        font-weight: 600;
                    }
                    .daily-cash-worksheet tbody.daily-cash-sheet-group td {
                        border: 1px solid #212529 !important;
                        vertical-align: top;
                    }
                    .daily-cash-worksheet tbody.daily-cash-sheet-spacer td {
                        border: none !important;
                        height: 0.45rem;
                        padding: 0 !important;
                        background: transparent !important;
                    }
                    .daily-cash-worksheet tfoot td {
                        border: 1px solid #212529 !important;
                        vertical-align: middle;
                    }
                </style>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle daily-cash-worksheet" style="font-size:0.82rem;">
                        <thead>
                            <tr>
                                <th class="ps-2" style="width:9rem;">Group</th>
                                <th>Category</th>
                                <th class="text-end" style="width:100px;">Capital</th>
                                <th class="text-end" style="width:100px;">Income</th>
                                <th class="text-end" style="width:100px;">Expenses</th>
                                <th class="text-end" style="width:100px;">Discret.</th>
                                <th class="text-end" style="width:100px;">Savings</th>
                                <th class="text-end" style="width:100px;">Other</th>
                                <th style="width:140px;"></th>
                            </tr>
                        </thead>
                            @php
                                $metroSections = [];
                                $currentSection = null;
                                foreach ($metroSheetRows as $_sheetRow) {
                                    $_k = $_sheetRow['kind'] ?? '';
                                    if ($_k === 'heading') {
                                        if ($currentSection !== null) {
                                            $metroSections[] = $currentSection;
                                        }
                                        $currentSection = ['heading' => $_sheetRow['title'] ?? '', 'lines' => []];
                                    } elseif ($_k === 'line' && $currentSection !== null) {
                                        $currentSection['lines'][] = $_sheetRow;
                                    }
                                }
                                if ($currentSection !== null) {
                                    $metroSections[] = $currentSection;
                                }
                            @endphp
                            @foreach($metroSections as $section)
                                @php
                                    $lines = $section['lines'] ?? [];
                                    $typeRowspan = count($lines);
                                    $parentLabel = \App\Support\DailyCashMetroLedger::parentColumnLabelFromSectionHeading($section['heading'] ?? '');
                                @endphp
                                @if($typeRowspan > 0)
                                <tbody class="daily-cash-sheet-group">
                                @foreach($lines as $lineIdx => $sheetRow)
                                    @php
                                        $lineType = $sheetRow['type'];
                                        $lineAmt = (float) ($sheetRow['amount'] ?? 0);
                                        $hasAmt = abs($lineAmt) > 0.005;
                                        $pc = \App\Support\DailyCashMetroLedger::primaryAmountColumn($lineType);
                                        $rep = $sheetRow['representative_entry'] ?? null;
                                        $categoryDisplay = $sheetRow['category_display'] ?? '';
                                    @endphp
                                    <tr>
                                        @if($lineIdx === 0)
                                            <td rowspan="{{ $typeRowspan }}" class="ps-3 py-2 align-top text-body" style="width:9rem;max-width:11rem;">
                                                {{ $parentLabel }}
                                            </td>
                                        @endif
                                        <td class="align-top py-2 text-body">
                                            {{ $categoryDisplay }}
                                            @if($lineType === 'INCOME' && ($sheetRow['category_key'] ?? '') === 'cash_from_bank')
                                                <span class="d-block text-muted mt-1" style="font-size:0.62rem;">Withdrawal</span>
                                            @endif
                                        </td>
                                        <td class="text-end align-top py-2">
                                            @if($pc === 'capital' && $hasAmt)
                                                <span>₱{{ number_format($lineAmt, 2) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end align-top py-2">
                                            @if($pc === 'income' && $hasAmt)
                                                ₱{{ number_format($lineAmt, 2) }}
                                            @endif
                                        </td>
                                        <td class="text-end align-top py-2">
                                            @if($pc === 'expenses' && $hasAmt)
                                                ₱{{ number_format($lineAmt, 2) }}
                                            @endif
                                        </td>
                                        <td class="text-end align-top py-2">
                                            @if($pc === 'discretionary' && $hasAmt)
                                                <span>₱{{ number_format($lineAmt, 2) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end align-top py-2">
                                            @if($pc === 'savings' && $hasAmt)
                                                ₱{{ number_format($lineAmt, 2) }}
                                            @endif
                                        </td>
                                        <td class="text-end align-top py-2">
                                            @if($pc === 'other' && $hasAmt)
                                                ₱{{ number_format($lineAmt, 2) }}
                                            @endif
                                        </td>
                                        <td class="text-end pe-2 align-top py-2" style="white-space:nowrap;">
                                            @if($rep)
                                                <button class="btn btn-outline-secondary py-0 px-2"
                                                        style="font-size:0.75rem;"
                                                        onclick="editEntry({{ $rep->id }}, {{ json_encode($rep->type) }}, {{ json_encode($rep->description) }}, {{ json_encode((float) $rep->amount) }}, {{ json_encode($rep->category) }}, {{ json_encode($rep->subcategory_override ?? '') }})">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <form method="POST"
                                                      action="{{ route('daily-cash.entries.destroy', [$dailyCash, $rep]) }}"
                                                      class="d-inline"
                                                      onsubmit="return dailyCashConfirmDeleteEntry()">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-outline-danger py-0 px-2"
                                                            style="font-size:0.75rem;">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                @if(!$loop->last)
                                <tbody class="daily-cash-sheet-spacer"><tr><td colspan="9"></td></tr></tbody>
                                @endif
                                @endif
                            @endforeach
                        @if($dailyCash->entries->count() > 0)
                        <tfoot class="table-light">
                            <tr class="fw-semibold">
                                <td colspan="2" class="ps-3 small text-body-secondary">Totals</td>
                                <td class="text-end">₱{{ number_format($dailyCash->capital(), 2) }}</td>
                                <td class="text-end">₱{{ number_format($dailyCash->income(), 2) }}</td>
                                <td class="text-end">₱{{ number_format($dailyCash->expenses(), 2) }}</td>
                                <td class="text-end">₱{{ number_format($dailyCash->discretionary(), 2) }}</td>
                                <td class="text-end">₱{{ number_format($dailyCash->savings(), 2) }}</td>
                                <td class="text-end">₱{{ number_format($dailyCash->totalByType('OTHER'), 2) }}</td>
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

{{-- Add Entry Modal (worksheet drill-down + cash from bank) --}}
<div class="modal fade" id="addEntryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('daily-cash.entries.store', $dailyCash) }}"
              id="addEntryForm"
              onsubmit="return dailyCashAddEntrySubmit();">
            @csrf
            <input type="hidden" name="type" id="addEntryResolvedType" value="">
            <input type="hidden" name="worksheet_category_key" id="addEntryWorksheetCategoryKey" value="">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-plus-circle text-success"></i> Add Entry</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Entry group</label>
                            <select id="addEntryGroup" class="form-select form-select-sm" required>
                                <option value="" selected disabled>— Choose —</option>
                                <option value="CASH_FROM_BANK">Cash from Bank — Withdrawals</option>
                                <option value="CAPITAL">Capital</option>
                                <option value="INCOME">Income</option>
                                <option value="EXPENSES">Expenses</option>
                                <option value="DISCRETIONARY">Discretionary</option>
                                <option value="SAVINGS">Savings</option>
                                <option value="OTHER">Other</option>
                            </select>
                            <div class="form-text small">Matches the daily worksheet. Use <strong>Deposit to Bank</strong> (below the summary) when cash leaves the till for the bank.</div>
                        </div>
                        <div class="col-12 d-none" id="addEntryExpenseBucketWrap">
                            <label class="form-label small fw-bold">Expense section</label>
                            <select id="addExpenseBucket" class="form-select form-select-sm"></select>
                        </div>
                        <div class="col-12 d-none" id="addEntryLineWrap">
                            <label class="form-label small fw-bold" id="addEntryLineLabel">Category line</label>
                            <select id="addEntryLineSelect" class="form-select form-select-sm"></select>
                        </div>
                        <div class="col-12 d-none" id="addEntryOtherWrap">
                            <label class="form-label small fw-bold">Specify “Other” <span class="text-danger">*</span></label>
                            <input type="text" name="other_specify" id="addEntryOtherSpecify" class="form-control form-control-sm"
                                   maxlength="255">
                            <div class="form-text small">Shows on the worksheet as <strong>Other - …</strong> with this text.</div>
                        </div>
                        <div class="col-12 d-none" id="addEntryDiscSubWrap">
                            <label class="form-label small fw-bold">Optional sub-category</label>
                            <select id="addEntryDiscSubcategory" class="form-select form-select-sm">
                                @foreach(\App\Support\CashflowSubcategoryClassifier::designatedEditOptionsForType('DISCRETIONARY') as $discOpt)
                                <option value="{{ $discOpt['key'] }}">{{ $discOpt['label'] }}</option>
                                @endforeach
                            </select>
                            <div class="form-text small text-muted">For reporting tags (e.g. Dining out vs Travel). Ledger line stays “Discretionary — Others”; memo is below.</div>
                        </div>
                        <div class="col-12 d-none" id="addEntryDescWrap">
                            <label class="form-label small fw-bold">Description <span id="addEntryDescRequiredStar" class="text-danger d-none">*</span></label>
                            <input type="text" name="description" id="addEntryDescription" class="form-control form-control-sm"
                                   maxlength="255">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Amount (₱)</label>
                            <input type="number" name="amount" step="0.01" min="0.01"
                                   class="form-control form-control-sm" required>
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
                                   value="{{ old('amount') }}" required>
                            @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Total available cash today: <strong>₱{{ number_format($totalAvailableCash, 2) }}</strong></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Reference <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="reference" class="form-control form-control-sm">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Notes <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
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
        <form method="POST" id="editEntryForm" onsubmit="return dailyCashEditEntrySubmit();">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-pencil text-warning"></i> Edit Entry</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12 d-none" id="editEntryWorksheetPane">
                            <input type="hidden" name="type" id="editEntryResolvedType" value="">
                            <input type="hidden" name="worksheet_category_key" id="editEntryWorksheetCategoryKey" value="">
                            <label class="form-label small fw-bold">Entry group</label>
                            <select id="editEntryGroup" class="form-select form-select-sm"></select>
                            <div class="mt-2 d-none" id="editEntryExpenseBucketWrap">
                                <label class="form-label small fw-bold">Expense section</label>
                                <select id="editExpenseBucket" class="form-select form-select-sm"></select>
                            </div>
                            <div class="mt-2 d-none" id="editEntryLineWrap">
                                <label class="form-label small fw-bold" id="editEntryLineLabel">Category line</label>
                                <select id="editEntryLineSelect" class="form-select form-select-sm"></select>
                            </div>
                            <div class="mt-2 d-none" id="editEntryOtherWrap">
                                <label class="form-label small fw-bold">Specify “Other” <span class="text-danger">*</span></label>
                                <input type="text" name="other_specify" id="editEntryOtherSpecify" class="form-control form-control-sm" maxlength="255">
                            </div>
                            <div class="mt-2 d-none" id="editEntryDiscSubWrap">
                                <label class="form-label small fw-bold">Optional sub-category</label>
                                <select id="editEntryDiscSubcategory" class="form-select form-select-sm">
                                    @foreach(\App\Support\CashflowSubcategoryClassifier::designatedEditOptionsForType('DISCRETIONARY') as $discOpt)
                                    <option value="{{ $discOpt['key'] }}">{{ $discOpt['label'] }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text small text-muted">Reporting tag only; worksheet line is discretionary “Others”.</div>
                            </div>
                            <div class="mt-2 d-none" id="editEntryDescWrap">
                                <label class="form-label small fw-bold">Description <span id="editEntryDescRequiredStar" class="text-danger d-none">*</span></label>
                                <input type="text" name="description" id="editWsDesc" class="form-control form-control-sm" maxlength="255">
                            </div>
                        </div>
                        <div class="col-12 d-none" id="editEntryLegacyPane">
                            <label class="form-label small fw-bold">Type</label>
                            <select name="type" id="editLegacyType" class="form-select form-select-sm" required>
                                @foreach(\App\Models\DailyCashEntry::$types as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @if($val === 'INCOME')
                                <option value="CASH_FROM_BANK">Cash from Bank — Withdrawals (adds to cash on hand)</option>
                                @endif
                                @endforeach
                            </select>
                            <div class="mt-2 js-edit-entry-classify">
                                <label class="form-label small fw-bold">Subcategory <span class="text-muted fw-normal">(optional)</span></label>
                                <select name="subcategory_key" id="editEntrySubcategory" class="form-select form-select-sm"></select>
                                <div class="form-text small text-muted">Optional. Default skips an explicit bucket (keywords / Uncategorized).</div>
                            </div>
                            <div class="mt-2 js-edit-entry-classify">
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
                            <div class="mt-2 d-none" id="editEntryCustomCategoryWrap">
                                <label class="form-label small fw-bold">Custom category name</label>
                                <input type="text" name="category_custom_piece" id="editEntryCategoryCustom" class="form-control form-control-sm">
                            </div>
                            <div class="mt-2">
                                <label class="form-label small fw-bold">Description</label>
                                <input type="text" name="description" id="editLegacyDesc" class="form-control form-control-sm" required maxlength="255">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Amount (₱)</label>
                            <input type="number" name="amount" id="editAmount"
                                   step="0.01" min="0" class="form-control form-control-sm" required>
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
@php
    $__dcfCashEntryMeta = $cashEntryFormMeta ?? ['groups' => [], 'labels' => []];
    $__dcfWorksheetMeta = $dailyCashWorksheetEntryMeta ?? [
        'capital' => ['lines' => []],
        'income' => ['buckets' => []],
        'expense' => ['buckets' => []],
        'managed_keys' => [],
    ];
@endphp
<script>
window.cashEntryFormMeta = @json($__dcfCashEntryMeta);
window.dailyCashWorksheetEntryMeta = @json($__dcfWorksheetMeta);
window.dailyCashDiscretionaryMetroOthersCategory = @json(\App\Support\DailyCashMetroLedger::DISCRETIONARY_METRO_OTHERS_CATEGORY);

function dailyCashDiscretionaryMetroOthersCategoryKey() {
    return window.dailyCashDiscretionaryMetroOthersCategory || 'metro_discretionary_others';
}

window.__dailyCashEditLegacyMode = false;

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

/** Cash-from-bank worksheet description is required; stars shown via HTML span ids. */
function dailyCashSetAddDescriptionRequireStar(showRequired) {
    const star = document.getElementById('addEntryDescRequiredStar');
    const descIn = document.getElementById('addEntryDescription');
    if (descIn) descIn.required = !!showRequired;
    if (star) star.classList.toggle('d-none', !showRequired);
}

function dailyCashSetEditDescriptionRequireStar(showRequired) {
    const star = document.getElementById('editEntryDescRequiredStar');
    const descIn = document.getElementById('editWsDesc');
    if (descIn) descIn.required = !!showRequired;
    if (star) star.classList.toggle('d-none', !showRequired);
}

function dailyCashAppendLineOption(sel, line) {
    const o = document.createElement('option');
    o.value = line.key;
    o.textContent = line.label;
    o.dataset.needsOther = line.needsOther ? '1' : '0';
    sel.appendChild(o);
}

/** @param buckets list of { heading, lines } from worksheet meta */
function dailyCashFillLineBucketsSelect(sel, buckets) {
    sel.innerHTML = '';
    const ph = document.createElement('option');
    ph.value = '';
    ph.textContent = '— Choose line —';
    sel.appendChild(ph);
    const list = buckets || [];
    for (let i = 0; i < list.length; i++) {
        const b = list[i];
        const og = document.createElement('optgroup');
        og.label = b.heading || ('Group ' + (i + 1));
        const lines = b.lines || [];
        for (let j = 0; j < lines.length; j++) {
            dailyCashAppendLineOption(og, lines[j]);
        }
        sel.appendChild(og);
    }
}

function dailyCashFillExpenseBucketSelect(sel, meta) {
    sel.innerHTML = '';
    const ph = document.createElement('option');
    ph.value = '';
    ph.textContent = '— Choose expense section —';
    sel.appendChild(ph);
    const buckets = (meta.expense && meta.expense.buckets) ? meta.expense.buckets : [];
    for (let i = 0; i < buckets.length; i++) {
        const o = document.createElement('option');
        o.value = String(i);
        o.textContent = buckets[i].heading || ('Section ' + (i + 1));
        sel.appendChild(o);
    }
}

function dailyCashFillExpenseLinesFromBucket(lineSel, meta, bucketIdx) {
    lineSel.innerHTML = '';
    const ph = document.createElement('option');
    ph.value = '';
    ph.textContent = '— Choose line —';
    lineSel.appendChild(ph);
    const buckets = (meta.expense && meta.expense.buckets) ? meta.expense.buckets : [];
    const b = buckets[bucketIdx];
    if (!b) return;
    const lines = b.lines || [];
    for (let j = 0; j < lines.length; j++) {
        dailyCashAppendLineOption(lineSel, lines[j]);
    }
}

function dailyCashSetAddEntryLineLabel(grp) {
    const lbl = document.getElementById('addEntryLineLabel');
    if (!lbl) return;
    if (grp === 'DISCRETIONARY') lbl.textContent = 'Subcategory';
    else if (grp === 'INCOME') lbl.textContent = 'Income line';
    else if (grp === 'EXPENSES') lbl.textContent = 'Expense line';
    else if (grp === 'SAVINGS') lbl.textContent = 'Savings line';
    else if (grp === 'OTHER') lbl.textContent = 'Other line';
    else lbl.textContent = 'Category line';
}

function dailyCashSetEditEntryLineLabel(grp) {
    const lbl = document.getElementById('editEntryLineLabel');
    if (!lbl) return;
    if (grp === 'DISCRETIONARY') lbl.textContent = 'Subcategory';
    else if (grp === 'INCOME') lbl.textContent = 'Income line';
    else if (grp === 'EXPENSES') lbl.textContent = 'Expense line';
    else if (grp === 'SAVINGS') lbl.textContent = 'Savings line';
    else if (grp === 'OTHER') lbl.textContent = 'Other line';
    else lbl.textContent = 'Category line';
}

function dailyCashSyncAddOtherDescVisibility() {
    const lineSel = document.getElementById('addEntryLineSelect');
    const keyHidden = document.getElementById('addEntryWorksheetCategoryKey');
    const otherWrap = document.getElementById('addEntryOtherWrap');
    const descWrap = document.getElementById('addEntryDescWrap');
    const otherIn = document.getElementById('addEntryOtherSpecify');
    const descIn = document.getElementById('addEntryDescription');
    const grp = document.getElementById('addEntryGroup').value;

    dailyCashSetAddEntryLineLabel(grp);

    if (grp === 'CASH_FROM_BANK' || grp === '' || grp === 'CAPITAL') {
        return;
    }

    const opt = lineSel.options[lineSel.selectedIndex];
    const needsOther = opt && opt.dataset.needsOther === '1';
    keyHidden.value = lineSel.value || '';

    otherWrap.classList.toggle('d-none', !needsOther);
    descWrap.classList.toggle('d-none', needsOther);
    otherIn.disabled = !needsOther;
    descIn.disabled = needsOther;
    otherIn.required = needsOther;
    descIn.required = needsOther ? false : (grp === 'DISCRETIONARY');
    dailyCashSetAddDescriptionRequireStar(needsOther ? false : (grp === 'DISCRETIONARY'));

    const discWrap = document.getElementById('addEntryDiscSubWrap');
    const discSel = document.getElementById('addEntryDiscSubcategory');
    const isDiscOther = grp === 'DISCRETIONARY' && needsOther;
    if (discWrap) {
        discWrap.classList.toggle('d-none', !isDiscOther);
    }
    if (discSel) {
        discSel.disabled = !isDiscOther;
        discSel.removeAttribute('name');
        if (isDiscOther) {
            discSel.setAttribute('name', 'subcategory_key');
        } else {
            discSel.value = '';
        }
    }
}

function dailyCashSyncAddEntryForm(options) {
    const resetInputs = !options || options.resetInputs !== false;
    const meta = window.dailyCashWorksheetEntryMeta || {};
    const grpEl = document.getElementById('addEntryGroup');
    const grp = grpEl ? grpEl.value : '';
    const typeHidden = document.getElementById('addEntryResolvedType');
    const keyHidden = document.getElementById('addEntryWorksheetCategoryKey');
    const expWrap = document.getElementById('addEntryExpenseBucketWrap');
    const lineWrap = document.getElementById('addEntryLineWrap');
    const lineSel = document.getElementById('addEntryLineSelect');
    const bucketSel = document.getElementById('addExpenseBucket');
    const otherWrap = document.getElementById('addEntryOtherWrap');
    const descWrap = document.getElementById('addEntryDescWrap');
    const otherIn = document.getElementById('addEntryOtherSpecify');
    const descIn = document.getElementById('addEntryDescription');
    const discSubWrapEl = document.getElementById('addEntryDiscSubWrap');
    const discSelReset = document.getElementById('addEntryDiscSubcategory');

    typeHidden.value = grp === 'CASH_FROM_BANK' ? 'CASH_FROM_BANK' : grp;
    keyHidden.value = '';
    expWrap.classList.add('d-none');
    lineWrap.classList.add('d-none');
    otherWrap.classList.add('d-none');
    if (discSubWrapEl) discSubWrapEl.classList.add('d-none');
    descWrap.classList.add('d-none');
    if (resetInputs) {
        otherIn.value = '';
        descIn.value = '';
    }
    if (discSelReset) {
        discSelReset.value = '';
        discSelReset.disabled = true;
        discSelReset.removeAttribute('name');
    }
    otherIn.disabled = true;
    descIn.disabled = true;
    otherIn.required = false;
    descIn.required = false;
    dailyCashSetAddDescriptionRequireStar(false);

    if (!grp) return;

    if (grp === 'CASH_FROM_BANK') {
        descWrap.classList.remove('d-none');
        descIn.disabled = false;
        dailyCashSetAddDescriptionRequireStar(true);
        return;
    }

    if (grp === 'CAPITAL') {
        const lines = (meta.capital && meta.capital.lines) ? meta.capital.lines : [];
        if (lines[0]) keyHidden.value = lines[0].key;
        descWrap.classList.remove('d-none');
        descIn.disabled = false;
        dailyCashSetAddDescriptionRequireStar(false);
        return;
    }

    if (grp === 'INCOME') {
        lineWrap.classList.remove('d-none');
        dailyCashFillLineBucketsSelect(lineSel, (meta.income && meta.income.buckets) ? meta.income.buckets : []);
        dailyCashSyncAddOtherDescVisibility();
        return;
    }

    if (grp === 'DISCRETIONARY' || grp === 'SAVINGS' || grp === 'OTHER') {
        lineWrap.classList.remove('d-none');
        const buckets = grp === 'DISCRETIONARY'
            ? (meta.discretionary && meta.discretionary.buckets)
            : grp === 'SAVINGS'
                ? (meta.savings && meta.savings.buckets)
                : (meta.other && meta.other.buckets);
        dailyCashFillLineBucketsSelect(lineSel, buckets || []);
        dailyCashSyncAddOtherDescVisibility();
        return;
    }

    if (grp === 'EXPENSES') {
        expWrap.classList.remove('d-none');
        lineWrap.classList.remove('d-none');
        dailyCashFillExpenseBucketSelect(bucketSel, meta);
        lineSel.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = '';
        ph.textContent = '— Choose expense section first —';
        lineSel.appendChild(ph);
        lineSel.disabled = bucketSel.value === '';
        dailyCashSyncAddOtherDescVisibility();
    }
}

function dailyCashAddEntrySubmit() {
    if (!dailyCashConfirmCarryImpact()) return false;
    const grp = document.getElementById('addEntryGroup').value;
    if (!grp) {
        window.alert('Choose an entry group.');
        return false;
    }
    if (grp !== 'CASH_FROM_BANK' && grp !== 'CAPITAL') {
        dailyCashSyncAddOtherDescVisibility();
        if (!document.getElementById('addEntryWorksheetCategoryKey').value) {
            window.alert('Choose a worksheet line.');
            return false;
        }
    }
    if (grp === 'DISCRETIONARY') {
        const lineSel = document.getElementById('addEntryLineSelect');
        const opt = lineSel.options[lineSel.selectedIndex];
        const needsOther = opt && opt.dataset.needsOther === '1';
        const descEl = document.getElementById('addEntryDescription');
        const otherEl = document.getElementById('addEntryOtherSpecify');
        const text = needsOther ? (otherEl.value || '').trim() : (descEl.value || '').trim();
        if (!text) {
            window.alert('Enter a description for this discretionary entry.');
            return false;
        }
    }
    return true;
}

function dailyCashSetPaneInputsDisabled(paneEl, disabled) {
    if (!paneEl) return;
    paneEl.querySelectorAll('input,select,textarea').forEach(function (el) {
        el.disabled = disabled;
    });
}

function dailyCashFillEditGroupSelect(sel) {
    if (!sel) return;
    sel.innerHTML = '';
    const opts = [
        ['', '— Choose —'],
        ['CASH_FROM_BANK', 'Cash from Bank — Withdrawals'],
        ['CAPITAL', 'Capital'],
        ['INCOME', 'Income'],
        ['EXPENSES', 'Expenses'],
        ['DISCRETIONARY', 'Discretionary'],
        ['SAVINGS', 'Savings'],
        ['OTHER', 'Other'],
    ];
    for (let i = 0; i < opts.length; i++) {
        const o = document.createElement('option');
        o.value = opts[i][0];
        o.textContent = opts[i][1];
        sel.appendChild(o);
    }
}

function dailyCashSyncEditOtherDescVisibility() {
    const grp = document.getElementById('editEntryGroup').value;
    const lineSel = document.getElementById('editEntryLineSelect');
    const keyHidden = document.getElementById('editEntryWorksheetCategoryKey');
    const otherWrap = document.getElementById('editEntryOtherWrap');
    const descWrap = document.getElementById('editEntryDescWrap');
    const otherIn = document.getElementById('editEntryOtherSpecify');
    const descIn = document.getElementById('editWsDesc');

    dailyCashSetEditEntryLineLabel(grp);

    if (grp === 'CASH_FROM_BANK' || grp === '' || grp === 'CAPITAL') {
        return;
    }

    const opt = lineSel.options[lineSel.selectedIndex];
    const needsOther = opt && opt.dataset.needsOther === '1';
    keyHidden.value = lineSel.value || '';

    otherWrap.classList.toggle('d-none', !needsOther);
    descWrap.classList.toggle('d-none', needsOther);
    otherIn.disabled = !needsOther;
    descIn.disabled = needsOther;
    otherIn.required = needsOther;
    descIn.required = needsOther ? false : (grp === 'DISCRETIONARY');
    dailyCashSetEditDescriptionRequireStar(needsOther ? false : (grp === 'DISCRETIONARY'));

    const discWrap = document.getElementById('editEntryDiscSubWrap');
    const discSel = document.getElementById('editEntryDiscSubcategory');
    const isDiscOther = grp === 'DISCRETIONARY' && needsOther;
    if (discWrap) {
        discWrap.classList.toggle('d-none', !isDiscOther);
    }
    if (discSel) {
        discSel.disabled = !isDiscOther;
        discSel.removeAttribute('name');
        if (isDiscOther) {
            discSel.setAttribute('name', 'subcategory_key');
        }
    }
}

function dailyCashSyncEditWorksheetForm(options) {
    const resetInputs = !options || options.resetInputs !== false;
    const meta = window.dailyCashWorksheetEntryMeta || {};
    const grpEl = document.getElementById('editEntryGroup');
    const grp = grpEl ? grpEl.value : '';
    const typeHidden = document.getElementById('editEntryResolvedType');
    const keyHidden = document.getElementById('editEntryWorksheetCategoryKey');
    const expWrap = document.getElementById('editEntryExpenseBucketWrap');
    const lineWrap = document.getElementById('editEntryLineWrap');
    const lineSel = document.getElementById('editEntryLineSelect');
    const bucketSel = document.getElementById('editExpenseBucket');
    const otherWrap = document.getElementById('editEntryOtherWrap');
    const descWrap = document.getElementById('editEntryDescWrap');
    const otherIn = document.getElementById('editEntryOtherSpecify');
    const descIn = document.getElementById('editWsDesc');
    const discSubWrapEdit = document.getElementById('editEntryDiscSubWrap');
    const discSelResetEd = document.getElementById('editEntryDiscSubcategory');

    typeHidden.value = grp === 'CASH_FROM_BANK' ? 'CASH_FROM_BANK' : grp;
    keyHidden.value = '';
    expWrap.classList.add('d-none');
    lineWrap.classList.add('d-none');
    otherWrap.classList.add('d-none');
    if (discSubWrapEdit) discSubWrapEdit.classList.add('d-none');
    descWrap.classList.add('d-none');
    if (resetInputs) {
        otherIn.value = '';
        descIn.value = '';
    }
    if (discSelResetEd) {
        discSelResetEd.value = '';
        discSelResetEd.disabled = true;
        discSelResetEd.removeAttribute('name');
    }
    otherIn.disabled = true;
    descIn.disabled = true;
    otherIn.required = false;
    descIn.required = false;
    dailyCashSetEditDescriptionRequireStar(false);

    if (!grp) return;

    if (grp === 'CASH_FROM_BANK') {
        descWrap.classList.remove('d-none');
        descIn.disabled = false;
        dailyCashSetEditDescriptionRequireStar(true);
        return;
    }

    if (grp === 'CAPITAL') {
        const lines = (meta.capital && meta.capital.lines) ? meta.capital.lines : [];
        if (lines[0]) keyHidden.value = lines[0].key;
        descWrap.classList.remove('d-none');
        descIn.disabled = false;
        dailyCashSetEditDescriptionRequireStar(false);
        return;
    }

    if (grp === 'INCOME') {
        lineWrap.classList.remove('d-none');
        dailyCashFillLineBucketsSelect(lineSel, (meta.income && meta.income.buckets) ? meta.income.buckets : []);
        dailyCashSyncEditOtherDescVisibility();
        return;
    }

    if (grp === 'DISCRETIONARY' || grp === 'SAVINGS' || grp === 'OTHER') {
        lineWrap.classList.remove('d-none');
        const buckets = grp === 'DISCRETIONARY'
            ? (meta.discretionary && meta.discretionary.buckets)
            : grp === 'SAVINGS'
                ? (meta.savings && meta.savings.buckets)
                : (meta.other && meta.other.buckets);
        dailyCashFillLineBucketsSelect(lineSel, buckets || []);
        dailyCashSyncEditOtherDescVisibility();
        return;
    }

    if (grp === 'EXPENSES') {
        expWrap.classList.remove('d-none');
        lineWrap.classList.remove('d-none');
        dailyCashFillExpenseBucketSelect(bucketSel, meta);
        lineSel.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = '';
        ph.textContent = '— Choose expense section first —';
        lineSel.appendChild(ph);
        lineSel.disabled = bucketSel.value === '';
        dailyCashSyncEditOtherDescVisibility();
    }
}

function dailyCashPrefillEditWorksheet(isBank, ledgerType, categoryKey, description, amount, storedDiscSub) {
    storedDiscSub = (typeof storedDiscSub === 'undefined' || storedDiscSub === null) ? '' : storedDiscSub;
    const meta = window.dailyCashWorksheetEntryMeta || {};
    const wsPane = document.getElementById('editEntryWorksheetPane');
    dailyCashSetPaneInputsDisabled(wsPane, false);

    const grp = document.getElementById('editEntryGroup');
    const lineSel = document.getElementById('editEntryLineSelect');
    const bucketSel = document.getElementById('editExpenseBucket');
    const otherIn = document.getElementById('editEntryOtherSpecify');
    const descIn = document.getElementById('editWsDesc');

    document.getElementById('editAmount').value = amount;

    if (isBank) {
        grp.value = 'CASH_FROM_BANK';
        dailyCashSyncEditWorksheetForm({ resetInputs: false });
        descIn.value = description;
        return;
    }

    let key = categoryKey || '';
    if (ledgerType === 'CAPITAL' && !key) {
        const lines = (meta.capital && meta.capital.lines) ? meta.capital.lines : [];
        key = lines[0] ? lines[0].key : '';
    }

    const managed = meta.managed_keys || [];
    if (managed.indexOf(key) === -1) {
        return;
    }

    if (ledgerType === 'CAPITAL') {
        grp.value = 'CAPITAL';
        dailyCashSyncEditWorksheetForm({ resetInputs: false });
        descIn.value = description;
        return;
    }

    if (ledgerType === 'INCOME') {
        grp.value = 'INCOME';
        dailyCashSyncEditWorksheetForm({ resetInputs: false });
        lineSel.value = key;
        if (lineSel.value !== key) {
            dailyCashFillLineBucketsSelect(lineSel, (meta.income && meta.income.buckets) ? meta.income.buckets : []);
            lineSel.value = key;
        }
        dailyCashSyncEditOtherDescVisibility();
        const opt = lineSel.options[lineSel.selectedIndex];
        const needsOther = opt && opt.dataset.needsOther === '1';
        if (needsOther) otherIn.value = description;
        else descIn.value = description;
        return;
    }

    if (ledgerType === 'DISCRETIONARY' || ledgerType === 'SAVINGS' || ledgerType === 'OTHER') {
        const grpVal = ledgerType;
        grp.value = grpVal;
        dailyCashSyncEditWorksheetForm({ resetInputs: false });
        const buckets =
            grpVal === 'DISCRETIONARY'
                ? (meta.discretionary && meta.discretionary.buckets)
                : grpVal === 'SAVINGS'
                    ? (meta.savings && meta.savings.buckets)
                    : (meta.other && meta.other.buckets);
        dailyCashFillLineBucketsSelect(lineSel, buckets || []);
        lineSel.value = key;
        if (lineSel.value !== key) {
            dailyCashFillLineBucketsSelect(lineSel, buckets || []);
            lineSel.value = key;
        }
        dailyCashSyncEditOtherDescVisibility();
        const opt = lineSel.options[lineSel.selectedIndex];
        const needsOther = opt && opt.dataset.needsOther === '1';
        if (needsOther) otherIn.value = description;
        else descIn.value = description;

        const dk = dailyCashDiscretionaryMetroOthersCategoryKey();
        const dsel = document.getElementById('editEntryDiscSubcategory');
        if (ledgerType === 'DISCRETIONARY' && dsel) {
            if (key === dk) {
                let sk = (storedDiscSub != null && storedDiscSub !== '') ? String(storedDiscSub) : '';
                if (sk === 'auto') sk = '';
                dsel.value = sk;
                if (!Array.from(dsel.options).some(function (o) { return o.value === dsel.value; })) {
                    dsel.value = '';
                }
            } else {
                dsel.value = '';
            }
        }
        return;
    }

    if (ledgerType === 'EXPENSES') {
        grp.value = 'EXPENSES';
        dailyCashSyncEditWorksheetForm({ resetInputs: false });
        const buckets = (meta.expense && meta.expense.buckets) ? meta.expense.buckets : [];
        let bidx = -1;
        for (let i = 0; i < buckets.length; i++) {
            const lines = buckets[i].lines || [];
            for (let j = 0; j < lines.length; j++) {
                if (lines[j].key === key) {
                    bidx = i;
                    break;
                }
            }
            if (bidx !== -1) break;
        }
        if (bidx !== -1) {
            bucketSel.value = String(bidx);
            dailyCashFillExpenseLinesFromBucket(lineSel, meta, bidx);
            lineSel.disabled = false;
            lineSel.value = key;
        }
        dailyCashSyncEditOtherDescVisibility();
        const opt = lineSel.options[lineSel.selectedIndex];
        const needsOther = opt && opt.dataset.needsOther === '1';
        if (needsOther) otherIn.value = description;
        else descIn.value = description;
    }
}

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
    const wrap = document.getElementById('editEntryCustomCategoryWrap');
    const customIn = document.getElementById('editEntryCategoryCustom');
    if (wrap) {
        const show = cur === 'income_plus' || cur === 'discretionary_plus' || cur === 'savings_plus';
        wrap.classList.toggle('d-none', !show);
    }
    if (customIn && (cur !== 'income_plus' && cur !== 'discretionary_plus' && cur !== 'savings_plus')) {
        customIn.value = '';
    }
}

function dailyCashSetLegacyClassifyVisibility(isBank) {
    document.querySelectorAll('.js-edit-entry-classify').forEach(function (el) {
        el.classList.toggle('d-none', isBank);
    });
    const sub = document.getElementById('editEntrySubcategory');
    const preset = document.getElementById('editEntryCategoryPreset');
    if (sub) sub.disabled = isBank;
    if (preset) preset.disabled = isBank;
}

function editEntry(id, type, description, amount, category, subKey) {
    document.getElementById('editEntryForm').action =
        '{{ url("daily-cash/".$dailyCash->id."/entries") }}/' + id;

    const meta = window.dailyCashWorksheetEntryMeta || {};
    const managed = meta.managed_keys || [];
    const cat = category ? String(category) : '';
    const formBank = type === 'INCOME' && cat === 'cash_from_bank';
    const worksheetKey = (type === 'CAPITAL' && cat === '') ? (((meta.capital && meta.capital.lines) || [])[0] || {}).key : cat;
    const useWorksheet = formBank || (worksheetKey && managed.indexOf(worksheetKey) !== -1);

    const wsPane = document.getElementById('editEntryWorksheetPane');
    const legPane = document.getElementById('editEntryLegacyPane');

    if (useWorksheet) {
        window.__dailyCashEditLegacyMode = false;
        wsPane.classList.remove('d-none');
        legPane.classList.add('d-none');
        dailyCashSetPaneInputsDisabled(wsPane, false);
        dailyCashSetPaneInputsDisabled(legPane, true);
        dailyCashFillEditGroupSelect(document.getElementById('editEntryGroup'));
        dailyCashPrefillEditWorksheet(formBank, type, worksheetKey, description, amount, subKey);
    } else {
        window.__dailyCashEditLegacyMode = true;
        wsPane.classList.add('d-none');
        legPane.classList.remove('d-none');
        dailyCashSetPaneInputsDisabled(wsPane, true);
        dailyCashSetPaneInputsDisabled(legPane, false);

        let formType = type;
        if (type === 'INCOME' && cat === 'cash_from_bank') {
            formType = 'CASH_FROM_BANK';
        }
        document.getElementById('editLegacyType').value = formType;
        document.getElementById('editLegacyDesc').value = description;
        document.getElementById('editAmount').value = amount;

        const bank = formType === 'CASH_FROM_BANK';
        dailyCashSetLegacyClassifyVisibility(bank);
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
    }

    new bootstrap.Modal(document.getElementById('editEntryModal')).show();
}

function dailyCashEditEntrySubmit() {
    if (!dailyCashConfirmCarryImpact()) return false;
    if (!window.__dailyCashEditLegacyMode) {
        const grp = document.getElementById('editEntryGroup').value;
        if (!grp) {
            window.alert('Choose an entry group.');
            return false;
        }
        if (grp !== 'CASH_FROM_BANK' && grp !== 'CAPITAL') {
            dailyCashSyncEditOtherDescVisibility();
            if (!document.getElementById('editEntryWorksheetCategoryKey').value) {
                window.alert('Choose a worksheet line.');
                return false;
            }
        }
        if (grp === 'DISCRETIONARY') {
            const lineSel = document.getElementById('editEntryLineSelect');
            const opt = lineSel.options[lineSel.selectedIndex];
            const needsOther = opt && opt.dataset.needsOther === '1';
            const descEl = document.getElementById('editWsDesc');
            const otherEl = document.getElementById('editEntryOtherSpecify');
            const text = needsOther ? (otherEl.value || '').trim() : (descEl.value || '').trim();
            if (!text) {
                window.alert('Enter a description for this discretionary entry.');
                return false;
            }
        }
    }
    return true;
}

(function () {
    const grp = document.getElementById('addEntryGroup');
    const bucketSel = document.getElementById('addExpenseBucket');
    const lineSel = document.getElementById('addEntryLineSelect');

    if (grp) {
        grp.addEventListener('change', function () {
            dailyCashSyncAddEntryForm({ resetInputs: true });
        });
    }
    if (bucketSel) {
        bucketSel.addEventListener('change', function () {
            const meta = window.dailyCashWorksheetEntryMeta || {};
            const idx = parseInt(bucketSel.value, 10);
            if (bucketSel.value === '' || isNaN(idx)) {
                lineSel.innerHTML = '';
                const ph = document.createElement('option');
                ph.value = '';
                ph.textContent = '— Choose expense section first —';
                lineSel.appendChild(ph);
                lineSel.disabled = true;
            } else {
                dailyCashFillExpenseLinesFromBucket(lineSel, meta, idx);
                lineSel.disabled = false;
            }
            dailyCashSyncAddOtherDescVisibility();
        });
    }
    if (lineSel) {
        lineSel.addEventListener('change', dailyCashSyncAddOtherDescVisibility);
    }

    const editGrp = document.getElementById('editEntryGroup');
    const editBucket = document.getElementById('editExpenseBucket');
    const editLine = document.getElementById('editEntryLineSelect');

    dailyCashFillEditGroupSelect(editGrp);

    if (editGrp) {
        editGrp.addEventListener('change', function () {
            dailyCashSyncEditWorksheetForm({ resetInputs: true });
        });
    }
    if (editBucket) {
        editBucket.addEventListener('change', function () {
            const meta = window.dailyCashWorksheetEntryMeta || {};
            const idx = parseInt(editBucket.value, 10);
            if (editBucket.value === '' || isNaN(idx)) {
                editLine.innerHTML = '';
                const ph = document.createElement('option');
                ph.value = '';
                ph.textContent = '— Choose expense section first —';
                editLine.appendChild(ph);
                editLine.disabled = true;
            } else {
                dailyCashFillExpenseLinesFromBucket(editLine, meta, idx);
                editLine.disabled = false;
            }
            dailyCashSyncEditOtherDescVisibility();
        });
    }
    if (editLine) {
        editLine.addEventListener('change', dailyCashSyncEditOtherDescVisibility);
    }

    const editLegacyType = document.getElementById('editLegacyType');
    const editPreset = document.getElementById('editEntryCategoryPreset');
    if (editLegacyType) {
        editLegacyType.addEventListener('change', function () {
            const bank = editLegacyType.value === 'CASH_FROM_BANK';
            dailyCashSetLegacyClassifyVisibility(bank);
            const ledgerType = bank ? 'INCOME' : editLegacyType.value;
            dailyCashFillSubcategorySelect(document.getElementById('editEntrySubcategory'), ledgerType);
            dailyCashSyncPresetOptionsForType(editPreset, ledgerType);
        });
    }
    if (editPreset) {
        editPreset.addEventListener('change', function () {
            const bank = editLegacyType && editLegacyType.value === 'CASH_FROM_BANK';
            const ledgerType = bank ? 'INCOME' : (editLegacyType ? editLegacyType.value : 'INCOME');
            dailyCashSyncPresetOptionsForType(editPreset, ledgerType);
        });
    }

    document.getElementById('addEntryModal')?.addEventListener('show.bs.modal', function () {
        const g = document.getElementById('addEntryGroup');
        if (g) g.selectedIndex = 0;
        dailyCashSyncAddEntryForm({ resetInputs: true });
    });
})();
</script>
@endpush
@endsection
