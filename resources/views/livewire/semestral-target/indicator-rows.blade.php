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
    x-on:semestral-target-updated.window="isSorting = false; releaseDragHandle()"
    x-on:save-all-scores.window="saveAllScores()" x-data="{
        hasHistoryByItem: @js($hasHistoryByItem),
        saveAllScores() {
            let payload = [];
            this.$el.querySelectorAll('[data-score-row]').forEach(row => {
                let itemId = row.getAttribute('data-score-row');
                let q = row.querySelector('[data-field=\'quantity\']')?.value || '';
                let ql = row.querySelector('[data-field=\'quality\']')?.value || '';
                let t = row.querySelector('[data-field=\'timeliness\']')?.value || '';
                let avg = row.querySelector('[data-field=\'average\']')?.value || '';
                let accomp = row.querySelector('[data-field=\'actual_accomp\']')?.value || '';
                let movs = row.querySelector('[data-field=\'target_movs\']')?.value || '';
                let remarks = row.querySelector('[data-field=\'target_remarks\']')?.value || '';

                payload.push({
                    id: itemId,
                    quantity_score: q,
                    quality_score: ql,
                    timeliness_score: t,
                    average: avg,
                    actual_accomp: accomp,
                    target_movs: movs,
                    target_remarks: remarks
                });
            });
            if (payload.length > 0) {
                $wire.batchSaveScores(payload);
            }
        },
        draggingRow: null,
        dragHandlePressed: false,
        isSorting: false,
        showContextMenu: false,
        contextX: 0,
        contextY: 0,
        activeSubMenu: null,
        subMenuX: 0,
        subMenuY: 0,
        deleteSubMenuX: 0,
        deleteSubMenuY: 0,
        contextKra: 1,
        contextIndicatorId: 0,
        contextItemId: 0,
        canDeleteTarget: true,
        canDeleteSubTarget: false,
        isDraggingMenu: false,
        dragStartX: 0,
        dragStartY: 0,
        initialMenuX: 0,
        initialMenuY: 0,
        pressDragHandle() {
            this.dragHandlePressed = true;
            document.documentElement.classList.add('annual-target-is-dragging');
            document.body.style.setProperty('cursor', 'grabbing', 'important');
        },
        releaseDragHandle() {
            if (this.draggingRow !== null) return;
            this.dragHandlePressed = false;
            document.documentElement.classList.remove('annual-target-is-dragging');
            document.body.style.removeProperty('cursor');
        },
        startDrag(event, target) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('application/json', JSON.stringify(target));
            this.draggingRow = target.indicatorId;
            this.dragHandlePressed = true;
            document.documentElement.classList.add('annual-target-is-dragging');
            document.body.style.setProperty('cursor', 'grabbing', 'important');
        },
        endDrag(event = null) {
            this.draggingRow = null;
            this.dragHandlePressed = false;
            document.documentElement.classList.remove('annual-target-is-dragging');
            document.body.style.removeProperty('cursor');
        },
        dropOn(event, target) {
            const raw = event.dataTransfer.getData('application/json');
            if (raw) {
                this.isSorting = true;
                this.$dispatch('semestral-target-dropped', { source: JSON.parse(raw), target });
            }
        },
        openContextMenu(event, kra, indicatorId, itemId, subTargetCount, isLocked = false) {
            event.preventDefault();
            if (isLocked) {
                return;
            }
            window.dispatchEvent(new CustomEvent('close-all-target-context-menus'));

            const clickedTd = event.target ? event.target.closest('td') : null;
            const isKraOrAction = clickedTd ? (clickedTd.getAttribute('data-col-type') === 'kra-action' || clickedTd.hasAttribute('rowspan')) : false;

            this.contextKra = kra;
            this.contextIndicatorId = indicatorId;
            this.contextItemId = itemId;

            if (subTargetCount <= 1) {
                this.canDeleteTarget = true;
                this.canDeleteSubTarget = false;
            } else if (isKraOrAction) {
                this.canDeleteTarget = true;
                this.canDeleteSubTarget = false;
            } else {
                this.canDeleteTarget = false;
                this.canDeleteSubTarget = true;
            }

            this.activeSubMenu = null;
            let x = event.clientX;
            let y = event.clientY;

            const menuWidth = 190;
            const menuHeight = 160;
            if (x + menuWidth > window.innerWidth) x = window.innerWidth - menuWidth - 8;
            if (y + menuHeight > window.innerHeight) y = window.innerHeight - menuHeight - 8;

            this.contextX = Math.max(8, x);
            this.contextY = Math.max(8, y);
            this.showContextMenu = true;
        },
        closeContextMenu() {
            this.showContextMenu = false;
            this.activeSubMenu = null;
        },
        openAddSubMenu(event = null) {
            this.activeSubMenu = 'add';
            let x = this.contextX + 185;
            let y = this.contextY + 28;

            const subMenuWidth = 180;
            const subMenuHeight = 100;
            if (x + subMenuWidth > window.innerWidth) x = this.contextX - subMenuWidth + 5;
            if (y + subMenuHeight > window.innerHeight) y = window.innerHeight - subMenuHeight - 8;

            this.subMenuX = Math.max(8, x);
            this.subMenuY = Math.max(8, y);
        },
        openDeleteSubMenu(event = null) {
            this.activeSubMenu = 'delete';
            let x = this.contextX + 185;
            let y = this.contextY + 62;

            const subMenuWidth = 180;
            const subMenuHeight = 100;
            if (x + subMenuWidth > window.innerWidth) x = this.contextX - subMenuWidth + 5;
            if (y + subMenuHeight > window.innerHeight) y = window.innerHeight - subMenuHeight - 8;

            this.deleteSubMenuX = Math.max(8, x);
            this.deleteSubMenuY = Math.max(8, y);
        },
        toggleAddSubMenu(event) {
            if (this.activeSubMenu === 'add') {
                this.activeSubMenu = null;
            } else {
                this.openAddSubMenu(event);
            }
        },
        toggleDeleteSubMenu(event) {
            if (this.activeSubMenu === 'delete') {
                this.activeSubMenu = null;
            } else {
                this.openDeleteSubMenu(event);
            }
        },
        startMenuDrag(event) {
            this.isDraggingMenu = true;
            this.dragStartX = event.clientX;
            this.dragStartY = event.clientY;
            this.initialMenuX = this.contextX;
            this.initialMenuY = this.contextY;

            const onPointerMove = (e) => {
                if (!this.isDraggingMenu) return;
                const dx = e.clientX - this.dragStartX;
                const dy = e.clientY - this.dragStartY;
                this.contextX = Math.max(4, Math.min(window.innerWidth - 230, this.initialMenuX + dx));
                this.contextY = Math.max(4, Math.min(window.innerHeight - 250, this.initialMenuY + dy));
                if (this.activeSubMenu === 'add') {
                    this.openAddSubMenu();
                } else if (this.activeSubMenu === 'delete') {
                    this.openDeleteSubMenu();
                }
            };

            const onPointerUp = () => {
                this.isDraggingMenu = false;
                window.removeEventListener('pointermove', onPointerMove);
                window.removeEventListener('pointerup', onPointerUp);
            };

            window.addEventListener('pointermove', onPointerMove);
            window.addEventListener('pointerup', onPointerUp);
        }
    }">
    @foreach ($groupRows as $index => $row)
        @php
            $semItemId = (int) ($row['sem_item_id'] ?? 0);
            $isRowLocked = $isSemesterLocked ?? false;
        @endphp
        <tr wire:key="sem-row-{{ $semItemId }}-{{ $editing ? 'edit' : 'view' }}" data-score-row="{{ $semItemId }}" x-data="{
                        q: @js($scores[$semItemId]['quantity_score'] ?? ''),
                        ql: @js($scores[$semItemId]['quality_score'] ?? ''),
                        t: @js($scores[$semItemId]['timeliness_score'] ?? ''),
                        avg: @js($scores[$semItemId]['average'] ?? ''),
                        accomp: @js($scores[$semItemId]['actual_accomp'] ?? ''),
                        movs: @js($scores[$semItemId]['target_movs'] ?? ''),
                        remarks: @js($scores[$semItemId]['target_remarks'] ?? ''),
                        computeAverage() {
                            let nums = [];
                            let isNa = false;
                            let qVal = (this.q || '').toString().trim().toUpperCase();
                            let qlVal = (this.ql || '').toString().trim().toUpperCase();
                            let tVal = (this.t || '').toString().trim().toUpperCase();

                            if (qVal === 'N/A') isNa = true;
                            else if (qVal !== '' && !isNaN(parseFloat(qVal))) nums.push(parseFloat(qVal));

                            if (qlVal === 'N/A') isNa = true;
                            else if (qlVal !== '' && !isNaN(parseFloat(qlVal))) nums.push(parseFloat(qlVal));

                            if (tVal === 'N/A') isNa = true;
                            else if (tVal !== '' && !isNaN(parseFloat(tVal))) nums.push(parseFloat(tVal));

                            if (nums.length > 0) {
                                let mean = nums.reduce((a, b) => a + b, 0) / nums.length;
                                this.avg = mean.toFixed(2);
                            } else if (isNa) {
                                this.avg = 'N/A';
                            } else {
                                this.avg = '';
                            }
                        }
                    }" x-init="computeAverage()"
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
                                <flux:button size="xs" variant="ghost" type="button" wire:click="save" wire:loading.attr="disabled"
                                    wire:target="save" icon="check" style="width: 2.75rem; background-color: #22c55e; color: #fff;"
                                    aria-label="{{ __('Save') }}" />
                                <flux:button size="xs" variant="ghost" type="button" wire:click="cancel"
                                    wire:loading.attr="disabled" icon="x-mark"
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
                                    <div x-show="isSorting" class="flex items-center justify-center" x-cloak>
                                        <svg class="animate-spin size-5 text-emerald-600 dark:text-emerald-400"
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
                    <textarea data-autosize="true" data-field="actual_accomp" x-model="accomp"
                        x-init="$nextTick(() => { $el.style.height = '100px'; $el.style.height = Math.max(100, $el.scrollHeight) + 'px'; })"
                        x-on:input="$el.style.height = '100px'; $el.style.height = Math.max(100, $el.scrollHeight) + 'px'"
                        rows="3" placeholder="Actual accomplishment..." class="{{ $textareaClass }} min-h-[100px]"
                        style="resize:none; min-height: 100px;"></textarea>
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
                                                if (isNaN(num) || num <= 1) {
                                                    q = 'N/A';
                                                } else {
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
                                            " placeholder="Score (1-5 or N/A)"
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
                                                if (isNaN(num) || num <= 1) {
                                                    ql = 'N/A';
                                                } else {
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
                                            " placeholder="Score (1-5 or N/A)"
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
                                                if (isNaN(num) || num <= 1) {
                                                    t = 'N/A';
                                                } else {
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
                                            " placeholder="Score (1-5 or N/A)"
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
                    <div class="font-extrabold text-emerald-600 dark:text-emerald-400 text-xs" x-text="avg || '-'">
                    </div>
                </td>
            @endif
            <td class="border-b border-r border-border px-3 py-3 align-top text-xs" style="{{ $cellStyle }}">
                @if ($isRowLocked)
                    <textarea data-autosize="true" data-field="target_movs" x-model="movs"
                        x-init="$nextTick(() => { $el.style.height = '100px'; $el.style.height = Math.max(100, $el.scrollHeight) + 'px'; })"
                        x-on:input="$el.style.height = '100px'; $el.style.height = Math.max(100, $el.scrollHeight) + 'px'"
                        rows="3" placeholder="MOVs..." class="{{ $textareaClass }} min-h-[100px]"
                        style="resize:none; min-height: 100px;"></textarea>
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
                        x-init="$nextTick(() => { $el.style.height = '100px'; $el.style.height = Math.max(100, $el.scrollHeight) + 'px'; })"
                        x-on:input="$el.style.height = '100px'; $el.style.height = Math.max(100, $el.scrollHeight) + 'px'"
                        rows="3" placeholder="Remarks..." class="{{ $textareaClass }} min-h-[100px]"
                        style="resize:none; min-height: 100px;"></textarea>
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
        <div x-show="showContextMenu" x-cloak x-on:close-all-target-context-menus.window="closeContextMenu()"
            x-on:click.outside="closeContextMenu()" x-on:keydown.escape.window="closeContextMenu()"
            x-on:scroll.window="closeContextMenu()"
            :style="`top: ${contextY}px; left: ${contextX}px; z-index: 99999 !important;`"
            class="fixed min-w-[14rem] rounded-xl border border-slate-200 dark:border-zinc-700/80 bg-white dark:bg-zinc-900 text-slate-900 dark:text-zinc-100 p-1.5 text-xs font-medium opacity-100 shadow-2xl animate-in fade-in-50 zoom-in-95">

            <div class="px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border/60 mb-1 flex items-center justify-between cursor-move select-none"
                x-on:pointerdown="startMenuDrag($event)" title="{{ __('Drag to move popup') }}">
                <div class="flex items-center gap-1.5">
                    <flux:icon icon="adjustments-horizontal" class="size-3.5 text-emerald-600 dark:text-emerald-400" />
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

            <button type="button" x-on:mouseenter="activeSubMenu = null" x-on:click="closeContextMenu(); $wire.edit()"
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

            <button type="button" x-on:mouseenter="openDeleteSubMenu($event)" x-on:click="toggleDeleteSubMenu($event)"
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

    <template x-teleport="body">
        <div x-show="showContextMenu && activeSubMenu === 'add'" x-cloak
            x-on:close-all-target-context-menus.window="closeContextMenu()"
            x-on:keydown.escape.window="closeContextMenu()" x-on:scroll.window="closeContextMenu()"
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

    <template x-teleport="body">
        <div x-show="showContextMenu && activeSubMenu === 'delete'" x-cloak
            x-on:close-all-target-context-menus.window="closeContextMenu()"
            x-on:keydown.escape.window="closeContextMenu()" x-on:scroll.window="closeContextMenu()"
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

    <!-- Justification Modal for 2026 2nd Semester Edit -->
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
</tbody>