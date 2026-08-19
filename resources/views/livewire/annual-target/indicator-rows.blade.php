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
@endphp

<tbody wire:key="indicator-component-{{ $indicatorId }}-{{ $editing ? 'edit' : 'view' }}"
    x-data="{
        draggingRow: null,
        dragHandlePressed: false,
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
            if (raw) this.$dispatch('annual-target-target-dropped', { source: JSON.parse(raw), target });
        }
    }">
    @foreach ($rows as $groupIndex => $row)
        <tr wire:key="indicator-row-{{ $row['id'] }}-{{ $editing ? 'edit' : 'view' }}"
            x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
            x-on:dragend="endDrag()"
            x-on:drop.prevent="dropOn($event, { type: 'main', indicatorId: {{ $indicatorId }}, itemId: {{ (int) ($firstRow['id'] ?? 0) }}, kra: {{ (int) ($firstRow['kra_category'] ?? 0) }} })"
            :class="draggingRow === {{ $indicatorId }} ? 'shadow-lg shadow-slate-400/40 ring-1 ring-slate-300 bg-white scale-[1.01] relative z-10 cursor-grabbing' : ''"
            class="odd:bg-background even:bg-muted/25 hover:bg-accent/45 transition-colors">
            @if ($groupIndex === 0)
                <td rowspan="{{ $rowSpan }}" class="border-b border-r border-border px-3 py-3 align-top text-center text-muted-foreground whitespace-normal break-words" style="{{ $cellStyle }}">
                    <div class="flex items-center justify-center gap-1">
                        @if ($editing || $creatingSubTarget)
                            <div class="flex flex-col items-center gap-1">
                                <flux:button size="xs" variant="ghost" type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" icon="check" style="width: 2.75rem; background-color: #22c55e; color: #fff;" aria-label="{{ __('Save') }}" />
                                <flux:button size="xs" variant="ghost" type="button" wire:click="cancel" wire:loading.attr="disabled" icon="x-mark" style="width: 2.75rem; background-color: #f59e0b; color: #fff;" aria-label="{{ __('Cancel') }}" />
                            </div>
                        @elseif ((int) ($firstRow['target_status'] ?? 0) === 3)
                            <flux:icon icon="lock-closed" class="size-3.5 text-muted-foreground" />
                        @elseif ((int) ($firstRow['target_status'] ?? 0) === 1)
                            <div class="flex flex-col items-start gap-1">
                                <flux:button size="xs" variant="primary" icon="pencil-square" class="h-7 justify-center px-2 text-xs" style="width: 2.75rem;" wire:click="edit" wire:loading.attr="disabled" wire:target="edit" aria-label="{{ __('Edit') }}" />
                                <flux:button size="xs" variant="danger" icon="trash" class="h-7 justify-center px-2 text-xs" style="width: 2.75rem;" wire:click="requestDelete" aria-label="{{ __('Delete') }}" />
                                <flux:button size="xs" variant="primary" icon="plus" class="h-7 justify-center px-2 text-xs" style="width: 2.75rem; background-color: #2563eb; color: #fff; border-color: #2563eb;" wire:click="requestAddSubTarget" wire:loading.attr="disabled" wire:target="requestAddSubTarget" aria-label="{{ __('Add sub target') }}" />
                                <button type="button" draggable="true"
                                    x-on:pointerdown="pressDragHandle()"
                                    x-on:pointerup.window="releaseDragHandle()"
                                    x-on:pointercancel.window="releaseDragHandle()"
                                    x-on:dragstart="startDrag($event, { type: 'main', indicatorId: {{ $indicatorId }}, itemId: 0, kra: {{ (int) ($firstRow['kra_category'] ?? 0) }} })"
                                    x-on:dragend="endDrag($event)"
                                    class="flex h-7 items-center justify-center rounded-md border border-slate-300 bg-slate-100 text-slate-600 hover:bg-slate-200"
                                    x-bind:style="`width: 2.75rem; cursor: ${dragHandlePressed ? 'grabbing' : 'grab'} !important;`"
                                    aria-label="{{ __('Drag main target') }}" title="{{ __('Drag main target') }}">
                                    <flux:icon icon="bars-3" class="size-4" />
                                </button>
                            </div>
                        @endif
                    </div>
                </td>
                <td rowspan="{{ $rowSpan }}" class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words" style="{{ $cellStyle }}">
                    @if ($editing && ! $creatingSubTarget)
                        <input type="hidden" wire:model="editCategory">
                        <textarea data-autosize="true" wire:model="editActivity" rows="1" class="{{ $textareaClass }} @error('editActivity') border-red-500 focus-visible:ring-red-500 @else border-input @enderror" style="resize:none;"></textarea>
                    @else
                        {!! nl2br(e($formatValue($firstRow['activity'] ?? null))) !!}
                    @endif
                </td>
            @endif

            <td class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words" style="{{ $cellStyle }}">
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
                <td class="border-b {{ $loop->last ? 'border-l' : 'border-r' }} border-border px-3 py-3 align-top whitespace-normal break-words" style="{{ $loop->last ? $lastCellStyle : $cellStyle }}">
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
            <tr wire:key="indicator-pending-row-{{ $indicatorId }}-{{ $pendingIndex }}" class="odd:bg-background even:bg-muted/25 hover:bg-accent/45 transition-colors">
                <td class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words" style="{{ $cellStyle }}">
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
                    <td class="border-b {{ $loop->last ? 'border-l' : 'border-r' }} border-border px-3 py-3 align-top whitespace-normal break-words" style="{{ $loop->last ? $lastCellStyle : $cellStyle }}">
                        @if ($editing)
                            <textarea data-autosize="true" wire:model="pendingSubTargets.{{ $pendingIndex }}.{{ $field }}" rows="1" class="{{ $textareaClass }} @error('pendingSubTargets.'.$pendingIndex.'.'.$field) border-red-500 focus-visible:ring-red-500 @else border-input @enderror" style="resize:none;"></textarea>
                        @endif
                </td>
            @endforeach
        </tr>
    @endforeach
    @endif
</tbody>
