@extends('layouts.app')
@section('title', 'DR ' . ($receivable->dr_number ?? '#' . $receivable->id))
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">
            @if($receivable->dr_number)
                <span class="font-monospace">{{ $receivable->dr_number }}</span>
            @else
                DR #{{ $receivable->id }}
            @endif
        </h5>
        <small class="text-muted">
            <i class="bi bi-shop"></i> {{ $receivable->branch->name }} —
            {{ $receivable->delivery_date->format('M d, Y') }}
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('consignment.branch-transfer.create', $receivable->branch) }}"
           class="btn btn-sm btn-info btn-action text-white">
            <i class="bi bi-arrow-left-right"></i> Transfer Products
        </a>
        @if($receivable->status !== 'paid')
            <a href="{{ route('consignment.sale.create', $receivable) }}"
               class="btn btn-sm btn-success btn-action">
                <i class="bi bi-receipt"></i> Record Sales
            </a>
            <a href="{{ route('consignment.pullout.create', $receivable) }}"
               class="btn btn-sm btn-warning btn-action">
                <i class="bi bi-arrow-return-left"></i> Pull Out
            </a>
        @endif
        <a href="{{ route('consignment.branch', $receivable->branch) }}"
           class="btn btn-sm btn-secondary btn-action">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

{{-- Status Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">DR Value</div>
                <div class="fw-bold fs-5 text-primary">
                    ₱{{ number_format($receivable->total_amount, 2) }}
                </div>
                <div class="text-muted" style="font-size:0.72rem;">at selling price</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Amount Paid</div>
                <div class="fw-bold fs-5 text-success">
                    ₱{{ number_format($receivable->amount_paid, 2) }}
                </div>
                <div class="text-muted" style="font-size:0.72rem;">cash remitted (total)</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Returned</div>
                <div class="fw-bold fs-5 text-warning">
                    ₱{{ number_format($receivable->amount_returned, 2) }}
                </div>
                <div class="text-muted" style="font-size:0.72rem;">pull outs</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center {{ $receivable->balance > 0 ? 'border-danger' : 'border-success' }}">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Balance</div>
                <div class="fw-bold fs-5 {{ $receivable->balance > 0 ? 'text-danger' : 'text-success' }}">
                    ₱{{ number_format($receivable->balance, 2) }}
                </div>
                <div class="text-muted" style="font-size:0.72rem;">still owed</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Status</div>
                @php
                    $map = [
                        'open'    => ['bg-danger',  'Open'],
                        'partial' => ['bg-warning text-dark', 'Partial'],
                        'paid'    => ['bg-success', 'Paid'],
                    ];
                    [$cls, $lbl] = $map[$receivable->status];
                @endphp
                <span class="badge {{ $cls }} fs-6 px-3 py-2">{{ $lbl }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- LEFT COLUMN --}}
    <div class="col-md-8">

        {{-- Delivered Products (summary + modal) --}}
        @php $deliveredLines = $receivable->transfer->items; $deliveredCount = $deliveredLines->count(); @endphp
        <div class="card mb-3">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="bi bi-truck"></i> Delivered Products</span>
                <span class="badge bg-secondary">{{ $deliveredCount }} {{ $deliveredCount === 1 ? 'product' : 'products' }}</span>
            </div>
            <div class="card-body py-4 text-center">
                <p class="text-muted small mb-3 mb-md-0">
                    {{ $deliveredCount }} product line(s) on this delivery receipt.
                </p>
                <button type="button" class="btn btn-outline-primary btn-sm btn-action" data-bs-toggle="modal" data-bs-target="#deliveredProductsModal">
                    <i class="bi bi-box-seam"></i> View products delivered
                </button>
            </div>
        </div>

