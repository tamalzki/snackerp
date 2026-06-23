@foreach($sections as $section)
    @php
        $lines = $section['lines'] ?? [];
        $typeRowspan = count($lines);
        $parentLabel = \App\Support\DailyCashMetroLedger::parentColumnLabelFromSectionHeading($section['heading'] ?? '');
        $sectionSlugForGroup = \App\Support\DailyCashMetroLedger::sectionSlugFromHeading((string) ($section['heading'] ?? ''));
    @endphp
    @if($typeRowspan > 0)
    <tbody class="daily-cash-sheet-group">
    @foreach($lines as $lineIdx => $sheetRow)
        @php
            $lineType = $sheetRow['type'];
            $lineAmt = (float) ($sheetRow['amount'] ?? 0);
            $hasAmt = abs($lineAmt) > 0.005;
            $pc = \App\Support\DailyCashMetroLedger::primaryAmountColumn($lineType);
            $rep = $sheetRow['representative_entry'] ?? null;
            $categoryDisplay = $sheetRow['category_display'] ?? '';
            $isCustom = (bool) ($sheetRow['is_custom'] ?? false);
            $sectionSlug = (string) ($sheetRow['section_slug'] ?? '');
            $isAdjustment = $lineType === 'ADJUSTMENT';
            $amtClass = $isAdjustment && $lineAmt < 0 ? 'text-danger' : '';
            $entryCount = (int) ($sheetRow['meaningful_entry_count'] ?? ($rep ? 1 : 0));
            $lineEntries = $entryCount > 1
                ? ($sheetRow['entries'] ?? collect())->filter(fn ($e) => abs((float) $e->amount) > 0.005)->values()
                : collect();
            $breakdownId = 'entries-'.$sectionSlugForGroup.'-'.str_replace([':', '.', ' '], '-', (string) ($sheetRow['category_key'] ?? 'x')).'-'.$lineIdx;
        @endphp
        <tr>
            @if($lineIdx === 0)
                <td rowspan="{{ $typeRowspan }}" class="ps-3 py-2 align-top text-body" style="width:9rem;max-width:11rem;">
                    {{ $parentLabel }}
                </td>
            @endif
            <td class="align-top py-2 text-body">
                {{ $categoryDisplay }}
                @php $bankCap = (float) ($sheetRow['bank_withdrawals_in_capital'] ?? 0); @endphp
                @if($lineType === 'CAPITAL' && abs($bankCap) > 0.005)
                    <span class="d-block text-muted mt-1" style="font-size:0.62rem;">
                        Includes cash from bank withdrawals (₱{{ number_format($bankCap, 2) }})
                    </span>
                @endif
            </td>
            <td class="text-end align-top py-2">
                @if($pc === 'capital' && $hasAmt)
                    <span>₱{{ number_format($lineAmt, 2) }}</span>
                @endif
            </td>
            <td class="text-end align-top py-2">
                @if($pc === 'income' && $hasAmt)
                    ₱{{ number_format($lineAmt, 2) }}
                @endif
            </td>
            <td class="text-end align-top py-2">
                @if($pc === 'expenses' && $hasAmt)
                    ₱{{ number_format($lineAmt, 2) }}
                @endif
            </td>
            <td class="text-end align-top py-2">
                @if($pc === 'discretionary' && $hasAmt)
                    <span>₱{{ number_format($lineAmt, 2) }}</span>
                @endif
            </td>
            <td class="text-end align-top py-2">
                @if($pc === 'savings' && $hasAmt)
                    ₱{{ number_format($lineAmt, 2) }}
                @endif
            </td>
            <td class="text-end align-top py-2 {{ $amtClass }}">
                @if($pc === 'adjustment' && $hasAmt)
                    ₱{{ number_format($lineAmt, 2) }}
                @endif
            </td>
            <td class="text-end align-top py-2">
                @if($pc === 'other' && $hasAmt)
                    ₱{{ number_format($lineAmt, 2) }}
                @endif
            </td>
            <td class="text-end pe-2 align-top py-2" style="white-space:nowrap;">
                @if($rep && ($isCustom || $entryCount <= 1))
                    <button class="btn btn-outline-secondary py-0 px-2"
                            style="font-size:0.75rem;"
                            onclick="editEntry({{ $rep->id }}, {{ json_encode($rep->type) }}, {{ json_encode($rep->description) }}, {{ json_encode((float) $rep->amount) }}, {{ json_encode($rep->category) }}, {{ json_encode($rep->subcategory_override ?? '') }})">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    <form method="POST"
                          action="{{ route('daily-cash.entries.destroy', [$dailyCash, $rep]) }}"
                          class="d-inline"
                          onsubmit="return dailyCashConfirmDeleteEntry()">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger py-0 px-2"
                                style="font-size:0.75rem;">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                @elseif($rep && $entryCount > 1)
                    <button class="btn btn-outline-secondary py-0 px-2"
                            style="font-size:0.75rem;"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#{{ $breakdownId }}">
                        <i class="bi bi-list-ul"></i> {{ $entryCount }} entries
                    </button>
                @endif
            </td>
        </tr>
        @if(!$isCustom && $entryCount > 1)
        <tr class="daily-cash-sheet-breakdown-row">
            <td colspan="10" class="p-0 border-0">
                <div id="{{ $breakdownId }}" class="collapse">
                    <table class="table table-sm mb-0" style="font-size:0.78rem;background:#f8fafc;">
                        <tbody>
                        @foreach($lineEntries as $e)
                            <tr>
                                <td class="ps-4 text-muted">{{ $e->description !== null && $e->description !== '' ? $e->description : '—' }}</td>
                                <td class="text-end" style="width:9rem;">₱{{ number_format((float) $e->amount, 2) }}</td>
                                <td class="text-end pe-2" style="white-space:nowrap;width:11rem;">
                                    <button class="btn btn-outline-secondary py-0 px-2"
                                            style="font-size:0.72rem;"
                                            onclick="editEntry({{ $e->id }}, {{ json_encode($e->type) }}, {{ json_encode($e->description) }}, {{ json_encode((float) $e->amount) }}, {{ json_encode($e->category) }}, {{ json_encode($e->subcategory_override ?? '') }})">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <form method="POST"
                                          action="{{ route('daily-cash.entries.destroy', [$dailyCash, $e]) }}"
                                          class="d-inline"
                                          onsubmit="return dailyCashConfirmDeleteEntry()">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger py-0 px-2"
                                                style="font-size:0.72rem;">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
        @endif
    @endforeach
    @if($sectionSlugForGroup !== '' && !($section['no_add'] ?? false))
        <tr class="daily-cash-sheet-add-row">
            <td colspan="10" class="ps-3 py-1 text-end" style="font-size:0.72rem;background:#f8fafc;">
                <button type="button"
                        class="btn btn-link btn-sm p-0 text-decoration-none"
                        style="font-size:0.72rem;"
                        onclick="dailyCashOpenAddEntryForSection({{ json_encode($sectionSlugForGroup) }})">
                    <i class="bi bi-plus-lg"></i> Add custom row to {{ strtolower($section['heading']) }}
                </button>
            </td>
        </tr>
    @endif
    </tbody>
    @if(!$loop->last)
    <tbody class="daily-cash-sheet-spacer"><tr><td colspan="10"></td></tr></tbody>
    @endif
    @endif
@endforeach
