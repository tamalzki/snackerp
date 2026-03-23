@extends('layouts.app')
@section('title', 'Consignment')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Consignment Ledger</h5>
</div>

<div class="row g-3">
@forelse($branches as $branch)
    @php
        $s = $branch->summary;
        $totalReceivable = $s->total_receivable ?? 0;
        $totalPaid       = $s->total_paid       ?? 0;
        $totalReturned   = $s->total_returned   ?? 0;
        $totalBalance    = $s->total_balance    ?? 0;
        $totalDrs        = $s->total_drs        ?? 0;
    @endphp
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold mb-1">
                            <i class="bi bi-shop"></i> {{ $branch->name }}
                        </h6>
                        <small class="text-muted">{{ $totalDrs }} DR(s)</small>
                    </div>
                    @if($totalBalance > 0)
                        <span class="badge bg-danger">Outstanding</span>
                    @else
                        <span class="badge bg-success">All Settled</span>
                    @endif
                </div>

                <div class="row g-2 mb-3 text-center">
                    <div class="col-3">
                        <div class="text-muted" style="font-size:0.7rem;">DR Value</div>
                        <div class="fw-bold text-primary" style="font-size:0.82rem;">
                            ₱{{ number_format($totalReceivable, 2) }}
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted" style="font-size:0.7rem;">Paid</div>
                        <div class="fw-bold text-success" style="font-size:0.82rem;">
                            ₱{{ number_format($totalPaid, 2) }}
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted" style="font-size:0.7rem;">Returned</div>
                        <div class="fw-bold text-warning" style="font-size:0.82rem;">
                            ₱{{ number_format($totalReturned, 2) }}
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-muted" style="font-size:0.7rem;">Balance</div>
                        <div class="fw-bold {{ $totalBalance > 0 ? 'text-danger' : 'text-success' }}"
                             style="font-size:0.82rem;">
                            ₱{{ number_format($totalBalance, 2) }}
                        </div>
                    </div>
                </div>

                <a href="{{ route('consignment.branch', $branch) }}"
                   class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-eye"></i> View Ledger
                </a>
            </div>
        </div>
    </div>
@empty
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                No active branches found.
            </div>
        </div>
    </div>
@endforelse
</div>

@endsection