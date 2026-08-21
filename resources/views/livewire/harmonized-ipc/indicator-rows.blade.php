@php
    $firstRow = $rows[0] ?? [];
    $pendingCount = count($pendingSubTargets ?? []);
    $rowSpan = count($rows) + $pendingCount;
    $cellStyle = 'vertical-align: top !important; border-right: 1px solid var(--border);'.($editing ? ' background-color: #faf3de;' : '');
    $lastCellStyle = 'vertical-align: top !important; border-left: 1px solid var(--border);'.($editing ? ' background-color: #faf3de;' : '');
    $formatValue = static function (mixed $value): string {
        $text = html_entity_decode((string) ($value ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);

        return str_replace(["\r\n", "\r"], "\n", $text ?? '-');
    };
    $textareaClass = 'w-full rounded-md border bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-background';
    $isPositionSelected = ! blank($positionFilter ?? '');
@endphp

<tbody wire:key="harmonized-indicator-component-{{ $indicatorId }}-{{ $editing ? 'edit' : 'view' }}"
    x-data="{
        draggingRow: null,
        dragHandlePressed: false,
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
        get isPositionSelected() {
            return Boolean(this.$wire.positionFilter && String(this.$wire.positionFilter).trim() !== '');
        },
        targetStatus: {{ (int) ($firstRow['target_status'] ?? 0) }},
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
            if (raw) this.$dispatch('harmonized-ipc-target-dropped', { source: JSON.parse(raw), target });
        },
        openContextMenu(event, kra, indicatorId, itemId, subTargetCount) {
            event.preventDefault();

            if (this.targetStatus === 3) {
                return;
            }

            window.dispatchEvent(new CustomEvent('close-all-target-context-menus'));

            const clickedTd = event.target ? event.target.closest('td') : null;
            const isKraOrAction = clickedTd ? (clickedTd.getAttribute('data-col-type') === 'kra-action' || clickedTd.hasAttribute('rowspan')) : false;

            this.contextKra = kra;
            this.contextIndicatorId = indicatorId;
            this.contextItemId = itemId;

            if (!this.isPositionSelected) {
                this.canDeleteTarget = false;
                this.canDeleteSubTarget = false;
            } else if (subTargetCount <= 1) {
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

            const menuWidth = 220;
            const menuHeight = 240;

            if (x + menuWidth > window.innerWidth) {
                x = window.innerWidth - menuWidth - 12;
            }
            if (y + menuHeight > window.innerHeight) {
                y = window.innerHeight - menuHeight - 12;
            }

            this.contextX = Math.max(8, x);
            this.contextY = Math.max(8, y);
            this.showContextMenu = true;
        },
        closeContextMenu() {
            this.showContextMenu = false;
            this.activeSubMenu = null;
            this.isDraggingMenu = false;
            this.contextItemId = 0;
        },
        openAddSubMenu(event = null) {
            if (!this.isPositionSelected) return;

            let rect = null;
            if (event && event.currentTarget) {
                rect = event.currentTarget.getBoundingClientRect();
            } else if (this.$refs.addMenuBtn) {
                rect = this.$refs.addMenuBtn.getBoundingClientRect();
            }

            let subX = rect ? (rect.right - 11) : (this.contextX + 198);
            let subY = rect ? (rect.top - 6) : (this.contextY + 26);

            const subMenuWidth = 192;
            if (subX + subMenuWidth > window.innerWidth) {
                subX = rect ? (rect.left - subMenuWidth + 11) : (this.contextX - subMenuWidth);
            }

            this.subMenuX = Math.max(4, subX);
            this.subMenuY = Math.max(4, subY);
            this.activeSubMenu = 'add';
        },
        toggleAddSubMenu(event = null) {
            if (!this.isPositionSelected) return;
            if (this.activeSubMenu === 'add') {
                this.activeSubMenu = null;
            } else {
                this.openAddSubMenu(event);
            }
        },
        openDeleteSubMenu(event = null) {
            if (!this.isPositionSelected) return;

            let rect = null;
            if (event && event.currentTarget) {
                rect = event.currentTarget.getBoundingClientRect();
            } else if (this.$refs.deleteMenuBtn) {
                rect = this.$refs.deleteMenuBtn.getBoundingClientRect();
            }

            let subX = rect ? (rect.right - 11) : (this.contextX + 198);
            let subY = rect ? (rect.top - 6) : (this.contextY + 110);

            const subMenuWidth = 270;
            if (subX + subMenuWidth > window.innerWidth) {
                subX = rect ? (rect.left - subMenuWidth + 11) : (this.contextX - subMenuWidth);
            }

            this.deleteSubMenuX = Math.max(4, subX);
            this.deleteSubMenuY = Math.max(4, subY);
            this.activeSubMenu = 'delete';
        },
        toggleDeleteSubMenu(event = null) {
            if (!this.isPositionSelected) return;
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
    @foreach ($rows as $groupIndex => $row)
        <tr wire:key="harmonized-row-{{ $row['id'] }}-{{ $editing ? 'edit' : 'view' }}"
            x-on:contextmenu.prevent="openContextMenu($event, {{ (int) ($firstRow['kra_category'] ?? 1) }}, {{ $indicatorId }}, {{ (int) $row['id'] }}, {{ count($rows) }})"
            x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
            x-on:dragend="endDrag()"
            x-on:drop.prevent="dropOn($event, { type: 'main', indicatorId: {{ $indicatorId }}, itemId: {{ (int) ($firstRow['id'] ?? 0) }}, kra: {{ (int) ($firstRow['kra_category'] ?? 0) }} })"
            :class="showContextMenu && contextItemId === {{ (int) $row['id'] }} ? '!bg-sky-100 dark:!bg-sky-950/80 relative z-10' : (draggingRow === {{ $indicatorId }} ? 'shadow-lg shadow-slate-400/40 ring-1 ring-slate-300 bg-white scale-[1.01] relative z-10 cursor-grabbing' : '')"
            x-bind:style="showContextMenu && contextItemId === {{ (int) $row['id'] }} ? 'background-color: #bae6fd !important;' : ''"
            class="border-t border-border/60 text-sm hover:bg-muted/20 transition-colors">
            @if ($groupIndex === 0)
                <td data-col-type="kra-action" rowspan="{{ $rowSpan }}" class="border-b border-r border-border px-3 py-3 align-top text-center text-muted-foreground whitespace-normal break-words" style="{{ $cellStyle }}">
                    <div class="flex items-center justify-center gap-1">
                        @if ($editing || $creatingSubTarget)
                            <div class="flex flex-col items-center gap-1">
                                <flux:button size="xs" variant="ghost" type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" icon="check" style="width: 2.75rem; background-color: #22c55e; color: #fff;" aria-label="{{ __('Save') }}" />
                                <flux:button size="xs" variant="ghost" type="button" wire:click="cancel" wire:loading.attr="disabled" icon="x-mark" style="width: 2.75rem; background-color: #f59e0b; color: #fff;" aria-label="{{ __('Cancel') }}" />
                            </div>
                        @elseif ((int) ($firstRow['target_status'] ?? 0) === 3)
                            <div class="flex items-center justify-center p-1" title="{{ __('Locked target') }}">
                                <flux:icon icon="lock-closed" class="size-5 text-slate-500 dark:text-slate-400" />
                            </div>
                        @elseif ((int) ($firstRow['target_status'] ?? 0) === 1)
                            <div class="flex items-center justify-center">
                                <div draggable="true"
                                    x-on:pointerdown="pressDragHandle()"
                                    x-on:pointerup.window="releaseDragHandle()"
                                    x-on:pointercancel.window="releaseDragHandle()"
                                    x-on:dragstart="startDrag($event, { type: 'main', indicatorId: {{ $indicatorId }}, itemId: 0, kra: {{ (int) ($firstRow['kra_category'] ?? 0) }} })"
                                    x-on:dragend="endDrag($event)"
                                    class="inline-flex items-center justify-center text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition-colors p-1"
                                    x-bind:style="`cursor: ${dragHandlePressed ? 'grabbing' : 'grab'} !important;`"
                                    aria-label="{{ __('Drag main target') }}" title="{{ __('Drag main target') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m0-18l-3 3m3-3l3 3m-3 15l-3-3m3 3l3-3M3 12h18m-18 0l3-3m-3 3l3 3m15-3l-3-3m3 3l-3 3" />
                                    </svg>
                                </div>
                            </div>
                        @endif
                    </div>
                </td>
                <td data-col-type="kra-action" rowspan="{{ $rowSpan }}" class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words" style="{{ $cellStyle }}">
                    @if ($editing && ! $creatingSubTarget)
                        <input type="hidden" wire:model="editCategory">
                        <textarea data-autosize="true" wire:model="editActivity" rows="1" class="{{ $textareaClass }} @error('editActivity') border-red-500 focus-visible:ring-red-500 @else border-input @enderror" style="resize:none;"></textarea>
                    @else
                        {!! nl2br(e($formatValue($firstRow['activity'] ?? null))) !!}
                    @endif
                </td>
            @endif

            <td data-col-type="sub-target" class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words" style="{{ $cellStyle }}">
                @if ($editing && ! $creatingSubTarget)
                    <flux:select wire:model="editRows.{{ $row['id'] }}.semester" style="@error('editRows.'.$row['id'].'.semester') border: 1px solid #ef4444 !important; @enderror">
                        <option value="">{{ __('Select') }}</option>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->value }}">{{ $semester->label }}</option>
                        @endforeach
                    </flux:select>
                @else
                    {{ \App\Support\Semester::label($row['new_semester'] ?? null) }}
                @endif
            </td>

            @foreach (['description' => 'description', 'rg_efficiency_' => 'efficiency', 'rg_quality_' => 'quality', 'rg_timeliness_' => 'timeliness', 'rg_mov_' => 'movs', 'rg_remarks_' => 'remarks'] as $column => $field)
                <td data-col-type="sub-target" class="border-b {{ $loop->last ? 'border-l' : 'border-r' }} border-border px-3 py-3 align-top whitespace-normal break-words" style="{{ $loop->last ? $lastCellStyle : $cellStyle }}">
                    @if ($editing && ! $creatingSubTarget)
                        <textarea data-autosize="true" wire:model="editRows.{{ $row['id'] }}.{{ $field }}" rows="1" class="{{ $textareaClass }} @error('editRows.'.$row['id'].'.'.$field) border-red-500 focus-visible:ring-red-500 @else border-input @enderror" style="resize:none;"></textarea>
                    @else
                        {!! nl2br(e($formatValue($row[$column] ?? null))) !!}
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach

    @if (! empty($pendingSubTargets))
        @foreach ($pendingSubTargets as $pendingIndex => $pendingRow)
            <tr wire:key="harmonized-pending-row-{{ $indicatorId }}-{{ $pendingIndex }}" class="border-t border-border/60 text-sm hover:bg-muted/20">
                <td data-col-type="sub-target" class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words" style="{{ $cellStyle }}">
                    @if ($editing)
                        <flux:select wire:model="pendingSubTargets.{{ $pendingIndex }}.semester" style="@error('pendingSubTargets.'.$pendingIndex.'.semester') border: 1px solid #ef4444 !important; @enderror">
                            <option value="">{{ __('Select') }}</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->value }}">{{ $semester->label }}</option>
                            @endforeach
                        </flux:select>
                    @endif
                </td>

                @foreach (['description' => 'description', 'rg_efficiency_' => 'efficiency', 'rg_quality_' => 'quality', 'rg_timeliness_' => 'timeliness', 'rg_mov_' => 'movs', 'rg_remarks_' => 'remarks'] as $column => $field)
                    <td data-col-type="sub-target" class="border-b {{ $loop->last ? 'border-l' : 'border-r' }} border-border px-3 py-3 align-top whitespace-normal break-words" style="{{ $loop->last ? $lastCellStyle : $cellStyle }}">
                        @if ($editing)
                            <textarea data-autosize="true" wire:model="pendingSubTargets.{{ $pendingIndex }}.{{ $field }}" rows="1" class="{{ $textareaClass }} @error('pendingSubTargets.'.$pendingIndex.'.'.$field) border-red-500 focus-visible:ring-red-500 @else border-input @enderror" style="resize:none;"></textarea>
                        @endif
                </td>
            @endforeach
        </tr>
    @endforeach
    @endif

    <template x-teleport="body">
        <div x-show="showContextMenu"
            x-cloak
            x-on:close-all-target-context-menus.window="closeContextMenu()"
            x-on:click.outside="closeContextMenu()"
            x-on:keydown.escape.window="closeContextMenu()"
            x-on:scroll.window="closeContextMenu()"
            :style="`top: ${contextY}px; left: ${contextX}px; background-color: #ffffff !important; color: #0f172a !important; z-index: 99999 !important; box-shadow: 0 20px 30px -5px rgba(0, 0, 0, 0.3), 0 10px 12px -5px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(0,0,0,0.12) !important;`"
            class="fixed min-w-[14rem] rounded-xl border border-slate-200 bg-white text-slate-900 p-1.5 text-xs font-medium opacity-100 animate-in fade-in-50 zoom-in-95">
            
            <div class="px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border/60 mb-1 flex items-center justify-between cursor-move select-none"
                x-on:pointerdown="startMenuDrag($event)"
                title="{{ __('Drag to move popup') }}">
                <div class="flex items-center gap-1.5">
                    <flux:icon icon="adjustments-horizontal" class="size-3.5 text-emerald-600" />
                    <span>{{ __('OPTIONS') }}</span>
                </div>
            </div>

            <button type="button"
                x-ref="addMenuBtn"
                :disabled="!isPositionSelected"
                x-on:mouseenter="if (isPositionSelected) openAddSubMenu($event)"
                x-on:click="if (isPositionSelected) toggleAddSubMenu($event)"
                class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left transition-colors"
                :class="!isPositionSelected ? 'opacity-40 cursor-not-allowed text-slate-400 dark:text-zinc-500' : (activeSubMenu === 'add' ? 'bg-accent text-accent-foreground' : 'text-foreground hover:bg-accent hover:text-accent-foreground')">
                <div class="flex items-center gap-2">
                    <flux:icon icon="plus-circle" class="size-4" :class="!isPositionSelected ? 'text-slate-400 dark:text-zinc-500' : 'text-slate-700 dark:text-slate-300'" />
                    <span>{{ __('Add Target') }}</span>
                </div>
                <flux:icon icon="chevron-right" class="size-3.5 text-muted-foreground" />
            </button>

            <button type="button"
                :disabled="!isPositionSelected"
                x-on:mouseenter="activeSubMenu = null"
                x-on:click="if (isPositionSelected) { closeContextMenu(); $wire.edit(); }"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left transition-colors"
                :class="!isPositionSelected ? 'opacity-40 cursor-not-allowed text-slate-400 dark:text-zinc-500' : 'text-foreground hover:bg-accent hover:text-accent-foreground'">
                <flux:icon icon="pencil-square" class="size-4" :class="!isPositionSelected ? 'text-slate-400 dark:text-zinc-500' : 'text-amber-500'" />
                <span>{{ __('Edit Target') }}</span>
            </button>

            <div class="my-1 border-t border-border/60"></div>

            <button type="button"
                x-ref="deleteMenuBtn"
                :disabled="!isPositionSelected"
                x-on:mouseenter="if (isPositionSelected) openDeleteSubMenu($event)"
                x-on:click="if (isPositionSelected) toggleDeleteSubMenu($event)"
                class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left transition-colors"
                :class="!isPositionSelected ? 'opacity-40 cursor-not-allowed text-slate-400 dark:text-zinc-500' : (activeSubMenu === 'delete' ? 'bg-accent' : 'text-red-600 dark:text-red-400 hover:bg-accent')">
                <div class="flex items-center gap-2">
                    <flux:icon icon="trash" class="size-4" :class="!isPositionSelected ? 'text-slate-400 dark:text-zinc-500' : 'text-red-500'" />
                    <span>{{ __('Delete') }}</span>
                </div>
                <flux:icon icon="chevron-right" class="size-3.5 text-muted-foreground" />
            </button>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="showContextMenu && activeSubMenu === 'add'"
            x-cloak
            x-on:close-all-target-context-menus.window="closeContextMenu()"
            x-on:keydown.escape.window="closeContextMenu()"
            x-on:scroll.window="closeContextMenu()"
            :style="`top: ${subMenuY}px; left: ${subMenuX}px; background-color: #ffffff !important; color: #0f172a !important; z-index: 100000 !important; box-shadow: 0 20px 30px -5px rgba(0, 0, 0, 0.3), 0 10px 12px -5px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(0,0,0,0.12) !important;`"
            class="fixed min-w-[12rem] rounded-xl border border-slate-200 bg-white text-slate-900 p-1.5 text-xs font-medium opacity-100 animate-in fade-in-50 zoom-in-95">
            
            <button type="button"
                x-on:click="closeContextMenu(); $dispatch('open-add-target-modal', { kraCategory: contextKra, kra: contextKra })"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                <flux:icon icon="plus" class="size-4 text-slate-700 dark:text-slate-300" />
                <span>{{ __('Add new target') }}</span>
            </button>

            <button type="button"
                x-on:click="closeContextMenu(); $wire.requestAddSubTarget()"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                <flux:icon icon="document-plus" class="size-4 text-slate-700 dark:text-slate-300" />
                <span>{{ __('Add sub-target') }}</span>
            </button>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="showContextMenu && activeSubMenu === 'delete'"
            x-cloak
            x-on:close-all-target-context-menus.window="closeContextMenu()"
            x-on:keydown.escape.window="closeContextMenu()"
            x-on:scroll.window="closeContextMenu()"
            :style="`top: ${deleteSubMenuY}px; left: ${deleteSubMenuX}px; background-color: #ffffff !important; color: #0f172a !important; z-index: 100000 !important; box-shadow: 0 20px 30px -5px rgba(0, 0, 0, 0.3), 0 10px 12px -5px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(0,0,0,0.12) !important;`"
            class="fixed min-w-[17rem] rounded-xl border border-slate-200 bg-white text-slate-900 p-1.5 text-xs font-medium opacity-100 animate-in fade-in-50 zoom-in-95">
            
            <button type="button"
                :disabled="!canDeleteTarget"
                x-on:click="if (canDeleteTarget) { closeContextMenu(); $wire.requestDelete(); }"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left transition-colors"
                :class="!canDeleteTarget ? 'opacity-40 cursor-not-allowed text-slate-400 dark:text-zinc-500' : 'text-red-600 dark:text-red-400 hover:bg-accent'">
                <flux:icon icon="trash" class="size-4" :class="!canDeleteTarget ? 'text-slate-400 dark:text-zinc-500' : 'text-red-500'" />
                <span>{{ __('Delete selected target and its sub-target') }}</span>
            </button>

            <button type="button"
                :disabled="!canDeleteSubTarget"
                x-on:click="if (canDeleteSubTarget) { const targetId = contextItemId; closeContextMenu(); $wire.requestDeleteSubTarget(targetId); }"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left transition-colors"
                :class="!canDeleteSubTarget ? 'opacity-40 cursor-not-allowed text-slate-400 dark:text-zinc-500' : 'text-red-600 dark:text-red-400 hover:bg-accent'">
                <flux:icon icon="minus-circle" class="size-4" :class="!canDeleteSubTarget ? 'text-slate-400 dark:text-zinc-500' : 'text-rose-500'" />
                <span>{{ __('Delete selected sub-target') }}</span>
            </button>
        </div>
    </template>
</tbody>
