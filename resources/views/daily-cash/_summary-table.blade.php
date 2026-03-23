@php
    $cols = ['capital' => 'Capital', 'income' => 'Income', 'expenses' => 'Expenses',
             'discretionary' => 'Discret.', 'savings' => 'Savings'];
@endphp

@if(empty($rows))
<div class="text-center text-muted py-4">{{ $emptyMsg ?? 'No data.' }}</div>
@else
<div class="table-responsive">
<table class="table table-sm mb-0 align-middle" style="font-size:0.84rem;">
    <thead>
        <tr>
            <th class="ps-3" style="width:32px;"></th>
            <th>Period</th>
            <th class="text-end">Capital</th>
            <th class="text-end text-success">Income</th>
            <th class="text-end text-danger">Expenses</th>
            <th class="text-end">Discret.</th>
            <th class="text-end">Savings</th>
            <th class="text-end" style="border-left:2px solid #007A5E;">Net</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)

        {{-- Parent row (month or year) --}}
        @php $key = $row['month_key'] ?? $row['year_key'] ?? 'row'.$loop->index; @endphp
        <tr class="fw-bold" style="background:#f8fafc;cursor:pointer;"
            data-bs-toggle="collapse" data-bs-target="#detail-{{ $key }}"
            aria-expanded="true">
            <td class="ps-3 text-muted" style="font-size:0.75rem;">
                <i class="bi bi-chevron-down toggle-icon-{{ $key }}"></i>
            </td>
            <td>{{ $row['label'] }}</td>
            <td class="text-end">₱{{ number_format($row['capital'], 2) }}</td>
            <td class="text-end text-success">₱{{ number_format($row['income'], 2) }}</td>
            <td class="text-end text-danger">₱{{ number_format($row['expenses'], 2) }}</td>
            <td class="text-end">₱{{ number_format($row['discretionary'], 2) }}</td>
            <td class="text-end">₱{{ number_format($row['savings'], 2) }}</td>
            <td class="text-end fw-bold {{ $row['net'] >= 0 ? 'text-success' : 'text-danger' }}"
                style="border-left:2px solid #007A5E;">
                ₱{{ number_format($row['net'], 2) }}
            </td>
        </tr>

        {{-- Detail rows (days inside month, or months inside year) --}}
        <tr class="p-0 border-0">
            <td colspan="8" class="p-0 border-0">
                <div class="collapse show" id="detail-{{ $key }}">
                    <table class="table table-sm mb-0" style="font-size:0.78rem;background:#fff;">
                        <thead>
                            <tr style="background:#e8f5f1;">
                                <th class="ps-5">
                                    @if(isset($row['days'])) Date @else Month @endif
                                </th>
                                <th class="text-end">Capital</th>
                                <th class="text-end text-success">Income</th>
                                <th class="text-end text-danger">Expenses</th>
                                <th class="text-end">Discret.</th>
                                <th class="text-end">Savings</th>
                                <th class="text-end" style="border-left:2px solid #007A5E;">Net</th>
                                <th style="width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($row['days'] ?? $row['months'] ?? [] as $sub)
                            @php
                                $isDay = isset($sub['day']);
                                $subNet = $sub['net'];
                            @endphp
                            <tr>
                                <td class="ps-5 text-muted">
                                    @if($isDay)
                                        {{ $sub['day']->date->format('D, M d') }}
                                    @else
                                        {{ $sub['label'] }}
                                    @endif
                                </td>
                                <td class="text-end">₱{{ number_format($sub['capital'], 2) }}</td>
                                <td class="text-end text-success">₱{{ number_format($sub['income'], 2) }}</td>
                                <td class="text-end text-danger">₱{{ number_format($sub['expenses'], 2) }}</td>
                                <td class="text-end">₱{{ number_format($sub['discretionary'], 2) }}</td>
                                <td class="text-end">₱{{ number_format($sub['savings'], 2) }}</td>
                                <td class="text-end fw-bold {{ $subNet >= 0 ? 'text-success' : 'text-danger' }}"
                                    style="border-left:2px solid #007A5E;">
                                    ₱{{ number_format($subNet, 2) }}
                                </td>
                                <td class="text-end pe-2">
                                    @if($isDay)
                                    <a href="{{ route('daily-cash.show', $sub['day']) }}"
                                       class="btn btn-xs btn-outline-primary p-0 px-1"
                                       style="font-size:0.7rem;">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @else
                                    <a href="{{ route('daily-cash.index', ['tab'=>'monthly','year'=>$sub['year']]) }}"
                                       class="btn btn-xs btn-outline-primary p-0 px-1"
                                       style="font-size:0.7rem;">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>

        @endforeach
    </tbody>

    {{-- Grand total footer --}}
    <tfoot style="background:#f0f9f6;border-top:2px solid #007A5E;">
        <tr class="fw-bold">
            <td></td>
            <td class="ps-3">TOTAL</td>
            <td class="text-end">₱{{ number_format(array_sum(array_column($rows,'capital')), 2) }}</td>
            <td class="text-end text-success">₱{{ number_format(array_sum(array_column($rows,'income')), 2) }}</td>
            <td class="text-end text-danger">₱{{ number_format(array_sum(array_column($rows,'expenses')), 2) }}</td>
            <td class="text-end">₱{{ number_format(array_sum(array_column($rows,'discretionary')), 2) }}</td>
            <td class="text-end">₱{{ number_format(array_sum(array_column($rows,'savings')), 2) }}</td>
            @php $totalNet = array_sum(array_column($rows,'net')); @endphp
            <td class="text-end {{ $totalNet >= 0 ? 'text-success' : 'text-danger' }}"
                style="border-left:2px solid #007A5E;">
                ₱{{ number_format($totalNet, 2) }}
            </td>
        </tr>
    </tfoot>
</table>
</div>

<script>
// Rotate chevron on expand/collapse
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(row) {
    const target = row.getAttribute('data-bs-target');
    const collapseEl = document.querySelector(target);
    if (!collapseEl) return;
    const iconKey = target.replace('#detail-', '');
    collapseEl.addEventListener('show.bs.collapse', function () {
        document.querySelector('.toggle-icon-' + iconKey)?.classList.replace('bi-chevron-right','bi-chevron-down');
    });
    collapseEl.addEventListener('hide.bs.collapse', function () {
        document.querySelector('.toggle-icon-' + iconKey)?.classList.replace('bi-chevron-down','bi-chevron-right');
    });
});
</script>
@endif
