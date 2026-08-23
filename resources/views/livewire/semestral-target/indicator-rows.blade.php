@php
    $formatValue = static function (mixed $value): string {
        $text = html_entity_decode((string) ($value ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);

        return str_replace(["\r\n", "\r"], "\n", $text ?? '-');
    };
    $textareaClass = 'w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-background';
    $groupRows = collect($rows)->values();
    $rowSpan = count($groupRows) + count($pendingSubTargets);
    $firstRow = $groupRows->first() ?? [];
    $kraCategory = (int) ($firstRow['kra_category'] ?? 1);
    $cellStyle = 'vertical-align: top !important; border-right: 1px solid var(--border);' . (($editing || $creatingSubTarget) ? ' background-color: #faf3de !important;' : '');
    $lastCellStyle = 'vertical-align: top !important; border-left: 1px solid var(--border);' . (($editing || $creatingSubTarget) ? ' background-color: #faf3de !important;' : '');
@endphp

<tbody wire:key="semestral-target-indicator-group-{{ $indicatorId }}" x-data="{
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
            if (raw) this.$dispatch('semestral-target-dropped', { source: JSON.parse(raw), target });
        },
        openContextMenu(event, kra, indicatorId, itemId, subTargetCount) {
            event.preventDefault();
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
        @endphp
        <tr wire:key="sem-row-{{ $semItemId }}-{{ $editing ? 'edit' : 'view' }}"
            x-on:contextmenu.prevent="openContextMenu($event, {{ $kraCategory }}, {{ $indicatorId }}, {{ $semItemId }}, {{ count($groupRows) }})"
            x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'move'" x-on:dragend="endDrag()"
            x-on:drop.prevent="dropOn($event, { type: '{{ $index === 0 ? 'main' : 'sub' }}', indicatorId: {{ $indicatorId }}, itemId: {{ $semItemId }}, kra: {{ $kraCategory }} })"
            :class="showContextMenu && contextItemId === {{ $semItemId }} ? '!bg-sky-100 dark:!bg-sky-950/80 relative z-10' : (draggingRow === {{ $indicatorId }} ? 'shadow-lg shadow-slate-400/40 ring-1 ring-slate-300 bg-white scale-[1.01] relative z-10 cursor-grabbing' : '')"
            x-bind:style="showContextMenu && contextItemId === {{ $semItemId }} ? 'background-color: #bae6fd !important;' : ''"
            class="border-t border-border/60 text-sm hover:bg-muted/20 transition-colors">
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
                            <div draggable="true" x-on:pointerdown="pressDragHandle()" x-on:pointerup.window="releaseDragHandle()"
                                x-on:pointercancel.window="releaseDragHandle()"
                                x-on:dragstart="startDrag($event, { type: 'main', indicatorId: {{ $indicatorId }}, itemId: 0, kra: {{ $kraCategory }} })"
                                x-on:dragend="endDrag($event)"
                                class="inline-flex items-center justify-center text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 transition-colors p-1"
                                x-bind:style="`cursor: ${dragHandlePressed ? 'grabbing' : 'grab'} !important;`"
                                aria-label="{{ __('Drag main target') }}" title="{{ __('Drag main target') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 3v18m0-18l-3 3m3-3l3 3m-3 15l-3-3m3 3l3-3M3 12h18m-18 0l3-3m-3 3l3 3m15-3l-3-3m3 3l-3 3" />
                                </svg>
                            </div>
                        @endif
                    </div>
                </td>
                <td data-col-type="kra-action" rowspan="{{ $rowSpan }}"
                    class="border-b border-r border-border px-3 py-3 font-semibold text-foreground align-top"
                    style="{{ $cellStyle }}">
                    @if ($editing && !$creatingSubTarget)
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

            <td data-col-type="sub-target" class="border-b border-r border-border px-3 py-3 align-top"
                style="{{ $cellStyle }}">
                @if ($editing && !$creatingSubTarget)
                    <textarea data-autosize="true" wire:model="editRows.{{ $semItemId }}.description" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                @else
                    {!! nl2br(e($formatValue($row['description'] ?? ''))) !!}
                @endif
            </td>
            <td class="border-b border-r border-border px-3 py-3 align-top text-xs" style="{{ $cellStyle }}">
                @if ($editing && !$creatingSubTarget)
                    <textarea data-autosize="true" wire:model="editRows.{{ $semItemId }}.quantity" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                @else
                    {!! nl2br(e($formatValue($row['rg_quantity'] ?? ''))) !!}
                @endif
            </td>
            <td class="border-b border-r border-border px-3 py-3 align-top text-xs" style="{{ $cellStyle }}">
                @if ($editing && !$creatingSubTarget)
                    <textarea data-autosize="true" wire:model="editRows.{{ $semItemId }}.quality" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                @else
                    {!! nl2br(e($formatValue($row['rg_quality'] ?? ''))) !!}
                @endif
            </td>
            <td class="border-b border-r border-border px-3 py-3 align-top text-xs" style="{{ $cellStyle }}">
                @if ($editing && !$creatingSubTarget)
                    <textarea data-autosize="true" wire:model="editRows.{{ $semItemId }}.timeliness" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                @else
                    {!! nl2br(e($formatValue($row['rg_timeliness'] ?? ''))) !!}
                @endif
            </td>
            <td class="border-b border-r border-border px-3 py-3 align-top text-xs" style="{{ $cellStyle }}">
                @if ($editing && !$creatingSubTarget)
                    <textarea data-autosize="true" wire:model="editRows.{{ $semItemId }}.movs" rows="1"
                        class="{{ $textareaClass }}" style="resize:none;"></textarea>
                @else
                    {!! nl2br(e($formatValue($row['rg_movs'] ?? ''))) !!}
                @endif
            </td>
            <td class="border-b border-l border-border px-3 py-3 align-top text-xs" style="{{ $lastCellStyle }}">
                @if ($editing && !$creatingSubTarget)
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
            :style="`top: ${contextY}px; left: ${contextX}px; background-color: #ffffff !important; color: #0f172a !important; z-index: 99999 !important; box-shadow: 0 20px 30px -5px rgba(0, 0, 0, 0.3), 0 10px 12px -5px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(0,0,0,0.12) !important;`"
            class="fixed min-w-[14rem] rounded-xl border border-slate-200 bg-white text-slate-900 p-1.5 text-xs font-medium opacity-100 animate-in fade-in-50 zoom-in-95">

            <div class="px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-muted-foreground border-b border-border/60 mb-1 flex items-center justify-between cursor-move select-none"
                x-on:pointerdown="startMenuDrag($event)" title="{{ __('Drag to move popup') }}">
                <div class="flex items-center gap-1.5">
                    <flux:icon icon="adjustments-horizontal" class="size-3.5 text-emerald-600" />
                    <span>{{ __('OPTIONS') }}</span>
                </div>
            </div>

            <button type="button" x-on:mouseenter="openAddSubMenu($event)" x-on:click="toggleAddSubMenu($event)"
                class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                :class="activeSubMenu === 'add' ? 'bg-accent text-accent-foreground' : ''">
                <div class="flex items-center gap-2">
                    <flux:icon icon="plus-circle" class="size-4 text-slate-700 dark:text-slate-300" />
                    <span>{{ __('Add Target') }}</span>
                </div>
                <flux:icon icon="chevron-right" class="size-3.5 text-muted-foreground" />
            </button>

            <button type="button" x-on:mouseenter="activeSubMenu = null" x-on:click="closeContextMenu(); $wire.edit()"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                <flux:icon icon="pencil-square" class="size-4 text-amber-500" />
                <span>{{ __('Edit Target') }}</span>
            </button>

            <div class="my-1 border-t border-border/60"></div>

            <button type="button" x-on:mouseenter="openDeleteSubMenu($event)" x-on:click="toggleDeleteSubMenu($event)"
                class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-1.5 text-left text-red-600 dark:text-red-400 hover:bg-accent transition-colors"
                :class="activeSubMenu === 'delete' ? 'bg-accent' : ''">
                <div class="flex items-center gap-2">
                    <flux:icon icon="trash" class="size-4 text-red-500" />
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
            :style="`top: ${subMenuY}px; left: ${subMenuX}px; background-color: #ffffff !important; color: #0f172a !important; z-index: 100000 !important; box-shadow: 0 20px 30px -5px rgba(0, 0, 0, 0.3), 0 10px 12px -5px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(0,0,0,0.12) !important;`"
            class="fixed min-w-[12rem] rounded-xl border border-slate-200 bg-white text-slate-900 p-1.5 text-xs font-medium opacity-100 animate-in fade-in-50 zoom-in-95">

            <button type="button"
                x-on:click="closeContextMenu(); $dispatch('open-add-target-modal', { kraCategory: contextKra, kra: contextKra })"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                <flux:icon icon="plus" class="size-4 text-slate-700 dark:text-slate-300" />
                <span>{{ __('Add new target') }}</span>
            </button>

            <button type="button" x-on:click="closeContextMenu(); $wire.requestAddSubTarget()"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                <flux:icon icon="document-plus" class="size-4 text-slate-700 dark:text-slate-300" />
                <span>{{ __('Add sub-target') }}</span>
            </button>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="showContextMenu && activeSubMenu === 'delete'" x-cloak
            x-on:close-all-target-context-menus.window="closeContextMenu()"
            x-on:keydown.escape.window="closeContextMenu()" x-on:scroll.window="closeContextMenu()"
            :style="`top: ${deleteSubMenuY}px; left: ${deleteSubMenuX}px; background-color: #ffffff !important; color: #0f172a !important; z-index: 100000 !important; box-shadow: 0 20px 30px -5px rgba(0, 0, 0, 0.3), 0 10px 12px -5px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(0,0,0,0.12) !important;`"
            class="fixed min-w-[17rem] rounded-xl border border-slate-200 bg-white text-slate-900 p-1.5 text-xs font-medium opacity-100 animate-in fade-in-50 zoom-in-95">

            <button type="button" :disabled="!canDeleteTarget"
                x-on:click="if (canDeleteTarget) { closeContextMenu(); $wire.requestDelete(); }"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left transition-colors"
                :class="!canDeleteTarget ? 'opacity-40 cursor-not-allowed text-slate-400 dark:text-zinc-500' : 'text-red-600 dark:text-red-400 hover:bg-accent'">
                <flux:icon icon="trash" class="size-4"
                    :class="!canDeleteTarget ? 'text-slate-400 dark:text-zinc-500' : 'text-red-500'" />
                <span>{{ __('Delete selected target and its sub-target') }}</span>
            </button>

            <button type="button" :disabled="!canDeleteSubTarget"
                x-on:click="if (canDeleteSubTarget) { const targetId = contextItemId; closeContextMenu(); $wire.requestDeleteSubTarget(targetId); }"
                class="flex w-full items-center gap-2 rounded-lg px-2.5 py-1.5 text-left transition-colors"
                :class="!canDeleteSubTarget ? 'opacity-40 cursor-not-allowed text-slate-400 dark:text-zinc-500' : 'text-red-600 dark:text-red-400 hover:bg-accent'">
                <flux:icon icon="minus-circle" class="size-4"
                    :class="!canDeleteSubTarget ? 'text-slate-400 dark:text-zinc-500' : 'text-rose-500'" />
                <span>{{ __('Delete selected sub-target') }}</span>
            </button>
        </div>
    </template>
</tbody>