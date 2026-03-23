@extends('layouts.report')
@php $reportTitle = 'Remittance'; @endphp
@section('report-content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Remittance Report</h5>
</div>

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.remittance') }}"
              class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Branch</label>
                <select name="branch_id" class="form-select" required>
                    <option value="">-- Select Branch --</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}"
                            {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">From</label>
                <input type="date" name="from" class="form-control"
                       value="{{ $from }}" id="dateFrom">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">To</label>
                <input type="date" name="to" class="form-control"
                       value="{{ $to }}" id="dateTo">
            </div>
            <div class="col-md-3 d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Generate
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        onclick="setWeek(0)">This Week</button>
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        onclick="setWeek(-1)">Last Week</button>
            </div>
        </form>
    </div>
</div>

@if(isset($selectedBranch) && $selectedBranch)

<div class="alert alert-light border small mb-4">
    <i class="bi bi-info-circle text-primary me-1"></i>
    <strong>How to read this report:</strong> DRs listed are deliveries in the date range. <strong>Sold</strong> is
    branch sales recorded. <strong>Paid</strong> is cash remitted to the warehouse — entered
    <strong>with each sale</strong> on the <em>Record Sales</em> review step (shown as linked to that sale date in the
    detail below).
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h6 class="fw-bold mb-0">
            <i class="bi bi-shop"></i> {{ $selectedBranch->name }}
        </h6>
        <small class="text-muted">
            Period: {{ \Carbon\Carbon::parse($from)->format('M d, Y') }}
            to {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}
        </small>
    </div>
    <button onclick="window.print()"
            class="btn btn-sm btn-outline-secondary btn-action">
        <i class="bi bi-printer"></i> Print
    </button>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-2">
                <div class="text-muted small">Delivered</div>
                <div class="fw-bold text-primary">
                    ₱{{ number_format($totals['delivered_amt'], 2) }}
                </div>
                <div style="font-size:0.7rem;" class="text-muted">this period</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-2">
                <div class="text-muted small">Sold</div>
                <div class="fw-bold text-success">
                    ₱{{ number_format($totals['sold_amt'], 2) }}
                </div>
                <div style="font-size:0.7rem;" class="text-muted">recorded sales</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-2">
                <div class="text-muted small">Pull Outs</div>
                <div class="fw-bold text-warning">
                    ₱{{ number_format($totals['returned_amt'], 2) }}
                </div>
                <div style="font-size:0.7rem;" class="text-muted">returned</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-2">
                <div class="text-muted small">Paid (remitted)</div>
                <div class="fw-bold text-success">
                    ₱{{ number_format($totals['paid_amt'], 2) }}
                </div>
                <div style="font-size:0.65rem;" class="text-muted">
                    with sales: ₱{{ number_format($totals['paid_with_sale'] ?? 0, 2) }}
                </div>
                @if(($totals['paid_additional'] ?? 0) > 0.009)
                <div style="font-size:0.65rem;" class="text-muted">
                    other records: ₱{{ number_format($totals['paid_additional'], 2) }}
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center border-danger">
            <div class="card-body py-2">
                <div class="text-muted small">Period Balance</div>
                <div class="fw-bold text-danger">
                    ₱{{ number_format($totals['balance'], 2) }}
                </div>
                <div style="font-size:0.7rem;" class="text-muted">still owed</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center" style="border:1.5px solid #dc2626;">
            <div class="card-body py-2">
                <div class="text-muted small">All-Time Balance</div>
                <div class="fw-bold text-danger fs-6">
                    ₱{{ number_format($totals['all_time_balance'], 2) }}
                </div>
                <div style="font-size:0.7rem;" class="text-muted">total outstanding</div>
            </div>
        </div>
    </div>
</div>

{{-- Per DR Remittance Table --}}
<div class="card">
    <div class="card-header fw-semibold">
        <i class="bi bi-table"></i> Per DR Remittance Detail
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0">
            <thead style="background:#004d3b;">
                <tr>
                    <th style="padding:10px 14px; color:#fff; border:none; width:10%;">DR #</th>
                    <th style="padding:10px 14px; color:#fff; border:none; width:10%;">Date</th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right; width:13%;">DR Value</th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right; width:12%;">Sold</th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right; width:12%;">Pull Outs</th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right; width:12%;">Paid</th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right; width:12%;">Balance</th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:center; width:8%;">Status</th>
                    <th style="padding:10px 14px; color:#fff; border:none; width:5%;"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>
                        <a href="{{ route('consignment.show', $row['receivable_id']) }}"
                           class="badge bg-dark font-monospace text-decoration-none">
                            {{ $row['dr_number'] }}
                        </a>
                    </td>
                    <td class="small">{{ $row['delivery_date'] }}</td>
                    <td class="text-end fw-semibold text-primary">
                        ₱{{ number_format($row['delivered_amt'], 2) }}
                    </td>
                    <td class="text-end text-success">
                        ₱{{ number_format($row['sold_amt'], 2) }}
                    </td>
                    <td class="text-end {{ $row['returned_amt'] > 0 ? 'text-warning' : 'text-muted' }}">
                        {{ $row['returned_amt'] > 0 ? '₱'.number_format($row['returned_amt'], 2) : '—' }}
                    </td>
                    <td class="text-end text-success fw-semibold">
                        ₱{{ number_format($row['paid_amt'], 2) }}
                    </td>
                    <td class="text-end fw-bold {{ $row['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                        ₱{{ number_format($row['balance'], 2) }}
                    </td>
                    <td class="text-center">
                        @php
                            $map = [
                                'open'    => 'bg-danger',
                                'partial' => 'bg-warning text-dark',
                                'paid'    => 'bg-success',
                            ];
                        @endphp
                        <span class="badge {{ $map[$row['status']] }}">
                            {{ ucfirst($row['status']) }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($row['payments']->count())
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    style="padding:2px 7px; font-size:0.75rem;"
                                    onclick="togglePayments({{ $row['receivable_id'] }})">
                                <i class="bi bi-chevron-right"
                                   id="pay-chevron-{{ $row['receivable_id'] }}"
                                   style="transition:transform 0.2s;"></i>
                            </button>
                        @endif
                    </td>
                </tr>

                {{-- Payment breakdown --}}
                @if($row['payments']->count())
                <tr id="pay-row-{{ $row['receivable_id'] }}" style="display:none; background:#f8fafc;">
                    <td colspan="9" style="padding:0;">
                        <table class="table table-sm mb-0" style="background:#f8fafc;">
                            <tbody>
                            @foreach($row['payments'] as $pay)
                                <tr>
                                    <td style="width:10%; padding:6px 14px; border:none;"></td>
                                    <td style="padding:6px 14px; border:none; color:#6b7280; font-size:0.82rem;">
                                        <i class="bi bi-arrow-return-right me-1"></i>
                                        @if(!empty($pay['with_sale']))
                                            <span class="badge bg-success me-1" style="font-size:0.65rem;">Sale</span>
                                            @if(!empty($pay['sale_period']))
                                                <span class="text-muted">{{ $pay['sale_period'] }}</span> ·
                                            @endif
                                        @else
                                            <span class="text-muted me-1" style="font-size:0.75rem;">Other record</span>
                                        @endif
                                        Remitted {{ $pay['date'] }}
                                        @if($pay['reference'])
                                            <span class="badge bg-secondary ms-1">
                                                {{ $pay['reference'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding:6px 14px; border:none; text-align:right; font-weight:600; color:#007A5E; font-size:0.82rem;">
                                        ₱{{ number_format($pay['amount'], 2) }}
                                    </td>
                                    <td colspan="6" style="border:none;"></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
                @endif

            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No deliveries for this branch in the selected period.
                    </td>
                </tr>
            @endforelse
            </tbody>
            @if($rows->count())
            <tfoot style="background:#004d3b;">
                <tr>
                    <th colspan="2" style="padding:10px 14px; color:#fff; border:none;">TOTAL</th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right;">
                        ₱{{ number_format($totals['delivered_amt'], 2) }}
                    </th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right;">
                        ₱{{ number_format($totals['sold_amt'], 2) }}
                    </th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right;">
                        ₱{{ number_format($totals['returned_amt'], 2) }}
                    </th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right;">
                        ₱{{ number_format($totals['paid_amt'], 2) }}
                    </th>
                    <th style="padding:10px 14px; color:#fff; border:none; text-align:right;">
                        ₱{{ number_format($totals['balance'], 2) }}
                    </th>
                    <th colspan="2" style="border:none;"></th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@endif

@endsection
@push('scripts')
<script>
function setWeek(offset) {
    const now = new Date();
    const day = now.getDay();
    const mon = new Date(now);
    mon.setDate(now.getDate() - ((day + 6) % 7) + (offset * 7));
    const sun = new Date(mon);
    sun.setDate(mon.getDate() + 6);
    const fmt = d => d.toISOString().split('T')[0];
    document.getElementById('dateFrom').value = fmt(mon);
    document.getElementById('dateTo').value   = fmt(sun);
}

function togglePayments(id) {
    const row     = document.getElementById('pay-row-' + id);
    const chevron = document.getElementById('pay-chevron-' + id);
    const isOpen  = row.style.display !== 'none';
    row.style.display     = isOpen ? 'none' : 'table-row';
    chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
}
</script>
@endpush