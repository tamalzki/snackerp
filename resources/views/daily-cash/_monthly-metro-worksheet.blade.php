@php
    use App\Support\DailyCashMetroLedger;
    $sections = $matrix['sections'] ?? [];
    $periodTitle = $matrix['period_title'] ?? '';
    $monthNet = (float) ($matrix['month_net'] ?? 0);
    $ft = $matrix['footer_totals'] ?? [];
@endphp

<style>
.monthly-metro-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 0 0 10px 10px;
}
.monthly-metro-table {
    font-size: 0.82rem;
    margin-bottom: 0;
}
.monthly-metro-table thead th {
    background: #004d3b;
    color: #fff;
    font-weight: 600;
    vertical-align: middle;
}
.monthly-metro-table .sticky-grp {
    position: sticky;
    left: 0;
    z-index: 3;
    background: #f8fafc;
    min-width: 9rem;
    max-width: 11rem;
    white-space: normal;
    box-shadow: 4px 0 8px rgba(0,0,0,0.06);
}
.monthly-metro-table .sticky-cat {
    position: sticky;
    left: 9rem;
    z-index: 3;
    background: #f8fafc;
    min-width: 10rem;
    max-width: 14rem;
    white-space: normal;
    box-shadow: 4px 0 8px rgba(0,0,0,0.06);
}
.monthly-metro-table thead th.sticky-grp,
.monthly-metro-table thead th.sticky-cat {
    background: #004d3b;
    color: #fff;
}
.monthly-metro-table tbody td.sticky-grp,
.monthly-metro-table tbody td.sticky-cat {
    background: #fff;
}
.monthly-metro-table tfoot td.sticky-grp,
.monthly-metro-table tfoot td.sticky-cat {
    background: #f0f9f6;
}
.monthly-metro-table .amt-col {
    width: 100px;
    text-align: right;
}
.monthly-metro-table tbody.daily-cash-sheet-spacer td {
    border: none !important;
    height: 0.45rem;
    padding: 0 !important;
    background: transparent !important;
}
.monthly-metro-header-bar {
    background: linear-gradient(180deg, #c8f5a0 0%, #9fe870 100%);
    border-radius: 10px 10px 0 0;
    padding: 0.85rem 1.25rem;
    border-bottom: 2px solid #86c93a;
}
.monthly-metro-header-bar h2 {
    color: #0d47a1;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    margin: 0;
}
</style>

<div class="monthly-metro-header-bar d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
        <h2 class="text-uppercase">Financial Statement - {{ $periodTitle }}</h2>
    </div>
    <div class="small text-primary fw-semibold">
        Net for month: <strong class="{{ $monthNet >= 0 ? 'text-success' : 'text-danger' }}">&#8369;{{ number_format($monthNet, 2) }}</strong>
    </div>
</div>

<div class="monthly-metro-wrap">
    <table class="table table-bordered table-sm monthly-metro-table align-middle mb-0">
        <thead>
            <tr>
                <th class="sticky-grp ps-3">Group</th>
                <th class="sticky-cat">Category</th>
                <th class="text-end amt-col">Capital</th>
                <th class="text-end amt-col">Income</th>
                <th class="text-end amt-col">Expenses</th>
                <th class="text-end amt-col">Discret.</th>
                <th class="text-end amt-col">Savings</th>
                <th class="text-end amt-col">Other</th>
            </tr>
        </thead>
        @foreach($sections as $secIdx => $section)
            @php
                $lines = array_values(array_filter($section['lines'] ?? [], fn ($ln) => is_array($ln)));
                $typeRowspan = count($lines);
                $parentLabel = DailyCashMetroLedger::parentColumnLabelFromSectionHeading($section['heading'] ?? '');
                $colSpanSpacer = 8;
            @endphp
            @if($typeRowspan > 0)
                <tbody class="daily-cash-sheet-group">
                    @foreach($lines as $lineIdx => $sheetRow)
                        @php
                            $lineAmt = (float) ($sheetRow['amount'] ?? 0);
                            $hasAmt = abs($lineAmt) > 0.005;
                            $pc = DailyCashMetroLedger::primaryAmountColumn($sheetRow['type']);
                            $lineType = $sheetRow['type'];
                            $categoryDisplay = $sheetRow['category_display'] ?? '';
                        @endphp
                        <tr>
                            @if($lineIdx === 0)
                                <td rowspan="{{ $typeRowspan }}" class="sticky-grp ps-3 py-2 align-top text-body">{{ $parentLabel }}</td>
                            @endif
                            <td class="sticky-cat py-2 text-body">
                                {{ $categoryDisplay }}
                                @if($lineType === 'INCOME' && ($sheetRow['category_key'] ?? '') === 'cash_from_bank')
                                    <span class="d-block text-muted mt-1" style="font-size:0.62rem;">Withdrawal</span>
                                @endif
                            </td>
                            <td class="text-end align-top py-2 amt-col">
                                @if($pc === 'capital' && $hasAmt)
                                    <span>&#8369;{{ number_format($lineAmt, 2) }}</span>
                                @endif
                            </td>
                            <td class="text-end align-top py-2 amt-col">
                                @if($pc === 'income' && $hasAmt)
                                    &#8369;{{ number_format($lineAmt, 2) }}
                                @endif
                            </td>
                            <td class="text-end align-top py-2 amt-col">
                                @if($pc === 'expenses' && $hasAmt)
                                    &#8369;{{ number_format($lineAmt, 2) }}
                                @endif
                            </td>
                            <td class="text-end align-top py-2 amt-col">
                                @if($pc === 'discretionary' && $hasAmt)
                                    <span>&#8369;{{ number_format($lineAmt, 2) }}</span>
                                @endif
                            </td>
                            <td class="text-end align-top py-2 amt-col">
                                @if($pc === 'savings' && $hasAmt)
                                    &#8369;{{ number_format($lineAmt, 2) }}
                                @endif
                            </td>
                            <td class="text-end align-top py-2 amt-col">
                                @if($pc === 'other' && $hasAmt)
                                    &#8369;{{ number_format($lineAmt, 2) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                @if(!$loop->last)
                    <tbody class="daily-cash-sheet-spacer"><tr><td colspan="{{ $colSpanSpacer }}"></td></tr></tbody>
                @endif
            @endif
        @endforeach
        @if(($matrix['has_entries'] ?? false))
        <tfoot class="table-light">
            <tr class="fw-semibold">
                <td colspan="2" class="ps-3 small text-body-secondary">Totals</td>
                <td class="text-end amt-col">&#8369;{{ number_format((float) ($ft['capital'] ?? 0), 2) }}</td>
                <td class="text-end amt-col">&#8369;{{ number_format((float) ($ft['income'] ?? 0), 2) }}</td>
                <td class="text-end amt-col">&#8369;{{ number_format((float) ($ft['expenses'] ?? 0), 2) }}</td>
                <td class="text-end amt-col">&#8369;{{ number_format((float) ($ft['discretionary'] ?? 0), 2) }}</td>
                <td class="text-end amt-col">&#8369;{{ number_format((float) ($ft['savings'] ?? 0), 2) }}</td>
                <td class="text-end amt-col">&#8369;{{ number_format((float) ($ft['other'] ?? 0), 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
