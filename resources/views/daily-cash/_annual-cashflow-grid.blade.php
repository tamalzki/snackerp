@php
    use App\Support\DailyCashMetroLedger;
    $grid = $annualCashflowGrid ?? [];
    $reportYear = (int) ($grid['year'] ?? now()->year);
    $monthLabels = $grid['month_labels'] ?? [];
    $amountCols = $grid['amount_columns'] ?? [];
    $sections = $grid['sections'] ?? [];
    $totalsRow = $grid['totals_row'] ?? ['label' => 'Totals', 'months' => [], 'row_total' => 0];
    $acn = count($amountCols);
    $colSpanSpacer = 3 + 11 + 12 * $acn;
@endphp

<style>
.annual-cashflow-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 0 0 10px 10px;
}
.annual-cashflow-table {
    font-size: 0.78rem;
    margin-bottom: 0;
    white-space: nowrap;
}
.annual-cashflow-table thead th {
    background: #004d3b;
    color: #fff;
    font-weight: 600;
    vertical-align: middle;
    text-align: center;
}
.annual-cashflow-table thead tr.subhead th {
    background: #006b52;
    font-size: 0.72rem;
    font-weight: 600;
}
.annual-cashflow-table .sticky-grp {
    position: sticky;
    left: 0;
    z-index: 4;
    background: #f8fafc;
    min-width: 8rem;
    max-width: 10rem;
    white-space: normal;
    text-align: left;
    box-shadow: 4px 0 8px rgba(0,0,0,0.06);
}
.annual-cashflow-table .sticky-cat {
    position: sticky;
    left: 8rem;
    z-index: 4;
    background: #f8fafc;
    min-width: 8rem;
    max-width: 12rem;
    white-space: normal;
    text-align: left;
    box-shadow: 4px 0 8px rgba(0,0,0,0.06);
}
.annual-cashflow-table thead th.sticky-grp,
.annual-cashflow-table thead th.sticky-cat {
    background: #004d3b;
    color: #fff;
    z-index: 5;
}
.annual-cashflow-table tbody td.sticky-grp,
.annual-cashflow-table tbody th.sticky-grp,
.annual-cashflow-table tbody td.sticky-cat,
.annual-cashflow-table tbody th.sticky-cat {
    background: #fff;
}
.annual-cashflow-table tfoot th.sticky-grp,
.annual-cashflow-table tfoot th.sticky-cat {
    background: #f0f9f6;
}
.annual-cashflow-table .amt {
    min-width: 4.5rem;
    text-align: right;
}
.annual-cashflow-table .month-gap {
    width: 0.4rem;
    min-width: 0.4rem;
    padding: 0 !important;
    border-left: none !important;
    border-right: none !important;
    background: transparent !important;
}
.annual-cashflow-table .row-total-col {
    min-width: 5.5rem;
    text-align: right;
    font-weight: 600;
    background: #f8fdf9;
}
.annual-cashflow-table tbody.annual-sheet-spacer td {
    border: none !important;
    height: 0.45rem;
    padding: 0 !important;
    background: transparent !important;
}
.annual-cashflow-header-bar {
    background: linear-gradient(180deg, #c8f5a0 0%, #9fe870 100%);
    border-radius: 10px 10px 0 0;
    padding: 0.85rem 1.25rem;
    border-bottom: 2px solid #86c93a;
}
.annual-cashflow-header-bar h2 {
    color: #0d47a1;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    margin: 0;
}
</style>

<div class="annual-cashflow-header-bar d-flex flex-wrap align-items-center justify-content-between gap-2">
    <h2 class="text-uppercase mb-0">Annual cashflow — {{ $reportYear }}</h2>
    <span class="small text-muted">Annual from January to December</span>
</div>

<div class="annual-cashflow-wrap">
    <table class="table table-bordered table-sm annual-cashflow-table align-middle">
        <thead>
            <tr>
                <th class="sticky-grp ps-3" rowspan="2">Group</th>
                <th class="sticky-cat" rowspan="2">Category</th>
                @for($m = 1; $m <= 12; $m++)
                    @if($m > 1)
                        <th class="month-gap"></th>
                    @endif
                    <th colspan="{{ count($amountCols) }}" class="text-center">{{ strtoupper($monthLabels[$m] ?? '') }}</th>
                @endfor
                <th rowspan="2" class="row-total-col text-center align-middle">Total</th>
            </tr>
            <tr class="subhead">
                @for($m = 1; $m <= 12; $m++)
                    @if($m > 1)
                        <th class="month-gap"></th>
                    @endif
                    @foreach($amountCols as $ac)
                        <th class="amt">{{ $ac['label'] }}</th>
                    @endforeach
                @endfor
            </tr>
        </thead>
        @foreach($sections as $secIdx => $section)
            @php
                $lines = array_values(array_filter($section['lines'] ?? [], fn ($ln) => is_array($ln)));
                $typeRowspan = count($lines);
                $parentLabel = DailyCashMetroLedger::parentColumnLabelFromSectionHeading($section['heading'] ?? '');
            @endphp
            @if($typeRowspan > 0)
                <tbody class="annual-sheet-group">
                    @foreach($lines as $lineIdx => $line)
                        <tr>
                            @if($lineIdx === 0)
                                <td rowspan="{{ $typeRowspan }}" class="sticky-grp ps-3 py-2 align-top text-body">{{ $parentLabel }}</td>
                            @endif
                            <td class="sticky-cat py-2 text-body">{{ $line['category_display'] ?? '' }}</td>
                            @for($m = 1; $m <= 12; $m++)
                                @if($m > 1)
                                    <td class="month-gap"></td>
                                @endif
                                @php $mo = $line['months'][$m] ?? []; @endphp
                                @foreach($amountCols as $ac)
                                    @php $k = $ac['key']; $v = (float) ($mo[$k] ?? 0); @endphp
                                    <td class="amt py-2 @if($v > 0) text-success @elseif($v < 0) text-danger @endif">
                                        @if(abs($v) >= 0.005)
                                            ₱{{ number_format($v, 2) }}
                                        @endif
                                    </td>
                                @endforeach
                            @endfor
                            @php
                                $rowTot = (float) ($line['row_total'] ?? 0);
                            @endphp
                            <td class="row-total-col py-2">{{ abs($rowTot) >= 0.005 ? '₱'.number_format($rowTot, 2) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                @if(!$loop->last)
                    <tbody class="annual-sheet-spacer"><tr><td colspan="{{ $colSpanSpacer }}"></td></tr></tbody>
                @endif
            @endif
        @endforeach
        <tfoot>
            <tr class="fw-bold">
                <th class="sticky-grp ps-3" colspan="2">{{ $totalsRow['label'] ?? 'Totals' }}</th>
                @for($m = 1; $m <= 12; $m++)
                    @if($m > 1)
                        <th class="month-gap"></th>
                    @endif
                    @php $tm = $totalsRow['months'][$m] ?? []; @endphp
                    @foreach($amountCols as $ac)
                        @php $k = $ac['key']; $v = (float) ($tm[$k] ?? 0); @endphp
                        <td class="amt">{{ abs($v) >= 0.005 ? '₱'.number_format($v, 2) : '' }}</td>
                    @endforeach
                @endfor
                @php
                    $grandTot = (float) ($totalsRow['row_total'] ?? 0);
                @endphp
                <td class="row-total-col">{{ abs($grandTot) >= 0.005 ? '₱'.number_format($grandTot, 2) : '' }}</td>
            </tr>
        </tfoot>
    </table>
</div>
