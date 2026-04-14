@php
    $cols = ['capital' => 'Capital', 'income' => 'Income', 'expenses' => 'Expenses',
             'discretionary' => 'Discret.', 'savings' => 'Savings', 'other' => 'Other'];
@endphp
<style>
.annual-cashflow-detail-table .annual-cashflow-edit-col {
    width: 2rem;
    min-width: 2rem;
    max-width: 2rem;
    vertical-align: middle;
}
</style>

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
            <th class="text-end">Other</th>
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
            <td class="text-end text-secondary">₱{{ number_format($row['other'] ?? 0, 2) }}</td>
            <td class="text-end fw-bold {{ $row['net'] >= 0 ? 'text-success' : 'text-danger' }}"
                style="border-left:2px solid #007A5E;">
                ₱{{ number_format($row['net'], 2) }}
            </td>
        </tr>

        {{-- Group totals by type + normalized description (monthly/yearly) --}}
        <tr class="p-0 border-0">
            <td colspan="9" class="p-0 border-0">
                <div class="collapse show" id="detail-{{ $key }}">
                    <div class="px-3 py-2 small text-muted border-bottom" style="background:#fafdfb;">
                        <i class="bi bi-tags"></i> Totals by <strong>type + description</strong> (same wording, any caps/spacing). <strong>Subcategory</strong> uses your manual choice when set, otherwise keywords in the description — {{ $row['label'] }}.
                    </div>
                    <table class="table table-sm mb-0 annual-cashflow-detail-table" style="font-size:0.78rem;background:#fff;">
                        <thead>
                            <tr style="background:#e8f5f1;">
                                <th class="ps-5">Type</th>
                                <th>Subcategory</th>
                                <th class="annual-cashflow-edit-col p-0" aria-label="Recategorize"><span class="visually-hidden">Edit</span></th>
                                <th>Group</th>
                                <th class="text-end pe-5">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($row['report_rows'] ?? [] as $cr)
                            <tr>
                                <td class="ps-5">
                                    <span class="badge bg-secondary" style="font-size:0.65rem;">{{ $cr['type'] }}</span>
                                </td>
                                <td class="small text-muted">{{ $cr['subcategory_label'] ?? '—' }}</td>
                                <td class="annual-cashflow-edit-col text-center align-middle p-0">
                                    <button type="button"
                                            class="btn btn-link btn-sm p-0 lh-1 text-secondary js-daily-cash-subcat-edit"
                                            title="Recategorize"
                                            data-bs-toggle="modal"
                                            data-bs-target="#subcategoryOverrideModal"
                                            data-year="{{ $row['year'] ?? (int) $row['label'] }}"
                                            data-type="{{ $cr['type'] }}"
                                            data-description-norm="{{ $cr['description_norm'] }}"
                                            data-line-subcategory-key="{{ $cr['subcategory_key'] }}"
                                            data-tab="annual">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </td>
                                <td>{{ $cr['label'] }}</td>
                                <td class="text-end pe-5 fw-semibold">₱{{ number_format($cr['total'], 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="ps-5 text-muted py-2">No entries in this period.</td>
                            </tr>
                            @endforelse
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
            <td class="text-end text-secondary">₱{{ number_format(array_sum(array_column($rows,'other')), 2) }}</td>
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
