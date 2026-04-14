@php
    $typeColors = \App\Models\DailyCashEntry::$typeColors;
    $columns = $matrix['columns'] ?? [];
    $netFooter = $matrix['net_footer'] ?? [];
    $cornerNet = $matrix['corner_net'] ?? ($matrix['year_total_net'] ?? 0);
    $pencilYear = $matrix['pencil_year'] ?? ($matrix['year'] ?? now()->year);
    $dataTab = $matrix['data_tab'] ?? 'monthly';
    $headerTitle = $matrix['header_title'] ?? 'Monthly cash flow';
    $headerMetricLabel = $matrix['header_metric_label'] ?? 'Year net:';
    $infoText = $matrix['info_text'] ?? '';
    $footerRowLabel = $matrix['footer_row_label'] ?? 'Net (month)';
    $scrollHint = $matrix['scroll_hint'] ?? '';
    $emptyContext = $matrix['empty_context'] ?? '';
    $colCount = count($columns);
    $tableColspan = 4 + $colCount + 1;
@endphp

<style>
.monthly-matrix-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 0 0 10px 10px;
}
.monthly-matrix-table {
    font-size: 0.78rem;
    margin-bottom: 0;
    white-space: nowrap;
}
.monthly-matrix-table thead th {
    background: #004d3b;
    color: #fff;
    font-weight: 600;
    vertical-align: middle;
}
/* Sticky header cells were using light backgrounds from column classes → white text disappeared. Keep bar styling. */
.monthly-matrix-table thead th.sticky-type,
.monthly-matrix-table thead th.sticky-sub,
.monthly-matrix-table thead th.sticky-edit,
.monthly-matrix-table thead th.sticky-desc {
    background: #004d3b;
    color: #fff;
}
.monthly-matrix-table .month-col {
    min-width: 5.25rem;
    text-align: right;
}
.monthly-matrix-table .sticky-type {
    position: sticky;
    left: 0;
    z-index: 3;
    background: #f8fafc;
    min-width: 6.5rem;
    box-shadow: 4px 0 8px rgba(0,0,0,0.06);
}
.monthly-matrix-table .sticky-sub {
    position: sticky;
    left: 6.5rem;
    z-index: 3;
    background: #f8fafc;
    min-width: 7.25rem;
    max-width: 11rem;
    white-space: normal;
    box-shadow: 4px 0 8px rgba(0,0,0,0.06);
}
/* Narrow column: pencil sits between subcategory label and description (matches workbook layout). */
.monthly-matrix-table .sticky-edit {
    position: sticky;
    left: 13.75rem;
    z-index: 3;
    background: #f8fafc;
    width: 2rem;
    min-width: 2rem;
    max-width: 2rem;
    padding-left: 0.15rem;
    padding-right: 0.15rem;
    text-align: center;
    vertical-align: middle;
    box-shadow: 4px 0 8px rgba(0,0,0,0.06);
}
.monthly-matrix-table .sticky-desc {
    position: sticky;
    left: 15.75rem;
    z-index: 3;
    background: #f8fafc;
    min-width: 9rem;
    max-width: 14rem;
    white-space: normal;
    box-shadow: 4px 0 8px rgba(0,0,0,0.06);
}
.monthly-matrix-table tbody td.sticky-type,
.monthly-matrix-table tbody td.sticky-sub,
.monthly-matrix-table tbody td.sticky-edit,
.monthly-matrix-table tbody td.sticky-desc {
    background: #fff;
}
.monthly-matrix-table tfoot th.sticky-type,
.monthly-matrix-table tfoot th.sticky-sub,
.monthly-matrix-table tfoot th.sticky-edit,
.monthly-matrix-table tfoot th.sticky-desc {
    background: #f0f9f6;
}
.monthly-matrix-header-bar {
    background: linear-gradient(180deg, #c8f5a0 0%, #9fe870 100%);
    border-radius: 10px 10px 0 0;
    padding: 0.85rem 1.25rem;
    border-bottom: 2px solid #86c93a;
}
.monthly-matrix-header-bar h2 {
    color: #0d47a1;
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    margin: 0;
}
</style>

<div class="monthly-matrix-header-bar d-flex flex-wrap align-items-center justify-content-between gap-2">
    <h2 class="text-uppercase mb-0">{{ $headerTitle }}</h2>
    <div class="d-flex flex-wrap align-items-center gap-3 small">
        <span class="text-primary fw-semibold">{{ $headerMetricLabel }} <strong>&#8369;{{ number_format($cornerNet, 2) }}</strong></span>
        @if($scrollHint !== '')
            <span class="text-muted d-none d-md-inline">{{ $scrollHint }}</span>
        @endif
    </div>
</div>
@if($infoText !== '')
<div class="px-3 py-2 border-bottom bg-white small text-muted">
    <i class="bi bi-info-circle"></i>
    {!! $infoText !!}
</div>
@endif

<div class="monthly-matrix-wrap">
    <table class="table table-bordered table-sm monthly-matrix-table align-middle">
        <thead>
            <tr>
                <th class="sticky-type ps-3">Type</th>
                <th class="sticky-sub">Subcategory</th>
                <th class="sticky-edit px-1 text-center" style="font-size:0.7rem;" title="Recategorize subcategory">Edit</th>
                <th class="sticky-desc">Description</th>
                @foreach($columns as $col)
                    <th class="month-col text-end">{{ $col['label'] }}</th>
                @endforeach
                <th class="month-col text-end" style="background:#0d4d3c;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($matrix['lines'] as $line)
                @php $tc = $typeColors[$line['type']] ?? 'secondary'; @endphp
                <tr>
                    <td class="sticky-type ps-2">
                        <span class="badge bg-{{ $tc }}" style="font-size:0.65rem;">{{ $line['type'] }}</span>
                    </td>
                    <td class="sticky-sub small text-muted">{{ $line['subcategory_label'] ?? '—' }}</td>
                    <td class="sticky-edit">
                        <button type="button"
                                class="btn btn-link btn-sm p-0 lh-1 text-secondary js-daily-cash-subcat-edit"
                                title="Recategorize"
                                data-bs-toggle="modal"
                                data-bs-target="#subcategoryOverrideModal"
                                data-year="{{ $pencilYear }}"
                                data-type="{{ $line['type'] }}"
                                data-description-norm="{{ $line['description_norm'] }}"
                                data-line-subcategory-key="{{ $line['subcategory_key'] }}"
                                data-tab="{{ $dataTab }}">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    </td>
                    <td class="sticky-desc small">{{ $line['description_display'] }}</td>
                    @foreach($columns as $col)
                        @php $ck = $col['key']; $amt = $line['amounts'][$ck] ?? 0; @endphp
                        <td class="month-col text-end @if($amt > 0) text-success @elseif($amt < 0) text-danger @endif">
                            @if(abs($amt) >= 0.005)
                                &#8369;{{ number_format($amt, 2) }}
                            @endif
                        </td>
                    @endforeach
                    <td class="month-col text-end fw-semibold" style="background:#f0fdf4;">
                        @if(abs($line['row_total']) >= 0.005)
                            &#8369;{{ number_format($line['row_total'], 2) }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $tableColspan }}" class="text-center text-muted py-5">
                        @if(($matrix['mode'] ?? '') === 'annual')
                            No ledger entries yet.
                        @else
                            No ledger entries for {{ $emptyContext }}.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($matrix['lines']) > 0 && $colCount > 0)
        <tfoot>
            <tr class="fw-bold" style="background:#f0f9f6;">
                <th class="sticky-type ps-3 text-uppercase small" colspan="4">{{ $footerRowLabel }}</th>
                @foreach($columns as $col)
                    @php $ck = $col['key']; $nm = $netFooter[$ck] ?? 0; @endphp
                    <th class="month-col text-end {{ $nm >= 0 ? 'text-success' : 'text-danger' }}">
                        &#8369;{{ number_format($nm, 2) }}
                    </th>
                @endforeach
                <th class="month-col text-end {{ $cornerNet >= 0 ? 'text-success' : 'text-danger' }}" style="background:#e8f5f0;">
                    &#8369;{{ number_format($cornerNet, 2) }}
                </th>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
