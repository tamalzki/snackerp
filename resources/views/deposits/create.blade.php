@extends('layouts.app')
@section('title', 'New Deposit')
@section('content')

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-safe"></i> Record Deposit
            </div>
            <div class="card-body">
                <form action="{{ route('deposits.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deposit To</label>
                        <div class="d-flex gap-3 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                       name="source_type" id="typeCash"
                                       value="cash" checked
                                       onchange="toggleAccounts('cash')">
                                <label class="form-check-label" for="typeCash">
                                    <i class="bi bi-wallet2"></i> Cash Account
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                       name="source_type" id="typeBank"
                                       value="bank"
                                       onchange="toggleAccounts('bank')">
                                <label class="form-check-label" for="typeBank">
                                    <i class="bi bi-bank"></i> Bank Account
                                </label>
                            </div>
                        </div>

                        <div id="cashAccounts">
                            <select name="source_id" class="form-select @error('source_id') is-invalid @enderror">
                                <option value="">-- Select Cash Account --</option>
                                @foreach($cashAccounts as $a)
                                    <option value="{{ $a->id }}" {{ old('source_id') == $a->id ? 'selected' : '' }}>
                                        {{ $a->name }} — ₱{{ number_format($a->balance, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="bankAccountsDiv" class="d-none">
                            <select name="source_id" class="form-select" disabled>
                                <option value="">-- Select Bank Account --</option>
                                @foreach($bankAccounts as $a)
                                    <option value="{{ $a->id }}">
                                        {{ $a->bank_name }}@if($a->account_name) — {{ $a->account_name }}@endif
                                        (₱{{ number_format($a->balance, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @error('source_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount (₱)</label>
                        <input type="number" name="amount"
                               class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount') }}"
                               step="0.01" min="0.01" placeholder="0.00" required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deposit Date</label>
                        <input type="date" name="deposit_date"
                               class="form-control @error('deposit_date') is-invalid @enderror"
                               value="{{ old('deposit_date', date('Y-m-d')) }}" required>
                        @error('deposit_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reference # (optional)</label>
                        <input type="text" name="reference"
                               class="form-control"
                               value="{{ old('reference') }}"
                               placeholder="e.g. OR-001, Slip #123">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notes (optional)</label>
                        <textarea name="notes" class="form-control"
                                  rows="2">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Save Deposit
                        </button>
                        <a href="{{ route('deposits.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
function toggleAccounts(type) {
    const cashDiv  = document.getElementById('cashAccounts');
    const bankDiv  = document.getElementById('bankAccountsDiv');
    const cashSel  = cashDiv.querySelector('select');
    const bankSel  = bankDiv.querySelector('select');

    if (type === 'cash') {
        cashDiv.classList.remove('d-none');
        bankDiv.classList.add('d-none');
        cashSel.disabled = false;
        bankSel.disabled = true;
    } else {
        cashDiv.classList.add('d-none');
        bankDiv.classList.remove('d-none');
        cashSel.disabled = true;
        bankSel.disabled = false;
    }
}
</script>
@endpush