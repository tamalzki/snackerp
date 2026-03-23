@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

{{-- ALERTS --}}
@if($lowStockMaterials->count() || $lowStockProducts->count() || $expiringSoon->count())
<div class="alert alert-warning alert-dismissible fade show mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Attention required:</strong>
    @if($lowStockMaterials->count())
        <span class="badge bg-danger ms-1">
            {{ $lowStockMaterials->count() }} raw material(s) low stock
        </span>
    @endif
    @if($lowStockProducts->count())
        <span class="badge bg-danger ms-1">
            {{ $lowStockProducts->count() }} product(s) low stock
        </span>
    @endif
    @if($expiringSoon->count())
        <span class="badge bg-warning text-dark ms-1">
            {{ $expiringSoon->count() }} batch(es) expiring soon
        </span>
    @endif
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- KPI CARDS --}}
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Revenue (MTD)</div>
                <div class="fw-bold fs-5 text-success">
                    ₱{{ number_format($totalRevenue, 0) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Gross Profit</div>
                <div class="fw-bold fs-5 text-primary">
                    ₱{{ number_format($grossProfit, 0) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Expenses (MTD)</div>
                <div class="fw-bold fs-5 text-danger">
                    ₱{{ number_format($totalExpenses, 0) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Net Profit</div>
                <div class="fw-bold fs-5 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                    ₱{{ number_format($netProfit, 0) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Cash + Bank</div>
                <div class="fw-bold fs-5 text-info">
                    ₱{{ number_format($totalBalance, 0) }}
                </div>
                <div class="text-muted" style="font-size:0.7rem">
                    Cash: ₱{{ number_format($cashBalance, 0) }} |
                    Bank: ₱{{ number_format($bankBalance, 0) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Total Stock Value</div>
                <div class="fw-bold fs-5 text-secondary">
                    ₱{{ number_format($warehouseValue + $branchValue, 0) }}
                </div>
                <div class="text-muted" style="font-size:0.7rem">
                    WH: ₱{{ number_format($warehouseValue, 0) }} |
                    BR: ₱{{ number_format($branchValue, 0) }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SALES CHART + ALERTS --}}
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Sales — Last 7 Days</div>
            <div class="card-body">
                <canvas id="salesChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header text-danger fw-semibold">
                <i class="bi bi-exclamation-triangle-fill"></i> Alerts
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
    @foreach($expiringSoon as $b)
        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
            <div>
                <span class="badge bg-warning text-dark me-1">Expiring</span>
                {{ $b->finishedProduct->name }}
            </div>
            <small class="text-muted">{{ $b->expiry_date->format('M d') }}</small>
        </li>
    @endforeach
    @foreach($lowStockMaterials as $m)
        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
            <div>
                <span class="badge bg-danger me-1">Low</span>
                {{ $m->name }}
            </div>
            <small class="text-muted">
                {{ qty_fmt($m->stock_quantity) }} {{ $m->unit }}
            </small>
        </li>
    @endforeach
    @foreach($lowStockProducts as $p)
        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
            <div>
                <span class="badge bg-danger me-1">Low</span>
                {{ $p->name }}
            </div>
            <small class="text-muted">
                {{ number_format($p->current_stock, 2) }} pcs
            </small>
        </li>
    @endforeach
    @if(!$expiringSoon->count() && !$lowStockMaterials->count() && !$lowStockProducts->count())
        <li class="list-group-item text-center text-muted py-4">
            <i class="bi bi-check-circle text-success"></i> All clear!
        </li>
    @endif
</ul>
            </div>
        </div>
    </div>
</div>

{{-- RECENT ACTIVITY --}}
<div class="row g-3">
    {{-- Recent Sales --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
                <span>Recent Sales</span>
                <span class="text-muted small">
                    <a href="{{ route('consignment.index') }}">Consignment</a>
                    <span class="text-muted">·</span>
                    <a href="{{ route('sales.index') }}">Legacy</a>
                </span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($recentSales as $s)
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <div>
                                <div class="fw-semibold small">
                                    {{ $s->branch->name ?? '—' }}
                                    @if(($s->source ?? 'legacy') === 'consignment')
                                        <span class="badge bg-primary ms-1" style="font-size:0.65rem">DR</span>
                                    @else
                                        <span class="badge bg-secondary ms-1" style="font-size:0.65rem">Direct</span>
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size:0.75rem">
                                    @if(($s->source ?? 'legacy') === 'consignment' && !empty($s->period_label))
                                        {{ $s->period_label }}
                                    @else
                                        {{ $s->sale_date->format('M d, Y') }}
                                    @endif
                                </div>
                            </div>
                            <span class="text-success fw-semibold small">
                                ₱{{ number_format($s->total_amount, 2) }}
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-3">No sales yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- Recent Production --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>Recent Production</span>
                <a href="{{ route('production.index') }}" class="text-muted small">View all</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($recentProduction as $b)
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <div>
                                <div class="fw-semibold small">
                                    {{ $b->finishedProduct->name }}
                                </div>
                                <div class="text-muted" style="font-size:0.75rem">
                                    {{ $b->production_date->format('M d, Y') }}
                                </div>
                            </div>
                            <span class="text-primary fw-semibold small">
                                {{ qty_fmt($b->actual_output_qty) }} pcs
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-3">
                            No batches yet.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- Recent Transfers --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>Recent Transfers</span>
                <a href="{{ route('transfers.index') }}" class="text-muted small">View all</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($recentTransfers as $t)
    <li class="list-group-item py-2">
        <div class="d-flex justify-content-between align-items-center">
            <div class="fw-semibold small">{{ $t->branch->name }}</div>
            <div class="text-muted small">
                {{ $t->items_count ?? 0 }} product(s)
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-1">
            <div class="text-muted" style="font-size:0.75rem">
                {{ $t->transfer_date->format('M d, Y') }}
            </div>
            @if($t->source_branch_id)
                <span style="font-size:0.72rem" class="text-warning">
                    Branch → Branch
                </span>
            @else
                <span style="font-size:0.72rem" class="text-primary">
                    Warehouse → Branch
                </span>
            @endif
        </div>
    </li>
@empty
    <li class="list-group-item text-center text-muted py-3">
        No transfers yet.
    </li>
@endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
const labels = @json($salesChart->pluck('date'));
const data   = @json($salesChart->pluck('total'));

new Chart(document.getElementById('salesChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Sales (₱)',
            data: data,
            backgroundColor: 'rgba(37, 99, 235, 0.7)',
            borderColor: '#2563eb',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => '₱' + ctx.parsed.y.toLocaleString('en-PH', {
                        minimumFractionDigits: 2
                    })
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
</script>
@endpush