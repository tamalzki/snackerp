@extends('layouts.app')
@section('title', 'Daily Cash Flow')

@section('content')

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

{{-- ===== DAILY LOG ===== --}}
@if($tab === 'daily')
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-journal-text text-success"></i> Daily Cash Journal
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

<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-calendar-month text-success"></i>
        Monthly Summary — {{ $filterYear }}
    </div>
    <div class="card-body p-0">
        @include('daily-cash._summary-table', ['rows' => $monthly, 'emptyMsg' => 'No data for '.$filterYear.'.'])
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
        <form method="POST" action="{{ route('daily-cash.store') }}">
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
@endsection
