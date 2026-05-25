<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-bold small text-uppercase">
            <i class="bi bi-bank2 text-primary"></i>
            Cash in bank
        </span>
    </div>
    <div class="card-body py-2 px-2 px-sm-3">

        @if($dailyCashBankNames->isEmpty())

        @else
            <form method="POST" action="{{ route('daily-cash.bank-grid.sync', $dailyCash) }}" class="mb-0" id="dailyCashBankGridCardForm">
                @csrf
                <div class="table-responsive mb-2">
                    <table class="table table-sm align-middle mb-0 daily-cash-bank-grid-table" style="font-size:0.76rem;width:100%;min-width:400px;">
                        <thead>
                            <tr>
                                <th class="text-muted ps-1" style="width:7rem;"></th>
                                @foreach($dailyCashBankNames as $bn)
                                    <th class="text-center text-nowrap px-1">{{ $bn->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $gridMovements = $dailyCashBankGridState['movements'] ?? [];
                                $gridCells = $dailyCashBankGridState['cells'] ?? [];
                                $movLabels = [
                                    'deposit' => 'Deposits',
                                    'withdrawal' => 'Withdrawals',
                                    'other' => 'Others',
                                ];
                            @endphp
                            @foreach($gridMovements as $mov)
                                <tr>
                                    <th class="small text-muted text-nowrap ps-1">{{ $movLabels[$mov] ?? $mov }}</th>
                                    @foreach($dailyCashBankNames as $bn)
                                        @php
                                            $c = $gridCells[(int) $bn->id][$mov] ?? ['amount' => '', 'detail' => ''];
                                        @endphp
                                        <td class="align-top py-1 px-1">
                                            <input type="number"
                                                   name="grid[{{ $bn->id }}][{{ $mov }}][amount]"
                                                   value="{{ $c['amount'] }}"
                                                   step="0.01"
                                                   min="0"
                                                   class="form-control form-control-sm py-1"
                                                   placeholder="0.00">
                                            @if($mov === 'other')
                                                <label class="visually-hidden">Description</label>
                                                <input type="text"
                                                       name="grid[{{ $bn->id }}][{{ $mov }}][detail]"
                                                       value="{{ $c['detail'] }}"
                                                       class="form-control form-control-sm mt-1 py-1"
                                                       maxlength="255"
                                                       placeholder="Description">
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-check2"></i> Save
                </button>
            </form>
        @endif

    </div>
</div>