        <div class="modal fade" id="deliveredProductsModal" tabindex="-1" aria-labelledby="deliveredProductsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deliveredProductsModalLabel">Products delivered on this DR</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Qty delivered</th>
                                    <th class="text-end">Selling price</th>
                                    <th class="text-end">DR value</th>
                                    <th class="text-center">Branch stock</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($deliveredLines as $item)
                                @php $inv = $branchInventory[$item->finished_product_id] ?? null; @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $item->finishedProduct->name }}</td>
                                    <td class="text-center">{{ qty_fmt($item->quantity) }}</td>
                                    <td class="text-end">₱{{ number_format($item->finishedProduct->selling_price, 2) }}</td>
                                    <td class="text-end fw-semibold text-primary">
                                        ₱{{ number_format($item->quantity * $item->finishedProduct->selling_price, 2) }}
                                    </td>
                                    <td class="text-center">
                                        @php $stock = $inv ? $inv->stock_quantity : 0; @endphp
                                        <span class="badge {{ $stock > 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ qty_fmt($stock) }} pcs
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sales Records --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="bi bi-receipt"></i> Sales Records
                    <span class="text-muted fw-normal small ms-1">(products sold by branch)</span>
                </span>
                @if($receivable->status !== 'paid')
                    <a href="{{ route('consignment.sale.create', $receivable) }}"
                       class="btn btn-sm btn-success btn-action">
                        <i class="bi bi-plus"></i> Record Sales
                    </a>
                @endif
            </div>

            <div class="card-body p-0">
                @forelse($receivable->sales as $sale)

                    {{-- Clickable Sale Header --}}
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center"
                         style="background:#f8fafc; border-bottom:1px solid #e5e7eb;
                                cursor:pointer; user-select:none;"
                         onclick="toggleSale({{ $sale->id }})">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <i class="bi bi-chevron-right text-muted small"
                               id="chevron-{{ $sale->id }}"
                               style="transition:transform 0.2s;"></i>
                            <span class="fw-semibold small">
                                {{ $sale->periodLabel() }}
                            </span>
                            <span class="badge bg-secondary" style="font-size:0.72rem;">
                                {{ $sale->items->count() }} items
                            </span>
                            @php
                                $remitThisSale = $receivable->payments
                                    ->where('consignment_sale_id', $sale->id)
                                    ->sum('amount');
                            @endphp
                            @if((float) $remitThisSale > 0)
                                <span class="badge bg-success" style="font-size:0.72rem;">
                                    Remitted ₱{{ number_format($remitThisSale, 2) }}
                                </span>
                            @endif
                            @if($sale->notes)
                                <span class="text-muted small">— {{ $sale->notes }}</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="text-success fw-semibold small">
                                ₱{{ number_format($sale->total_amount, 2) }}
                            </span>
                            <span class="text-muted small">
                                Cost: ₱{{ number_format($sale->total_cost, 2) }}
                            </span>
                            <span class="text-primary fw-semibold small">
                                Profit: ₱{{ number_format($sale->gross_profit, 2) }}
                            </span>
                            <span class="text-muted small fst-italic"
                                  id="toggle-hint-{{ $sale->id }}"
                                  style="font-size:0.72rem;">
                                Click to view ▾
                            </span>
                        </div>
                    </div>

                    {{-- Collapsible Items --}}
                    <div id="sale-items-{{ $sale->id }}" style="display:none; background:#fff;">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr style="background:#fff; border-bottom:2px solid #e5e7eb;">
                                    <td style="padding:8px 24px; font-size:0.78rem; font-weight:600; color:#6b7280; border:none;">Product</td>
                                    <td style="padding:8px 24px; font-size:0.78rem; font-weight:600; color:#6b7280; border:none; text-align:center;">Qty Sold</td>
                                    <td style="padding:8px 24px; font-size:0.78rem; font-weight:600; color:#6b7280; border:none; text-align:right;">Unit Price</td>
                                    <td style="padding:8px 24px; font-size:0.78rem; font-weight:600; color:#6b7280; border:none; text-align:right;">Total</td>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($sale->items as $item)
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:8px 24px; border:none; font-weight:500; font-size:0.88rem; color:#111827;">
                                        {{ $item->finishedProduct->name }}
                                    </td>
                                    <td style="padding:8px 24px; border:none; text-align:center; font-size:0.88rem; color:#374151;">
                                        {{ qty_fmt($item->qty_sold) }}
                                    </td>
                                    <td style="padding:8px 24px; border:none; text-align:right; font-size:0.88rem; color:#374151;">
                                        ₱{{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td style="padding:8px 24px; border:none; text-align:right; font-weight:600; color:#007A5E; font-size:0.88rem;">
                                        ₱{{ number_format($item->total_price, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <div style="height:6px; background:#f0f2f5;"></div>
                    </div>

                @empty
                    <div class="text-center text-muted py-4">
                        No sales recorded yet.
                        <a href="{{ route('consignment.sale.create', $receivable) }}">
                            Record first sale
                        </a>
                    </div>
                @endforelse

                {{-- Sales Totals --}}
                @if($receivable->sales->count())
                    <div class="px-3 py-2 d-flex justify-content-end gap-4"
                         style="background:#f0fdf4; border-top:2px solid #007A5E;">
                        <span class="small text-muted">
                            Total Sales:
                            <strong class="text-success ms-1">
                                ₱{{ number_format($receivable->sales->sum('total_amount'), 2) }}
                            </strong>
                        </span>
                        <span class="small text-muted">
                            Total Cost:
                            <strong class="ms-1">
                                ₱{{ number_format($receivable->sales->sum('total_cost'), 2) }}
                            </strong>
                        </span>
                        <span class="small text-muted">
                            Gross Profit:
                            <strong class="text-primary ms-1">
                                ₱{{ number_format($receivable->sales->sum('total_amount') - $receivable->sales->sum('total_cost'), 2) }}
                            </strong>
                        </span>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- RIGHT COLUMN: Payments --}}
    <div class="col-md-4">

        {{-- Info Banner --}}
        <div class="alert mb-3 py-2"
             style="background:#ecfdf5; border:1px solid #007A5E; border-radius:10px;">
            <div class="fw-semibold small" style="color:#065f46;">
                <i class="bi bi-info-circle-fill me-1"></i> Sales &amp; remittance
            </div>
            <div class="small mt-1" style="color:#064e3b; line-height:1.5;">
                Cash remittance is recorded when you use <strong>Record Sales</strong> — enter the amount remitted on
                the same review screen as the products sold. Each entry is tied to that sale and updates this DR’s
                balance.
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold">
                <i class="bi bi-cash-coin"></i> Cash remitted
                <span class="text-muted fw-normal small ms-1">(from sales)</span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                @forelse($receivable->payments as $pay)
                    <li class="list-group-item py-2 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                @if($pay->consignment_sale_id)
                                    <span class="badge bg-success mb-1" style="font-size:0.68rem;">
                                        Sale {{ $pay->sale?->periodLabel() ?? '—' }}
                                    </span>
                                @else
                                    <span class="text-muted small d-block mb-1">Other record</span>
                                @endif
                                <div class="fw-bold text-success">
                                    ₱{{ number_format($pay->amount, 2) }}
                                </div>
                                <div class="text-muted small">
                                    Remitted {{ $pay->payment_date->format('M d, Y') }}
                                </div>
                                @if($pay->notes)
                                    <div class="text-muted small">{{ $pay->notes }}</div>
                                @endif
                            </div>
                            <div class="text-end">
                                @if($pay->reference)
                                    <span class="badge bg-secondary">
                                        {{ $pay->reference }}
                                    </span>
                                @endif
                                <div class="text-muted small mt-1">
                                    {{ $pay->creator->name ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-center text-muted py-3">
                        No cash remitted yet — use <strong>Record Sales</strong> and enter remittance on the review step.
                    </li>
                @endforelse
                </ul>
            </div>

            @if($receivable->status === 'paid')
            <div class="card-footer text-center text-success fw-semibold">
                <i class="bi bi-check-circle-fill"></i> Fully Paid — No balance remaining
            </div>
            @elseif($receivable->payments->isEmpty())
            <div class="card-footer small text-muted" style="background:#f8fafc;">
                <i class="bi bi-arrow-up-circle me-1"></i>
                Remittance is captured together with each <strong>Record Sales</strong> entry.
            </div>
            @else
            <div class="card-footer small text-muted" style="background:#f8fafc;">
                <i class="bi bi-info-circle me-1"></i>
                Further remittance is added the same way: <strong>Record Sales</strong> with cash on the review step.
            </div>
            @endif
        </div>

    </div>
</div>

@endsection
@push('scripts')
<script>
function toggleSale(id) {
    const items   = document.getElementById('sale-items-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const hint    = document.getElementById('toggle-hint-' + id);
    const isOpen  = items.style.display !== 'none';

    items.style.display     = isOpen ? 'none' : 'block';
    chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
    hint.textContent        = isOpen ? 'Click to view ▾' : 'Click to hide ▴';
}
</script>
@endpush