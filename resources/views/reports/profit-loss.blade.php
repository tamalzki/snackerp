@extends('layouts.report')
@php $reportTitle = 'Profit & Loss'; @endphp
@section('report-content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Profit & Loss Report</h5>
</div>

{{-- Filters --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.profit-loss') }}"
              class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">From</label>
                <input type="date" name="from" class="form-control" value="{{ $from }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">To</label>
                <input type="date" name="to" class="form-control" value="{{ $to }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-light border small mb-4">
    <i class="bi bi-calendar-range text-primary me-1"></i>
    Consignment revenue includes sales whose <strong>period</strong> overlaps the filter. The monthly trend chart
    assigns each entry to the <strong>month of “period to”</strong> (end of the range).
</div>

{{-- Net Profit Banner --}}
<div class="alert {{ $netProfit >= 0 ? 'alert-success' : 'alert-danger' }} mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-{{ $netProfit >= 0 ? 'graph-up-arrow' : 'graph-down-arrow' }} me-2"></i>
            <strong>Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</strong>
            —
            {{ \Carbon\Carbon::parse($from)->format('M d') }}
            to {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}
        </div>
        <div class="fw-bold fs-5">₱{{ number_format($netProfit, 2) }}</div>
    </div>
</div>

<div class="row g-3 mb-4">

    {{-- P&L Summary --}}
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header fw-bold">Income Statement</div>
            <div class="card-body p-0">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted ps-3">Sales Revenue</td>
                            <td class="text-end fw-bold text-success pe-3">
                                ₱{{ number_format($totalRevenue, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-3 small">— Cost of Goods Sold (COGS)</td>
                            <td class="text-end text-danger pe-3">
                                (₱{{ number_format($totalCOGS, 2) }})
                            </td>
                        </tr>
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td class="fw-semibold ps-3">Gross Profit</td>
                            <td class="text-end fw-bold text-primary pe-3">
                                ₱{{ number_format($grossProfit, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-3 small">— Operating Expenses</td>
                            <td class="text-end text-danger pe-3">
                                (₱{{ number_format($totalExpenses, 2) }})
                            </td>
                        </tr>
                        <tr class="{{ $netProfit >= 0 ? 'table-success' : 'table-danger' }}"
                            style="border-top:2px solid #e5e7eb;">
                            <td class="fw-bold ps-3 fs-6">Net Profit / Loss</td>
                            <td class="text-end fw-bold fs-6 pe-3">
                                ₱{{ number_format($netProfit, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Cash Flow Note --}}
            <div class="card-footer" style="background:#f8fafc;">
                <div class="small fw-semibold text-muted mb-2">
                    <i class="bi bi-cash-coin"></i> Cash Flow Note
                </div>
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted small ps-0">Cash received (consignment remittances)</td>
                        <td class="text-end fw-semibold text-success small">
                            ₱{{ number_format($paymentsReceived, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small ps-0">Pull Outs / Returns</td>
                        <td class="text-end fw-semibold text-warning small">
                            ₱{{ number_format($pullouts, 2) }}
                        </td>
                    </tr>
                    <tr style="border-top:1px solid #e5e7eb;">
                        <td class="text-muted small ps-0">Outstanding Receivables</td>
                        <td class="text-end fw-semibold text-danger small">
                            ₱{{ number_format($outstanding, 2) }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Expenses by Category --}}
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header">Expenses by Category</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($expensesByCategory as $e)
                        <tr>
                            <td>
                                <span class="badge bg-secondary">{{ $e['category'] }}</span>
                            </td>
                            <td class="text-end fw-semibold text-danger">
                                ₱{{ number_format($e['total'], 2) }}
                            </td>
                            <td class="text-end">
                                @if($totalExpenses > 0)
                                    {{ number_format(($e['total'] / $totalExpenses) * 100, 1) }}%
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">
                                No expenses for this period.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                    @if($expensesByCategory->count())
                    <tfoot class="table-dark">
                        <tr>
                            <th>Total</th>
                            <th class="text-end">₱{{ number_format($totalExpenses, 2) }}</th>
                            <th class="text-end">100%</th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Monthly Trend --}}
@if($monthlySales->count() > 1)
<div class="card mb-4">
    <div class="card-header">Monthly Revenue & Profit Trend</div>
    <div class="card-body">
        <canvas id="plChart" height="80"></canvas>
    </div>
</div>
@endif

@endsection
@push('scripts')
<script>
@if($monthlySales->count() > 1)
const labels  = @json($monthlySales->pluck('label'));
const revenue = @json($monthlySales->pluck('revenue'));
const cost    = @json($monthlySales->pluck('cost'));
const profit  = @json($monthlySales->pluck('profit'));

new Chart(document.getElementById('plChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Revenue',
                data: revenue,
                backgroundColor: 'rgba(0, 122, 94, 0.7)',
                borderColor: '#007A5E',
                borderWidth: 1,
                borderRadius: 4,
            },
            {
                label: 'COGS',
                data: cost,
                backgroundColor: 'rgba(220, 38, 38, 0.6)',
                borderColor: '#dc2626',
                borderWidth: 1,
                borderRadius: 4,
            },
            {
                label: 'Gross Profit',
                data: profit,
                type: 'line',
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 2,
                pointRadius: 4,
                fill: false,
                tension: 0.3,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label + ': ₱' +
                        ctx.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2 })
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: val => '₱' + val.toLocaleString()
                }
            }
        }
    }
});
@endif
</script>
@endpush