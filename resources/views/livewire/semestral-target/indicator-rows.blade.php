@php
    $formatValue = static function (mixed $value): string {
        $text = html_entity_decode((string) ($value ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);

        return str_replace(["\r\n", "\r"], "\n", $text ?? '-');
    };
    $textareaClass = 'w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-xs leading-4 text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-background';
    $groupRows = collect($rows)->values();
    $rowSpan = count($groupRows) + count($pendingSubTargets);
    $firstRow = $groupRows->first() ?? [];
    $kraCategory = (int) ($firstRow['kra_category'] ?? 1);
    $isEditingGroup = ($editing || ($creatingSubTarget ?? false));
    $editingHighlightClass = $isEditingGroup ? 'bg-amber-100/90 dark:bg-amber-950/60 text-amber-950 dark:text-amber-100 font-medium' : '';
    $cellStyle = 'vertical-align: top !important; border-right: 1px solid var(--border);';
    $lastCellStyle = 'vertical-align: top !important; border-left: 1px solid var(--border);';
@endphp

<tbody wire:key="semestral-target-indicator-group-{{ $indicatorId }}"
    x-on:semestral-target-updated.window="isSorting = false; draggingRow = null; releaseDragHandle()"
    x-on:semestral-target-swap-reset.window="isSorting = false; draggingRow = null; releaseDragHandle()"
    x-on:semestral-target-swap-completed.window="isSorting = false; draggingRow = null; releaseDragHandle()"
    x-data="semestralTargetGroup(@js($hasHistoryByItem))">
    @foreach ($groupRows as $index => $row)
        @php
            $semItemId = (int) ($row['sem_item_id'] ?? 0);
            $isRowLocked = $isSemesterLocked ?? false;
        @endphp
        <tr wire:key="sem-row-{{ $semItemId }}-{{ $editing ? 'edit' : 'view' }}" data-score-row="{{ $semItemId }}"
            data-kra-category="{{ (int) ($row['kra_category'] ?? $kraCategory) }}"
            data-row-avg="{{ $scores[$semItemId]['average'] ?? '' }}"
            data-has-attachments="{{ (!empty($row['has_attachments']) || ($row['attachment_count'] ?? 0) > 0) ? 1 : 0 }}"
            data-na-quantity="{{ (int) ($row['na_quantity'] ?? 0) }}"
            data-na-quality="{{ (int) ($row['na_quality'] ?? 0) }}"
            data-na-timeliness="{{ (int) ($row['na_timeliness'] ?? 0) }}"
            x-data="semestralScoreRow({
                                                                                                                                    q: @js($scores[$semItemId]['quantity_score'] ?? ''),
                                                                                                                                    ql: @js($scores[$semItemId]['quality_score'] ?? ''),
                                                                                                                                    t: @js($scores[$semItemId]['timeliness_score'] ?? ''),
                                                                                                                                    avg: @js($scores[$semItemId]['average'] ?? ''),
                                                                                                                                    accomp: @js($scores[$semItemId]['actual_accomp'] ?? ''),
                                                                                                                                    movs: @js($scores[$semItemId]['target_movs'] ?? ''),
                                                                                                                                    remarks: @js($scores[$semItemId]['target_remarks'] ?? ''),
                                                                                                                                    itemId: {{ $semItemId }},
                                                                                                                                    semId: @js($row['semester_id'] ?? request()->query('sem_id') ?? 0)
                                                                                                                                })" x-init="initRow()"
            x-on:semestral-target-scores-saved.window="
                                                                                                                                    let savedItems = $event.detail?.savedItems || {};
                                                                                                                                    let saved = savedItems[itemId] || savedItems[String(itemId)];
                                                                                                                                    if (!saved) return;
                                                                                                                                    confirmed = { id: itemId, ...saved };
                                                                                                                                    lastSavedStr = JSON.stringify(confirmed);
                                                                                                                                    isSaving = false;
                                                                                                                                    savingField = '';
                                                                                                                                    let storageKey = 'sem_target_drafts_' + (semId || '0');
                                                                                                                                    try {
                                                                                                                                        let drafts = JSON.parse(localStorage.getItem(storageKey) || '{}');
                                                                                                                                        if (drafts[itemId]) {
                                                                                                                                            delete drafts[itemId];
                                                                                                                                            if (Object.keys(drafts).length === 0) {
                                                                                                                                                localStorage.removeItem(storageKey);
                                                                                                                                            } else {
                                                                                                                                                localStorage.setItem(storageKey, JSON.stringify(drafts));
                                                                                                                                            }
                                                                                                                                        }
                                                                                                                                    } catch(e) {}
                                                                                                                                "
            x-on:semestral-target-scores-failed.window="
                                                                                                                                    let failed = ($event.detail?.items || []).some(item => Number(item.id) === Number(itemId));
                                                                                                                                    if (!failed || !confirmed) return;
                                                                                                                                    isSaving = false;
                                                                                                                                    savingField = '';
                                                                                                                                    q = confirmed.quantity_score || '';
                                                                                                                                    ql = confirmed.quality_score || '';
                                                                                                                                    t = confirmed.timeliness_score || '';
                                                                                                                                    avg = confirmed.average || '';
                                                                                                                                    accomp = confirmed.actual_accomp || '';
                                                                                                                                    movs = confirmed.target_movs || '';
                                                                                                                                    remarks = confirmed.target_remarks || '';
                                                                                                                                    lastSavedStr = JSON.stringify(confirmed);
                                                                                                                                    saveLocalDraft();
                                                                                                                                "
            x-on:contextmenu.prevent="openContextMenu($event, {{ (int) ($row['kra_category'] ?? $kraCategory) }}, {{ $indicatorId }}, {{ $semItemId }}, {{ count($groupRows) }}, {{ $isRowLocked ? 'true' : 'false' }})"
            x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'move'" x-on:dragend="endDrag()"
            x-on:drop.prevent="dropOn($event, { type: '{{ $index === 0 ? 'main' : 'sub' }}', indicatorId: {{ $indicatorId }}, itemId: {{ $semItemId }}, kra: {{ $kraCategory }} })"
            :class="showContextMenu && contextItemId === {{ $semItemId }} ? '!bg-sky-100 dark:!bg-sky-950/80 text-sky-950 dark:text-sky-100 relative z-10' : (draggingRow === {{ $indicatorId }} ? 'shadow-lg shadow-slate-400/40 ring-1 ring-slate-300 bg-white dark:bg-zinc-800 scale-[1.01] relative z-10 cursor-grabbing' : '')"
            class="border-t border-border/60 text-sm hover:bg-muted/20 transition-colors {{ $editingHighlightClass }}">
            @if ($index === 0)
                <td data-col-type="kra-action" rowspan="{{ $rowSpan }}"
                    class="border-b border-r border-border px-3 py-3 align-top text-center" style="{{ $cellStyle }}">
                    <div class="flex items-center justify-center">
                        @if ($editing || $creatingSubTarget)
                            <div class="flex flex-col items-center gap-1">
                                <flux:button size="xs" variant="ghost" type="button" wire:click="save" wire:target="save"
                                    icon="check" style="width: 2.75rem; background-color: #22c55e; color: #fff;"
                                    aria-label="{{ __('Save') }}" />
                                <flux:button size="xs" variant="ghost" type="button" wire:click="cancel" icon="x-mark"
                                    style="width: 2.75rem; background-color: #f59e0b; color: #fff;"
                                    aria-label="{{ __('Cancel') }}" />
                            </div>
                        @else
                            <div class="flex flex-col items-center gap-1.5">
                                <div class="inline-flex items-center justify-center p-1 relative">
                                    <div x-show="!isSorting" draggable="true" x-on:pointerdown="pressDragHandle()"
                                        x-on:pointerup.window="releaseDragHandle()" x-on:pointercancel.window="releaseDragHandle()"
                                        x-on:dragstart="startDrag($event, { type: 'main', indicatorId: {{ $indicatorId }}, itemId: 0, kra: {{ $kraCategory }} })"
                                        x-on:dragend="endDrag($event)"
                                        class="inline-flex items-center justify-center text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition-colors"
                                        x-bind:style="`cursor: ${dragHandlePressed ? 'grabbing' : 'grab'} !important;`"
                                        aria-label="{{ __('Drag main target') }}" title="{{ __('Drag main target') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                            stroke="currentColor" class="size-5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 3v18m0-18l-3 3m3-3l3 3m-3 15l-3-3m3 3l3-3M3 12h18m-18 0l3-3m-3 3l3 3m15-3l-3-3m3 3l-3 3" />
                                        </svg>
                                    </div>
                                    <div x-show="isSorting" class="absolute -right-2 -top-1 flex items-center justify-center"
                                        x-cloak x-transition.opacity>
                                        <svg class="animate-spin size-4 text-emerald-600 dark:text-emerald-400"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                    </div>
                                </div>

                                @if ($hasGroupHistory)
                                    <button type="button"
                                        x-on:click="$dispatch('show-semestral-target-edit-history', { itemId: null, indicatorId: {{ $indicatorId }} })"
                                        class="inline-flex items-center justify-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-950/60 rounded-md p-1 cursor-pointer transition-colors"
                                        style="cursor: pointer !important;" aria-label="{{ __('Show Edit History') }}"
                                        title="{{ __('Show Edit History') }}">
                                        <flux:icon icon="clock" class="size-6" />
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </td>
                <td data-col-type="kra-action" rowspan="{{ $rowSpan }}"
                    class="border-b border-r border-border px-3 py-3 text-xs font-normal text-foreground align-top"
                    style="{{ $cellStyle }}">
                    @if ($editing && !$creatingSubTarget && !($isTargetNewlyAdded ?? false))
                        <textarea data-autosize="true" wire:model="editActivity" rows="1" class="{{ $textareaClass }}"
                            style="resize:none;"></textarea>
                        <div class="mt-2">
                            <flux:select wire:model="editCategory" size="sm">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->value }}">{{ $category->label }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    @else
                        {!! nl2br(e($formatValue($firstRow['activity'] ?? ''))) !!}
                    @endif
                </td>
            @endif

            <td data-col-type="sub-target" class="border-b border-r border-border px-3 py-3 align-top text-xs"
                style="{{ $cellStyle }}">
                @if ($editing && !$creatingSubTarget && isset($editRows[$semItemId]))
                    <textarea data-autosize="true" wire:model="editRows.{{ $semItemId }}.description" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                @else
                    {!! nl2br(e($formatValue($row['description'] ?? ''))) !!}
                @endif
            </td>
            @if ($isRowLocked)
                <td data-col-type="actual-accomp" class="border-b border-r border-border px-3 py-3 align-top text-xs"
                    style="{{ $cellStyle }}">
                    <div class="space-y-2">
                        <textarea data-autosize="true" data-field="actual_accomp" x-model="accomp"
                            x-on:input="scheduleSave('actual_accomp')"
                            x-on:change="saveField('actual_accomp')"
                            x-on:blur="saveField('actual_accomp')" rows="3" placeholder="Actual accomplishment..."
                            class="{{ $textareaClass }} min-h-[100px]" style="resize:none; min-height: 100px;"></textarea>
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-muted-foreground">
                                    {{ trans_choice(':count file uploaded|:count files uploaded', (int) ($row['attachment_count'] ?? 0), ['count' => (int) ($row['attachment_count'] ?? 0)]) }}
                                </span>
                                <span class="text-[10px] text-muted-foreground">
                                    <strong><em>{{ $semItemId }}</em></strong>
                                </span>
                            </div>
                            <flux:button size="xs" type="button" variant="primary"
                                class="bg-emerald-600 text-white hover:bg-emerald-700"
                                wire:click="openAttachmentUpload({{ $semItemId }})" wire:loading.attr="disabled"
                                wire:target="openAttachmentUpload({{ $semItemId }})">
                                {{ __('Upload MOVs') }}
                            </flux:button>
                        </div>
                    </div>
                </td>
            @endif
            <td class="border-b border-r border-border px-3 py-3 align-top text-xs" style="{{ $cellStyle }}">
                @if ($isRowLocked)
                    @php
                        $rawQ = strip_tags($formatValue($row['rg_quantity'] ?? ''));
                        $qFull = $formatValue($row['rg_quantity'] ?? '');
                        $isLongQ = mb_strlen($rawQ) > 45;
                        $qShort = $isLongQ ? mb_substr($rawQ, 0, 45) . '...' : $qFull;
                    @endphp
                    <div x-data="{ expanded: false }" class="space-y-1">
                        <input type="text" data-field="quantity" x-model="q" x-on:keydown.down.prevent="
                                        let valStr = (q || '').toString().trim().toUpperCase();
                                        if (valStr === 'N/A') return;
                                        let num = parseFloat(valStr);
                                        if (!isNaN(num) && num > 1) {
                                            q = (num - 1).toFixed(2);
                                        }
                                        computeAverage();
                                    " x-on:keydown.up.prevent="
                                        let valStr = (q || '').toString().trim().toUpperCase();
                                        if (valStr === 'N/A') {
                                            q = '1';
                                        } else {
                                            let num = parseFloat(valStr);
                                            if (isNaN(num)) {
                                                q = '1';
                                            } else if (num < 5) {
                                                q = Math.min(5, num + 1).toFixed(2);
                                            }
                                        }
                                        computeAverage();
                                    " x-on:input="
                                        let raw = ($el.value || '').trim();
                                        if (raw === '') {
                                            q = '';
                                            computeAverage();
                                            return;
                                        }
                                        let upper = raw.toUpperCase();
                                        if (upper === 'N' || upper === 'NA' || upper === 'N/' || upper === 'N/A') {
                                            q = (upper === 'NA' || upper === 'N/A') ? 'N/A' : upper;
                                            computeAverage();
                                            return;
                                        }
                                        let cleaned = raw.replace(/[^0-9.]/g, '');
                                        let parts = cleaned.split('.');
                                        if (parts.length > 2) {
                                            cleaned = parts[0] + '.' + parts.slice(1).join('');
                                        }
                                        let num = parseFloat(cleaned);
                                        if (!isNaN(num) && num > 5) {
                                            cleaned = '5';
                                        }
                                        q = cleaned;
                                        computeAverage();
                                    " x-on:change="saveField('quantity')" x-on:blur="saveField('quantity')" placeholder="Score (1-5 or N/A)"
                            class="w-full rounded-md border border-input bg-background px-2.5 py-1 text-center text-xs font-semibold text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary mb-1" />
                        <div class="text-[10px] text-muted-foreground italic leading-tight">
                            <span x-show="expanded">{!! nl2br(e($qFull)) !!}</span>
                            <span x-show="!expanded">{!! nl2br(e($qShort)) !!}</span>
                        </div>
                        @if ($isLongQ)
                            <button type="button" @click="expanded = !expanded"
                                class="mt-0.5 inline-flex items-center gap-0.5 text-[10px] font-medium text-emerald-600 dark:text-emerald-400 hover:underline focus:outline-none">
                                <span x-text="expanded ? 'Show less' : 'Show more'"></span>
                                <svg x-show="!expanded" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                                <svg x-show="expanded" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                @elseif ($editing && !$creatingSubTarget && isset($editRows[$semItemId]))
                    <textarea data-autosize="true" wire:model="editRows.{{ $semItemId }}.quantity" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                @else
                    {!! nl2br(e($formatValue($row['rg_quantity'] ?? ''))) !!}
                @endif
            </td>
            <td class="border-b border-r border-border px-3 py-3 align-top text-xs" style="{{ $cellStyle }}">
                @if ($isRowLocked)
                    @php
                        $rawQl = strip_tags($formatValue($row['rg_quality'] ?? ''));
                        $qlFull = $formatValue($row['rg_quality'] ?? '');
                        $isLongQl = mb_strlen($rawQl) > 45;
                        $qlShort = $isLongQl ? mb_substr($rawQl, 0, 45) . '...' : $qlFull;
                    @endphp
                    <div x-data="{ expanded: false }" class="space-y-1">
                        <input type="text" data-field="quality" x-model="ql" x-on:keydown.down.prevent="
                                        let valStr = (ql || '').toString().trim().toUpperCase();
                                        if (valStr === 'N/A') return;
                                        let num = parseFloat(valStr);
                                        if (!isNaN(num) && num > 1) {
                                            ql = (num - 1).toFixed(2);
                                        }
                                        computeAverage();
                                    " x-on:keydown.up.prevent="
                                        let valStr = (ql || '').toString().trim().toUpperCase();
                                        if (valStr === 'N/A') {
                                            ql = '1';
                                        } else {
                                            let num = parseFloat(valStr);
                                            if (isNaN(num)) {
                                                ql = '1';
                                            } else if (num < 5) {
                                                ql = Math.min(5, num + 1).toFixed(2);
                                            }
                                        }
                                        computeAverage();
                                    " x-on:input="
                                        let raw = ($el.value || '').trim();
                                        if (raw === '') {
                                            ql = '';
                                            computeAverage();
                                            return;
                                        }
                                        let upper = raw.toUpperCase();
                                        if (upper === 'N' || upper === 'NA' || upper === 'N/' || upper === 'N/A') {
                                            ql = (upper === 'NA' || upper === 'N/A') ? 'N/A' : upper;
                                            computeAverage();
                                            return;
                                        }
                                        let cleaned = raw.replace(/[^0-9.]/g, '');
                                        let parts = cleaned.split('.');
                                        if (parts.length > 2) {
                                            cleaned = parts[0] + '.' + parts.slice(1).join('');
                                        }
                                        let num = parseFloat(cleaned);
                                        if (!isNaN(num) && num > 5) {
                                            cleaned = '5';
                                        }
                                        ql = cleaned;
                                        computeAverage();
                                    " x-on:change="saveField('quality')" x-on:blur="saveField('quality')" placeholder="Score (1-5 or N/A)"
                            class="w-full rounded-md border border-input bg-background px-2.5 py-1 text-center text-xs font-semibold text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary mb-1" />
                        <div class="text-[10px] text-muted-foreground italic leading-tight">
                            <span x-show="expanded">{!! nl2br(e($qlFull)) !!}</span>
                            <span x-show="!expanded">{!! nl2br(e($qlShort)) !!}</span>
                        </div>
                        @if ($isLongQl)
                            <button type="button" @click="expanded = !expanded"
                                class="mt-0.5 inline-flex items-center gap-0.5 text-[10px] font-medium text-emerald-600 dark:text-emerald-400 hover:underline focus:outline-none">
                                <span x-text="expanded ? 'Show less' : 'Show more'"></span>
                                <svg x-show="!expanded" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                                <svg x-show="expanded" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                @elseif ($editing && !$creatingSubTarget && isset($editRows[$semItemId]))
                    <textarea data-autosize="true" wire:model="editRows.{{ $semItemId }}.quality" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                @else
                    {!! nl2br(e($formatValue($row['rg_quality'] ?? ''))) !!}
                @endif
            </td>
            <td class="border-b border-r border-border px-3 py-3 align-top text-xs" style="{{ $cellStyle }}">
                @if ($isRowLocked)
                    @php
                        $rawT = strip_tags($formatValue($row['rg_timeliness'] ?? ''));
                        $tFull = $formatValue($row['rg_timeliness'] ?? '');
                        $isLongT = mb_strlen($rawT) > 45;
                        $tShort = $isLongT ? mb_substr($rawT, 0, 45) . '...' : $tFull;
                    @endphp
                    <div x-data="{ expanded: false }" class="space-y-1">
                        <input type="text" data-field="timeliness" x-model="t" x-on:keydown.down.prevent="
                                        let valStr = (t || '').toString().trim().toUpperCase();
                                        if (valStr === 'N/A') return;
                                        let num = parseFloat(valStr);
                                        if (!isNaN(num) && num > 1) {
                                            t = (num - 1).toFixed(2);
                                        }
                                        computeAverage();
                                    " x-on:keydown.up.prevent="
                                        let valStr = (t || '').toString().trim().toUpperCase();
                                        if (valStr === 'N/A') {
                                            t = '1';
                                        } else {
                                            let num = parseFloat(valStr);
                                            if (isNaN(num)) {
                                                t = '1';
                                            } else if (num < 5) {
                                                t = Math.min(5, num + 1).toFixed(2);
                                            }
                                        }
                                        computeAverage();
                                    " x-on:input="
                                        let raw = ($el.value || '').trim();
                                        if (raw === '') {
                                            t = '';
                                            computeAverage();
                                            return;
                                        }
                                        let upper = raw.toUpperCase();
                                        if (upper === 'N' || upper === 'NA' || upper === 'N/' || upper === 'N/A') {
                                            t = (upper === 'NA' || upper === 'N/A') ? 'N/A' : upper;
                                            computeAverage();
                                            return;
                                        }
                                        let cleaned = raw.replace(/[^0-9.]/g, '');
                                        let parts = cleaned.split('.');
                                        if (parts.length > 2) {
                                            cleaned = parts[0] + '.' + parts.slice(1).join('');
                                        }
                                        let num = parseFloat(cleaned);
                                        if (!isNaN(num) && num > 5) {
                                            cleaned = '5';
                                        }
                                        t = cleaned;
                                        computeAverage();
                                    " x-on:change="saveField('timeliness')" x-on:blur="saveField('timeliness')" placeholder="Score (1-5 or N/A)"
                            class="w-full rounded-md border border-input bg-background px-2.5 py-1 text-center text-xs font-semibold text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary mb-1" />
                        <div class="text-[10px] text-muted-foreground italic leading-tight">
                            <span x-show="expanded">{!! nl2br(e($tFull)) !!}</span>
                            <span x-show="!expanded">{!! nl2br(e($tShort)) !!}</span>
                        </div>
                        @if ($isLongT)
                            <button type="button" @click="expanded = !expanded"
                                class="mt-0.5 inline-flex items-center gap-0.5 text-[10px] font-medium text-emerald-600 dark:text-emerald-400 hover:underline focus:outline-none">
                                <span x-text="expanded ? 'Show less' : 'Show more'"></span>
                                <svg x-show="!expanded" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                                <svg x-show="expanded" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                @elseif ($editing && !$creatingSubTarget && isset($editRows[$semItemId]))
                    <textarea data-autosize="true" wire:model="editRows.{{ $semItemId }}.timeliness" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                @else
                    {!! nl2br(e($formatValue($row['rg_timeliness'] ?? ''))) !!}
                @endif
            </td>
            @if ($isRowLocked)
                <td class="border-b border-r border-border px-2 py-3 align-top text-center text-xs" style="{{ $cellStyle }}">
                    <input type="hidden" data-field="average" x-model="avg">
                    <div class="font-extrabold text-emerald-600 dark:text-emerald-400 text-xs" x-text="avg || '-'"></div>
                </td>
            @endif
            <td class="border-b border-r border-border px-3 py-3 align-top text-xs" style="{{ $cellStyle }}">
                @if ($isRowLocked)
                    <textarea data-autosize="true" data-field="target_movs" x-model="movs"
                        x-on:input="scheduleSave('target_movs')"
                        x-on:change="saveField('target_movs')"
                        x-on:blur="saveField('target_movs')" rows="3" placeholder="MOVs..."
                        class="{{ $textareaClass }} min-h-[100px]" style="resize:none; min-height: 100px;"></textarea>
                @elseif ($editing && !$creatingSubTarget && isset($editRows[$semItemId]))
                    <textarea data-autosize="true" wire:model="editRows.{{ $semItemId }}.movs" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                @else
                    {!! nl2br(e($formatValue($row['rg_movs'] ?? ''))) !!}
                @endif
            </td>
            <td class="border-b border-l border-border px-3 py-3 align-top text-xs" style="{{ $lastCellStyle }}">
                @if ($isRowLocked)
                    <textarea data-autosize="true" data-field="target_remarks" x-model="remarks"
                        x-on:input="scheduleSave('target_remarks')"
                        x-on:change="saveField('target_remarks')"
                        x-on:blur="saveField('target_remarks')" rows="3" placeholder="Remarks..."
                        class="{{ $textareaClass }} min-h-[100px]" style="resize:none; min-height: 100px;"></textarea>
                @elseif ($editing && !$creatingSubTarget && isset($editRows[$semItemId]))
                    <textarea data-autosize="true" wire:model="editRows.{{ $semItemId }}.remarks" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                @else
                    {!! nl2br(e($formatValue($row['rg_remarks'] ?? ''))) !!}
                @endif
            </td>
        </tr>
    @endforeach

    @if (!empty($pendingSubTargets))
        @foreach ($pendingSubTargets as $pendingIndex => $pendingRow)
            <tr wire:key="sem-pending-row-{{ $indicatorId }}-{{ $pendingIndex }}"
                class="border-t border-border/60 text-sm hover:bg-muted/20">
                <td data-col-type="sub-target" class="border-b border-r border-border px-3 py-3 align-top"
                    style="{{ $cellStyle }}">
                    <textarea data-autosize="true" wire:model="pendingSubTargets.{{ $pendingIndex }}.description" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                </td>
                <td class="border-b border-r border-border px-3 py-3 align-top text-xs" style="{{ $cellStyle }}">
                    <textarea data-autosize="true" wire:model="pendingSubTargets.{{ $pendingIndex }}.quantity" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                </td>
                <td class="border-b border-r border-border px-3 py-3 align-top text-xs" style="{{ $cellStyle }}">
                    <textarea data-autosize="true" wire:model="pendingSubTargets.{{ $pendingIndex }}.quality" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                </td>
                <td class="border-b border-r border-border px-3 py-3 align-top text-xs" style="{{ $cellStyle }}">
                    <textarea data-autosize="true" wire:model="pendingSubTargets.{{ $pendingIndex }}.timeliness" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                </td>
                <td class="border-b border-r border-border px-3 py-3 align-top text-xs" style="{{ $cellStyle }}">
                    <textarea data-autosize="true" wire:model="pendingSubTargets.{{ $pendingIndex }}.movs" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                </td>
                <td class="border-b border-l border-border px-3 py-3 align-top text-xs" style="{{ $lastCellStyle }}">
                    <textarea data-autosize="true" wire:model="pendingSubTargets.{{ $pendingIndex }}.remarks" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                </td>
            </tr>
        @endforeach
    @endif

    <template x-teleport="body">
        <template x-if="showContextMenu">
            <div x-on:close-all-target-context-menus.window="closeContextMenu()" x-on:click.outside="closeContextMenu()"
                :style="`top: ${contextY}px; left: ${contextX}px; z-index: 99999 !important;`"
                class="fixed min-w-[14rem] rounded-xl border border-slate-200 dark:border-zinc-700/80 bg-white dark:bg-zinc-900 text-slate-900 dark:text-zinc-100 p-1.5 text-xs font-medium opacity-100 shadow-2xl animate-in fade-in-50 zoom-in-95">

                <div class="px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border/60 mb-1 flex items-center justify-between cursor-move select-none"
                    x-on:pointerdown="startMenuDrag($event)" title="{{ __('Drag to move popup') }}">
                    <div class="flex items-center gap-1.5">
                        <flux:icon icon="adjustments-horizontal"
                            class="size-3.5 text-emerald-600 dark:text-emerald-400" />
                        <span>{{ __('OPTIONS') }}</span>
                    </div>
                </div>

                <button type="button" x-on:mouseenter="openAddSubMenu($event)" x-on:click="toggleAddSubMenu($event)"
                    class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-accent hover:text-accent-foreground dark:hover:bg-zinc-800 transition-colors"
                    :class="activeSubMenu === 'add' ? 'bg-accent dark:bg-zinc-800 text-accent-foreground' : ''">
                    <div class="flex items-center gap-2">
                        <flux:icon icon="plus-circle" class="size-4 text-slate-700 dark:text-slate-300" />
                        <span>{{ __('Add Target') }}</span>
                    </div>
                    <flux:icon icon="chevron-right" class="size-3.5 text-muted-foreground" />
                </button>

                <button type="button" x-on:mouseenter="activeSubMenu = null"
                    x-on:click="closeContextMenu(); $wire.edit()"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-accent hover:text-accent-foreground dark:hover:bg-zinc-800 transition-colors">
                    <flux:icon icon="pencil-square" class="size-4 text-amber-500 dark:text-amber-400" />
                    <span>{{ __('Edit Target') }}</span>
                </button>

                <button type="button" x-show="Boolean(hasHistoryByItem[contextItemId])"
                    x-on:mouseenter="activeSubMenu = null"
                    x-on:click="const item = contextItemId; const ind = contextIndicatorId; closeContextMenu(); $dispatch('show-semestral-target-edit-history', { itemId: item, indicatorId: ind })"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-accent hover:text-accent-foreground dark:hover:bg-zinc-800 transition-colors">
                    <flux:icon icon="clock" class="size-4 text-blue-500 dark:text-blue-400" />
                    <span>{{ __('Show Edit History') }}</span>
                </button>

                <div class="my-1 border-t border-border/60"></div>

                <button type="button" x-on:mouseenter="openDeleteSubMenu($event)"
                    x-on:click="toggleDeleteSubMenu($event)"
                    class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors"
                    :class="activeSubMenu === 'delete' ? 'bg-red-50 dark:bg-red-950/40' : ''">
                    <div class="flex items-center gap-2">
                        <flux:icon icon="trash" class="size-4 text-red-500 dark:text-red-400" />
                        <span>{{ __('Delete') }}</span>
                    </div>
                    <flux:icon icon="chevron-right" class="size-3.5 text-muted-foreground" />
                </button>
            </div>
        </template>
    </template>

    <template x-teleport="body">
        <template x-if="showContextMenu && activeSubMenu === 'add'">
            <div x-on:close-all-target-context-menus.window="closeContextMenu()"
                :style="`top: ${subMenuY}px; left: ${subMenuX}px; z-index: 100000 !important;`"
                class="fixed min-w-[12rem] rounded-xl border border-slate-200 dark:border-zinc-700/80 bg-white dark:bg-zinc-900 text-slate-900 dark:text-zinc-100 p-1.5 text-xs font-medium opacity-100 shadow-2xl animate-in fade-in-50 zoom-in-95">

                <button type="button"
                    x-on:click="closeContextMenu(); $dispatch('open-add-target-modal', { kraCategory: contextKra, kra: contextKra })"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-accent hover:text-accent-foreground dark:hover:bg-zinc-800 transition-colors">
                    <flux:icon icon="plus" class="size-4 text-slate-700 dark:text-slate-300" />
                    <span>{{ __('Add new target') }}</span>
                </button>

                <button type="button" x-on:click="closeContextMenu(); $wire.requestAddSubTarget()"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-accent hover:text-accent-foreground dark:hover:bg-zinc-800 transition-colors">
                    <flux:icon icon="document-plus" class="size-4 text-slate-700 dark:text-slate-300" />
                    <span>{{ __('Add sub-target') }}</span>
                </button>
            </div>
        </template>
    </template>

    <template x-teleport="body">
        <template x-if="showContextMenu && activeSubMenu === 'delete'">
            <div x-on:close-all-target-context-menus.window="closeContextMenu()"
                :style="`top: ${deleteSubMenuY}px; left: ${deleteSubMenuX}px; z-index: 100000 !important;`"
                class="fixed min-w-[17rem] rounded-xl border border-slate-200 dark:border-zinc-700/80 bg-white dark:bg-zinc-900 text-slate-900 dark:text-zinc-100 p-1.5 text-xs font-medium opacity-100 shadow-2xl animate-in fade-in-50 zoom-in-95">

                <button type="button" :disabled="!canDeleteTarget"
                    x-on:click="if (canDeleteTarget) { closeContextMenu(); $wire.requestDelete(); }"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left transition-colors"
                    :class="!canDeleteTarget ? 'opacity-40 cursor-not-allowed text-slate-400 dark:text-zinc-500' : 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40'">
                    <flux:icon icon="trash" class="size-4"
                        :class="!canDeleteTarget ? 'text-slate-400 dark:text-zinc-500' : 'text-red-500 dark:text-red-400'" />
                    <span>{{ __('Delete selected target and its sub-target') }}</span>
                </button>

                <button type="button" :disabled="!canDeleteSubTarget"
                    x-on:click="if (canDeleteSubTarget) { const targetId = contextItemId; closeContextMenu(); $wire.requestDeleteSubTarget(targetId); }"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left transition-colors"
                    :class="!canDeleteSubTarget ? 'opacity-40 cursor-not-allowed text-slate-400 dark:text-zinc-500' : 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40'">
                    <flux:icon icon="minus-circle" class="size-4"
                        :class="!canDeleteSubTarget ? 'text-slate-400 dark:text-zinc-500' : 'text-rose-500 dark:text-rose-400'" />
                    <span>{{ __('Delete selected sub-target') }}</span>
                </button>
            </div>
        </template>
    </template>

    <!-- Justification Modal for 2026 2nd Semester Edit -->
    @if ($showJustificationModal)
        <template x-teleport="body">
            <flux:modal wire:model="showJustificationModal" dismissible class="max-w-lg">
                <div class="space-y-5">
                    <div class="space-y-1">
                        <flux:heading size="lg">{{ __('Edit Justification Required') }}</flux:heading>
                        <flux:subheading>
                            {{ __('Please provide a reason/justification for modifying this semestral target.') }}
                        </flux:subheading>
                    </div>

                    <div class="grid gap-2">
                        <flux:label>{{ __('Justification Remarks') }} <span class="text-red-500">*</span></flux:label>
                        <textarea wire:model="justificationText" rows="3"
                            placeholder="{{ __('Enter the reason for updating this target...') }}"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost" type="button">
                                {{ __('Cancel') }}
                            </flux:button>
                        </flux:modal.close>
                        <flux:button variant="primary" type="button"
                            class="bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:text-white dark:hover:bg-emerald-700"
                            wire:click="save">
                            {{ __('Submit & Save Changes') }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        </template>
    @endif

    @if ($showAttachmentModal)
        @php
            $galleryAttachments = collect($existingAttachments)->map(fn($attachment) => [
                ...$attachment,
                'url' => asset($attachment['path']),
            ])->values()->all();

            $serverPreviewFiles = [];
            if (!empty($attachmentFiles)) {
                foreach ($attachmentFiles as $f) {
                    if ($f instanceof \Illuminate\Http\UploadedFile) {
                        $ext = strtolower($f->getClientOriginalExtension());
                        $isPdf = $ext === 'pdf';
                        $tempUrl = '';
                        if (!$isPdf) {
                            try {
                                $tempUrl = $f->temporaryUrl();
                            } catch (\Throwable $e) {
                                $tempUrl = '';
                            }
                        }
                        $serverPreviewFiles[] = [
                            'name' => $f->getClientOriginalName(),
                            'size' => number_format($f->getSize() / 1024 / 1024, 2) . ' MB',
                            'type' => $isPdf ? 'pdf' : 'image',
                            'url' => $tempUrl,
                        ];
                    }
                }
            }
        @endphp
        <template x-teleport="body">
            <flux:modal wire:model="showAttachmentModal" dismissible
                class="!w-[65vw] !max-w-[65vw] !h-[80vh] !max-h-[80vh] p-6"
                style="width: 45vw !important; max-width: 45vw !important; height: 85vh !important; max-height: 85vh !important;"
                @close="cancelAttachmentUpload; queuedAttachments = []; activeIndex = -1;">
                <div class="relative flex h-full w-full flex-col overflow-hidden" x-data="{
                                    progress: 0,
                                    uploading: false,
                                    attachments: @js($galleryAttachments),
                                    serverPreviews: @js($serverPreviewFiles),
                                    queuedAttachments: [],
                                    activeIndex: -1,
                                    currentAttachment() {
                                    return this.activeIndex >= 0 && this.activeIndex < this.attachments.length
                                    ? this.attachments[this.activeIndex]
                                    : null;
                                    },
                                    openAttachment(index) {
                                    this.attachments = @js($galleryAttachments);
                                    if (index < 0 || index >= this.attachments.length) return;
                                    this.activeIndex = index;
                                    },
                                    previousAttachment() {
                                    this.attachments = @js($galleryAttachments);
                                    if (!this.attachments.length) return;
                                    this.activeIndex = (this.activeIndex - 1 + this.attachments.length) % this.attachments.length;
                                    },
                                    nextAttachment() {
                                    this.attachments = @js($galleryAttachments);
                                    if (!this.attachments.length) return;
                                    this.activeIndex = (this.activeIndex + 1) % this.attachments.length;
                                    },
                                    closeViewer() { this.activeIndex = -1 },
                                    queueSelectedFiles(event) {
                                    this.queuedAttachments.forEach((file) => {
                                    if (file?.url) {
                                        URL.revokeObjectURL(file.url);
                                    }
                                    });
                                    const files = Array.from(event.target.files || []);
                                    this.queuedAttachments = files.map((file) => ({
                                    name: file.name,
                                    url: file.type === 'application/pdf' ? '' : URL.createObjectURL(file),
                                    type: file.type === 'application/pdf' ? 'pdf' : 'image',
                                    size: `${(file.size / 1024 / 1024).toFixed(2)} MB`,
                                    }));
                                    },
                                    removeQueuedFile(index) {
                                    if (index < 0) return;
                                    if (this.queuedAttachments.length > 0 && index < this.queuedAttachments.length) {
                                    const removed = this.queuedAttachments.splice(index, 1);
                                    if (removed[0]?.url) {
                                        URL.revokeObjectURL(removed[0].url);
                                    }
                                    }
                                    if (this.serverPreviews && index < this.serverPreviews.length) {
                                    this.serverPreviews.splice(index, 1);
                                    }
                                    $wire.removeQueuedAttachment(index);
                                    },
                                    displayPreviews() {
                                    return this.queuedAttachments.length > 0 ? this.queuedAttachments : this.serverPreviews;
                                    }
                                    }" x-on:keydown.escape.window="closeViewer()"
                    x-on:keydown.left.window="if (activeIndex >= 0 && attachments.length > 1) previousAttachment()"
                    x-on:keydown.right.window="if (activeIndex >= 0 && attachments.length > 1) nextAttachment()"
                    x-on:livewire-upload-start="uploading = true; progress = 0"
                    x-on:livewire-upload-finish="uploading = false; progress = 100"
                    x-on:livewire-upload-error="uploading = false"
                    x-on:livewire-upload-progress="progress = $event.detail.progress">
                    <!-- Fixed Modal Header -->
                    <div class="shrink-0 space-y-1 border-b border-border pb-3 mb-2">
                        <flux:heading size="lg">{{ __('Upload MOVs / Attachments') }}</flux:heading>
                        <flux:subheading>
                            {{ __('Select multiple image or PDF files. Maximum file size is 10MB per file.') }}
                        </flux:subheading>
                    </div>

                    <!-- Scrollable Current Attachments Container ONLY -->
                    <div class="min-h-0 flex-1 overflow-y-auto py-2 pr-1 space-y-2">
                        <div
                            class="flex items-center justify-between sticky top-0 bg-background/95 backdrop-blur-sm z-10 py-1">
                            <flux:heading size="sm">{{ __('Current Attachments') }}</flux:heading>
                            <span class="text-xs text-muted-foreground">
                                {{ trans_choice(':count file|:count files', count($galleryAttachments), ['count' => count($galleryAttachments)]) }}
                            </span>
                        </div>

                        @if (count($galleryAttachments) > 0)
                            <div class="!grid !grid-cols-5 !gap-1.5"
                                style="display: grid !important; grid-template-columns: repeat(5, minmax(0, 1fr)) !important; gap: 0.375rem !important; width: 100% !important;">
                                @foreach ($galleryAttachments as $index => $attachment)
                                    <div
                                        class="group relative w-full min-w-0 overflow-hidden rounded-md border border-border bg-card text-left shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-500 hover:shadow-md">
                                        <button type="button"
                                            class="w-full text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                                            x-on:click.prevent.stop="openAttachment({{ $index }})">
                                            <div class="aspect-square w-full overflow-hidden bg-muted"
                                                style="aspect-ratio: 1 / 1 !important;">
                                                @if ($attachment['type'] === 'pdf')
                                                    <div
                                                        class="flex h-full flex-col items-center justify-center gap-1 bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300">
                                                        <flux:icon icon="document-text" class="size-4"
                                                            style="width: 1rem !important; height: 1rem !important;" />
                                                        <span class="rounded bg-red-600 px-1 py-0.5 text-[7px] font-bold text-white"
                                                            style="font-size: 7px !important;">PDF</span>
                                                    </div>
                                                @else
                                                    <img src="{{ $attachment['url'] }}" alt="{{ $attachment['name'] }}" loading="lazy"
                                                        decoding="async"
                                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
                                                @endif
                                            </div>
                                            <div class="p-1 space-y-0">
                                                <div class="truncate text-[8px] font-medium leading-tight text-foreground"
                                                    style="font-size: 8px !important; line-height: 10px !important;"
                                                    title="{{ $attachment['name'] }}">
                                                    {{ $attachment['name'] }}
                                                </div>
                                                <div class="text-[7px] leading-none text-muted-foreground mt-0.5"
                                                    style="font-size: 7px !important; line-height: 8px !important;">
                                                    {{ $attachment['size'] }}
                                                </div>
                                            </div>
                                        </button>
                                        <button type="button" wire:click.stop="deleteAttachment('{{ $attachment['filename'] }}')"
                                            wire:confirm="{{ __('Are you sure you want to delete this attachment?') }}"
                                            class="absolute top-1 right-1 z-20 flex items-center justify-center rounded-full p-1 text-white shadow-md transition hover:scale-110 focus:outline-none"
                                            style="background-color: #dc2626 !important; color: #ffffff !important;"
                                            title="{{ __('Delete attachment') }}" aria-label="{{ __('Delete attachment') }}">
                                            <flux:icon icon="trash" class="size-3"
                                                style="width: 0.75rem !important; height: 0.75rem !important; color: #ffffff !important;" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div
                                class="rounded-xl border border-dashed border-border bg-muted/20 px-4 py-6 text-center text-xs text-muted-foreground">
                                {{ __('No attachments uploaded yet.') }}
                            </div>
                        @endif
                    </div>

                    <!-- Fixed Bottom Footer (Add Attachments + Previews + Actions) -->
                    <div class="shrink-0 border-t border-border pt-3 mt-2 space-y-3 bg-background">
                        <div>
                            <div class="mb-1.5 text-xs font-medium text-foreground">{{ __('Add Attachments') }}</div>
                            <input type="file" multiple
                                accept=".jpg,.jpeg,.png,.pdf,.jfif,.webp,image/jpeg,image/png,image/webp,application/pdf"
                                wire:model="attachmentFiles" x-on:change="queueSelectedFiles($event)"
                                class="block w-full rounded-lg border border-input bg-background px-3 py-1.5 text-xs text-foreground shadow-sm" />
                        </div>

                        <div class="max-h-[140px] overflow-y-auto space-y-1.5" x-show="displayPreviews().length > 0"
                            x-cloak>
                            <div class="flex items-center justify-between">
                                <flux:heading size="sm">{{ __('Selected File Preview') }}</flux:heading>
                                <span class="text-xs text-muted-foreground"
                                    x-text="displayPreviews().length + ' files'"></span>
                            </div>

                            <div class="!grid !grid-cols-5 !gap-1.5"
                                style="display: grid !important; grid-template-columns: repeat(5, minmax(0, 1fr)) !important; gap: 0.375rem !important; width: 100% !important;">
                                <template x-for="(file, index) in displayPreviews()" :key="file.name + '-' + index">
                                    <div
                                        class="group relative w-full min-w-0 overflow-hidden rounded-md border border-dashed border-emerald-400/70 bg-emerald-50/50 text-left shadow-sm">
                                        <div class="aspect-square w-full overflow-hidden bg-white"
                                            style="aspect-ratio: 1 / 1 !important;">
                                            <template x-if="file.type === 'pdf'">
                                                <div
                                                    class="flex h-full flex-col items-center justify-center gap-1 bg-red-50 text-red-700">
                                                    <flux:icon icon="document-text" class="size-4"
                                                        style="width: 1rem !important; height: 1rem !important;" />
                                                    <span
                                                        class="rounded bg-red-600 px-1 py-0.5 text-[7px] font-bold text-white"
                                                        style="font-size: 7px !important;">PDF</span>
                                                </div>
                                            </template>
                                            <template x-if="file.type !== 'pdf'">
                                                <template x-if="file.url">
                                                    <img :src="file.url" :alt="file.name" loading="lazy" decoding="async"
                                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
                                                </template>
                                                <template x-if="!file.url">
                                                    <div
                                                        class="flex h-full flex-col items-center justify-center gap-1 bg-muted p-1 text-center text-muted-foreground">
                                                        <flux:icon icon="document" class="size-4"
                                                            style="width: 1rem !important; height: 1rem !important;" />
                                                        <span class="text-[7px] uppercase font-semibold"
                                                            style="font-size: 7px !important;"
                                                            x-text="file.name.split('.').pop()"></span>
                                                    </div>
                                                </template>
                                            </template>
                                        </div>
                                        <div class="p-1 space-y-0">
                                            <div class="truncate text-[8px] font-medium leading-tight text-foreground"
                                                style="font-size: 8px !important; line-height: 10px !important;"
                                                :title="file.name" x-text="file.name"></div>
                                            <div class="text-[7px] leading-none text-muted-foreground mt-0.5"
                                                style="font-size: 7px !important; line-height: 8px !important;"
                                                x-text="file.size"></div>
                                        </div>
                                        <button type="button" x-on:click.stop.prevent="removeQueuedFile(index)"
                                            class="absolute top-1 right-1 z-30 flex items-center justify-center rounded-full p-1 text-white shadow-lg transition hover:scale-110 focus:outline-none"
                                            style="background-color: #dc2626 !important; color: #ffffff !important; width: 1.25rem !important; height: 1.25rem !important;"
                                            title="{{ __('Remove selected file') }}"
                                            aria-label="{{ __('Remove selected file') }}">
                                            <svg class="h-3 w-3 text-white pointer-events-none" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="py-0.5" x-show="uploading && progress > 0" x-cloak>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-emerald-600 transition-all duration-150"
                                    :style="`width: ${progress}%`"></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-1">
                            <flux:button type="button" variant="primary"
                                class="bg-emerald-600 text-white hover:bg-emerald-700" wire:click="saveAttachmentUploads"
                                wire:loading.attr="disabled" wire:target="saveAttachmentUploads,attachmentFiles">
                                {{ __('Upload Files') }}
                            </flux:button>
                            <flux:modal.close>
                                <flux:button type="button" variant="ghost">
                                    {{ __('Close') }}
                                </flux:button>
                            </flux:modal.close>
                        </div>
                    </div>

                    <div x-show="activeIndex >= 0" x-cloak
                        class="absolute inset-0 z-50 flex items-center justify-center overflow-hidden rounded-2xl bg-slate-950/80 backdrop-blur-md p-4 transition-all"
                        x-on:click.self="closeViewer()">
                        <div class="relative flex h-full w-full flex-col overflow-hidden rounded-xl bg-slate-950/90 border border-slate-800 shadow-2xl backdrop-blur-sm"
                            x-on:click.stop>
                            <!-- Header Bar -->
                            <div
                                class="flex items-center justify-between border-b border-slate-800/80 px-4 py-2.5 bg-slate-950/70">
                                <div class="min-w-0 flex-1 pr-3">
                                    <div class="truncate text-xs font-semibold text-slate-200"
                                        x-text="currentAttachment() ? currentAttachment().name : ''"></div>
                                    <div class="text-[10px] text-slate-400"
                                        x-text="currentAttachment() ? (activeIndex + 1) + ' of ' + attachments.length + ' • ' + currentAttachment().size : ''">
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" x-show="currentAttachment() && currentAttachment().filename"
                                        x-on:click="if (confirm('{{ __('Are you sure you want to delete this attachment?') }}')) { $wire.deleteAttachment(currentAttachment().filename); closeViewer(); }"
                                        class="flex items-center gap-1.5 rounded-md px-2.5 py-1 text-[11px] font-semibold text-white shadow-sm transition hover:opacity-90 focus:outline-none"
                                        style="background-color: #dc2626 !important; color: #ffffff !important;"
                                        title="{{ __('Delete attachment') }}">
                                        <flux:icon icon="trash" class="size-3.5" style="color: #ffffff !important;" />
                                        <span>{{ __('Delete') }}</span>
                                    </button>
                                    <button type="button" x-on:click="closeViewer()"
                                        class="rounded-full bg-slate-800/60 p-1.5 text-slate-300 transition hover:bg-red-600 hover:text-white focus:outline-none"
                                        aria-label="{{ __('Close attachment viewer') }}">
                                        <flux:icon icon="x-mark" class="size-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Preview Content -->
                            <div
                                class="relative min-h-0 flex-1 overflow-hidden p-3 flex items-center justify-center bg-black/40">
                                <template x-if="currentAttachment() && currentAttachment().type === 'image'">
                                    <img x-bind:src="currentAttachment().url" x-bind:alt="currentAttachment().name"
                                        class="h-full w-full rounded-lg object-contain transition duration-200" />
                                </template>
                                <template x-if="currentAttachment() && currentAttachment().type === 'pdf'">
                                    <iframe x-bind:src="currentAttachment().url"
                                        class="h-full w-full rounded-lg bg-white shadow-md"
                                        title="{{ __('PDF attachment viewer') }}"></iframe>
                                </template>
                            </div>

                            <!-- Navigation Controls -->
                            <button type="button" x-show="attachments.length > 1" x-on:click.stop="previousAttachment()"
                                class="absolute z-50 flex items-center justify-center rounded-full bg-slate-900/90 backdrop-blur-md border border-slate-700/80 p-3 text-white shadow-2xl transition-all hover:bg-emerald-600 hover:border-emerald-500 hover:scale-110 active:scale-95 focus:outline-none"
                                style="left: 1rem !important; top: 50% !important; transform: translateY(-50%) !important; right: auto !important;"
                                title="{{ __('Previous attachment') }}" aria-label="{{ __('Previous attachment') }}">
                                <flux:icon icon="chevron-left" class="size-6" />
                            </button>
                            <button type="button" x-show="attachments.length > 1" x-on:click.stop="nextAttachment()"
                                class="absolute z-50 flex items-center justify-center rounded-full bg-slate-900/90 backdrop-blur-md border border-slate-700/80 p-3 text-white shadow-2xl transition-all hover:bg-emerald-600 hover:border-emerald-500 hover:scale-110 active:scale-95 focus:outline-none"
                                style="right: 1rem !important; top: 50% !important; transform: translateY(-50%) !important; left: auto !important;"
                                title="{{ __('Next attachment') }}" aria-label="{{ __('Next attachment') }}">
                                <flux:icon icon="chevron-right" class="size-6" />
                            </button>
                        </div>
                    </div>
                </div>
            </flux:modal>
        </template>
    @endif
</tbody>