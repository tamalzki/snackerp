@extends('layouts.app')
@section('title', $dailyCash->date->format('F d, Y') . ' — Daily Cash')

@section('content')

{{-- Date tab navigation --}}
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    @if($prev)
    <a href="{{ route('daily-cash.show', $prev) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-chevron-left"></i> {{ $prev->date->format('M d') }}
    </a>
    @endif

    @foreach($recentDays->reverse() as $rd)
    <a href="{{ route('daily-cash.show', $rd) }}"
       class="btn btn-sm {{ $rd->id === $dailyCash->id ? 'btn-primary' : 'btn-outline-secondary' }}">
        {{ $rd->date->format('M d') }}
        @if($rd->date->isToday()) <span class="badge bg-light text-dark ms-1" style="font-size:0.6rem">Today</span> @endif
    </a>
    @endforeach

    @if($next)
    <a href="{{ route('daily-cash.show', $next) }}" class="btn btn-sm btn-outline-secondary">
        {{ $next->date->format('M d') }} <i class="bi bi-chevron-right"></i>
    </a>
    @endif

    <a href="{{ route('daily-cash.index', ['tab' => 'monthly']) }}" class="btn btn-sm btn-outline-secondary ms-auto">
        <i class="bi bi-calendar-month"></i> Monthly
    </a>
    <a href="{{ route('daily-cash.index', ['tab' => 'annual']) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-bar-chart-line"></i> Annual
    </a>
    <a href="{{ route('daily-cash.today') }}" class="btn btn-sm btn-success">
        <i class="bi bi-calendar-check"></i> Today
    </a>
</div>

<div class="row g-3">

    {{-- LEFT: Daily Summary --}}
    <div class="col-lg-3">

        {{-- Total Available Cash = opening + net (closing balance) --}}
        @php $closing = (float)$dailyCash->opening_balance + $dailyCash->net(); @endphp
        <div class="card mb-3">
            <div class="card-header py-2 d-flex align-items-center gap-2">
                <i class="bi bi-wallet2 text-success"></i>
                <span class="fw-bold small">TOTAL AVAILABLE CASH</span>
            </div>
            <div class="card-body py-3 text-center">
                {{-- Big amount --}}
                <div style="font-size:1.8rem;font-weight:700;color:#007A5E;">
                    ₱{{ number_format($closing, 2) }}
                </div>
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
                        <tr>
                            <td class="ps-3"><span class="badge bg-primary" style="font-size:0.65rem;">Capital</span></td>
                            <td class="text-end pe-3">₱{{ number_format($dailyCash->capital(), 2) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-3"><span class="badge bg-success" style="font-size:0.65rem;">Income</span></td>
                            <td class="text-end pe-3 text-success fw-bold">₱{{ number_format($dailyCash->income(), 2) }}</td>
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
                            <td class="ps-3"><span class="badge bg-info text-dark" style="font-size:0.65rem;">Savings</span></td>
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
                <i class="bi bi-bank"></i> Deposit to Bank / Cash Account
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
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEntryModal">
                    <i class="bi bi-plus-lg"></i> Add Entry
                </button>
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
                                            onclick="editEntry({{ $entry->id }},'{{ $entry->type }}',{{ json_encode($entry->description) }},{{ $entry->amount }})">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <form method="POST"
                                          action="{{ route('daily-cash.entries.destroy', [$dailyCash, $entry]) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this entry?')">
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
        <form method="POST" action="{{ route('daily-cash.entries.store', $dailyCash) }}">
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
                            <select name="type" class="form-select form-select-sm" required>
                                @foreach(\App\Models\DailyCashEntry::$types as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <input type="text" name="description" class="form-control form-control-sm"
                                   placeholder="e.g. SUMAN, WATER REFILL, LABOR…" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Amount (₱)</label>
                            <input type="number" name="amount" step="0.01" min="0"
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
        <form method="POST" action="{{ route('daily-cash.update', $dailyCash) }}">
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
                           value="{{ (float) $closing }}"
                           class="form-control"
                           placeholder="Enter actual cash on hand"
                           required>
                    <div class="form-text mt-1">Enter the actual cash amount you have on hand. The system will adjust automatically.</div>
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
        <form method="POST" action="{{ route('daily-cash.deposit', $dailyCash) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-bank text-primary"></i> Deposit to Bank</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="source_type" value="bank">
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
                                   class="form-control form-control-sm"
                                   placeholder="0.00" required>
                            @php $closingHint = (float)$dailyCash->opening_balance + $dailyCash->net(); @endphp
                            <div class="form-text">Total available cash today: <strong>₱{{ number_format($closingHint, 2) }}</strong></div>
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
        <form method="POST" id="editEntryForm">
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
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <input type="text" name="description" id="editDesc"
                                   class="form-control form-control-sm" required>
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
<script>
function editEntry(id, type, description, amount) {
    document.getElementById('editEntryForm').action =
        '{{ url("daily-cash/".$dailyCash->id."/entries") }}/' + id;
    document.getElementById('editType').value   = type;
    document.getElementById('editDesc').value   = description;
    document.getElementById('editAmount').value = amount;
    new bootstrap.Modal(document.getElementById('editEntryModal')).show();
}

</script>
@endpush
@endsection
