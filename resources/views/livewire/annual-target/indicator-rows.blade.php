@php
    $firstRow = $rows[0] ?? [];
    $rowSpan = count($rows);
    $cellStyle = 'vertical-align: top !important; border-right: 1px solid var(--border);'.($editing ? ' background-color: #faf3de;' : '');
    $lastCellStyle = 'vertical-align: top !important; border-left: 1px solid var(--border);'.($editing ? ' background-color: #faf3de;' : '');
    $formatValue = static function (mixed $value): string {
        $text = html_entity_decode((string) ($value ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);

        return str_replace(["\r\n", "\r"], "\n", $text ?? '-');
    };
    $textareaClass = 'w-full rounded-md border bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-background';
@endphp

<tbody wire:key="indicator-component-{{ $indicatorId }}-{{ $editing ? 'edit' : 'view' }}">
    @foreach ($rows as $groupIndex => $row)
        <tr wire:key="indicator-row-{{ $row['id'] }}-{{ $editing ? 'edit' : 'view' }}" class="odd:bg-background even:bg-muted/25 hover:bg-accent/45 transition-colors">
            @if ($groupIndex === 0)
                <td rowspan="{{ $rowSpan }}" class="border-b border-r border-border px-3 py-3 align-top text-center text-muted-foreground whitespace-normal break-words" style="{{ $cellStyle }}">
                    <div class="flex items-center justify-center gap-1">
                        @if ($editing)
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
                            </div>
                        @endif
                    </div>
                </td>
                <td rowspan="{{ $rowSpan }}" class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words" style="{{ $cellStyle }}">
                    @if ($editing)
                        <input type="hidden" wire:model="editCategory">
                        <textarea data-autosize="true" wire:model="editActivity" rows="1" class="{{ $textareaClass }} @error('editActivity') border-red-500 focus-visible:ring-red-500 @else border-input @enderror" style="resize:none;"></textarea>
                    @else
                        {!! nl2br(e($formatValue($firstRow['activity'] ?? null))) !!}
                    @endif
                </td>
            @endif

            <td class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words" style="{{ $cellStyle }}">
                @if ($editing)
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
                    @if ($editing)
                        <textarea data-autosize="true" wire:model="editRows.{{ $row['id'] }}.{{ $field }}" rows="1" class="{{ $textareaClass }} @error('editRows.'.$row['id'].'.'.$field) border-red-500 focus-visible:ring-red-500 @else border-input @enderror" style="resize:none;"></textarea>
                    @else
                        {!! nl2br(e($formatValue($row[$column] ?? null))) !!}
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach
</tbody>
