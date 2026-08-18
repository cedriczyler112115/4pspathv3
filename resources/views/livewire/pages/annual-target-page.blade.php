<section class="w-full space-y-6">
    @php
        $formatTableValue = function ($value): string {
            $text = (string) ($value ?? '-');
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace("/\r\n|\r|\n/", '<br>', $text);

            return $text ?? '-';
        };

        $formatScoreValue = function ($value): string {
            $text = html_entity_decode((string) ($value ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace("/\r\n|\r|\n/", '<br>', $text);

            return $text ?? '-';
        };
    @endphp

    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-1">
            <flux:heading size="lg" level="1">{{ __('Annual Target') }}</flux:heading>
            <flux:subheading size="sm">{{ __('Review annual target entries and manage profile information.') }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2 lg:justify-end">
            <flux:button type="button" icon="check" class="bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400">
                {{ __('Save Annual Target') }}
            </flux:button>
            <flux:button type="button" icon="plus" class="bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400">
                {{ __('Add Target') }}
            </flux:button>
            <flux:button type="button" icon="document-duplicate" class="bg-violet-600 text-white hover:bg-violet-700 dark:bg-violet-500 dark:hover:bg-violet-400">
                {{ __('Copy Target') }}
            </flux:button>
            <flux:button type="button" icon="printer" class="bg-slate-600 text-white hover:bg-slate-700 dark:bg-slate-500 dark:hover:bg-slate-400">
                {{ __('Print') }}
            </flux:button>
        </div>
    </div>

    <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full border-0 border-collapse">
                <tbody>
                    <tr class="align-top">
                        <td class="pr-8 whitespace-nowrap">
                            <div class="text-[11px] leading-none text-muted-foreground">{{ __('Full Name') }}</div>
                            <div class="mt-1 text-sm font-semibold leading-tight text-foreground uppercase">
                                {{ $fullName ?: '-' }}
                            </div>
                        </td>
                        <td class="pr-8 whitespace-nowrap">
                            <div class="text-[11px] leading-none text-muted-foreground">{{ __('Position') }}</div>
                            <div class="mt-1 text-sm font-semibold leading-tight text-foreground uppercase">
                                {{ $position ?: '-' }}
                            </div>
                        </td>
                        <td class="pr-8 whitespace-nowrap">
                            <div class="text-[11px] leading-none text-muted-foreground">{{ __('Designation') }}</div>
                            <div class="mt-1 text-sm font-semibold leading-tight text-foreground uppercase">
                                {{ $designation ?: '-' }}
                            </div>
                        </td>
                        <td class="pr-8 whitespace-nowrap">
                            <div class="text-[11px] leading-none text-muted-foreground">{{ __('Division Name') }}</div>
                            <div class="mt-1 text-sm font-semibold leading-tight text-foreground uppercase">
                                {{ $divisionName ?: '-' }}
                            </div>
                        </td>
                        <td class="whitespace-nowrap">
                            <div class="text-[11px] leading-none text-muted-foreground">{{ __('Section Name') }}</div>
                            <div class="mt-1 text-sm font-semibold leading-tight text-foreground uppercase">
                                {{ $sectionName ?: '-' }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
        <div class="mb-4 border-b border-border pb-4">
            <div class="overflow-x-auto">
                <table class="w-full border-0 border-collapse">
                    <tbody>
                        <tr class="align-top">
                            <td class="px-2 py-1 whitespace-nowrap">
                                <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')"
                                    :placeholder="__('Search annual targets')" />
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <flux:select wire:model.live="yearFilter" :label="__('Year')">
                                    <option value="">{{ __('All years') }}</option>
                                    @foreach ($years as $year)
                                        <option value="{{ $year->target_year }}">{{ $year->target_year }}</option>
                                    @endforeach
                                </flux:select>
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <flux:select wire:model.live="categoryFilter" :label="__('Category')">
                                    <option value="">{{ __('All categories') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->value }}">{{ $category->label }}</option>
                                    @endforeach
                                </flux:select>
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <flux:select wire:model.live="semesterFilter" :label="__('Semester')">
                                    <option value="">{{ __('All semesters') }}</option>
                                    @foreach ($semesters as $semester)
                                        <option value="{{ $semester->value }}">{{ $semester->label }}</option>
                                    @endforeach
                                </flux:select>
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <flux:select wire:model.live="perPage" :label="__('Records Per Page')" class="w-28">
                                    @foreach ($perPageOptions as $option)
                                        <option value="{{ $option->value }}">
                                            {{ $option->label }}
                                        </option>
                                    @endforeach
                                </flux:select>
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap align-bottom">
                                <flux:button variant="primary" type="button" wire:click="resetFilters" class="mt-6 bg-slate-600 text-white hover:bg-slate-700 dark:bg-slate-500 dark:text-white dark:hover:bg-slate-400">
                                    {{ __('Reset') }}
                                </flux:button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="w-full overflow-x-auto rounded-xl border border-border">
            <table class="w-full min-w-[1100px] table-fixed border-separate border-spacing-0 text-sm">
                <colgroup>
                    <col style="width: 6%; !important;">
                    <col style="width: 15%; !important;">
                    <col style="width: 7%; !important;">
                    <col style="width: 15%; !important;">
                    <col style="width: 15%; !important;">
                    <col style="width: 15%; !important;">
                    <col style="width: 15%; !important;">
                    <col style="width: 8%; !important;">
                    <col style="width: 8%; !important;">
                </colgroup>
                <thead
                    class="bg-muted/50 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th class="border-b border-r border-border px-3 py-3 text-center whitespace-nowrap first:rounded-tl-xl"
                            style="border-right: 1px solid var(--border);">
                            {{ __('Action') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                            style="border-right: 1px solid var(--border);">
                            {{ __('Key Result Area') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                            style="border-right: 1px solid var(--border);">
                            {{ __('Semester') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                            style="border-right: 1px solid var(--border);">
                            {{ __('Success Indicator') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                            style="border-right: 1px solid var(--border);">
                            {{ __('Efficiency') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                            style="border-right: 1px solid var(--border);">
                            {{ __('Quality') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                            style="border-right: 1px solid var(--border);">
                            {{ __('Timeliness') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                            style="border-right: 1px solid var(--border);">
                            {{ __('MOVs') }}
                        </th>
                        <th class="border-b border-l border-border px-3 py-3 whitespace-nowrap last:rounded-tr-xl"
                            style="border-left: 1px solid var(--border);">
                            {{ __('Remarks') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @php($groupedTargets = collect($annualTargets->items())->groupBy('kra_category'))
                    @forelse ($groupedTargets as $kraCategory => $categoryRows)
                    @php($groupedByIndicator = $categoryRows->groupBy('ind_id'))
                    <tr class="bg-muted/30">
                        <td colspan="9"
                            class="border-b border-border px-3 py-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ \App\Support\KraCategory::label($kraCategory) }}
                        </td>
                    </tr>
                    @foreach ($groupedByIndicator as $indId => $rows)
                    @php($groupRows = $rows->values())
                    @php($firstRow = $groupRows->first())
                    @php($rowSpan = $groupRows->count())
                    @foreach ($groupRows as $groupIndex => $row)
                        <tr wire:key="annual-target-{{ $indId }}-{{ $row->id }}"
                            class="odd:bg-background even:bg-muted/25 hover:bg-accent/45 transition-colors">
                            @if ($groupIndex === 0)
                                <td rowspan="{{ $rowSpan }}"
                                    class="border-b border-r border-border px-3 py-3 align-top text-center text-muted-foreground whitespace-normal break-words"
                                    style="vertical-align: top !important; border-right: 1px solid var(--border); {{ $editingIndicatorId === (int) $firstRow->ind_id ? 'background-color: #faf3de;' : '' }}">
                                    <div class="flex items-center justify-center gap-1">
                                            @if ($editingIndicatorId === (int) $firstRow->ind_id)
                                                <div class="flex w-full flex-col items-center gap-2">
                                                    <div class="flex flex-col items-center gap-1">
                                                        <flux:button size="xs" variant="ghost" type="button" wire:click="saveEdit" icon="check" style="width: 2.75rem; background-color: #22c55e; color: #fff;" aria-label="{{ __('Save') }}" />
                                                        <flux:button size="xs" variant="ghost" type="button" wire:click="cancelEdit" icon="x-mark" style="width: 2.75rem; background-color: #f59e0b; color: #fff;" aria-label="{{ __('Cancel') }}" />
                                                    </div>
                                                    <div class="h-px w-full bg-border"></div>
                                                </div>
                                        @elseif ((int) ($firstRow->target_status ?? 0) === 3)
                                            <flux:icon icon="lock-closed" class="size-3.5 text-muted-foreground" />
                                        @elseif ((int) ($firstRow->target_status ?? 0) === 1)
                                            <div class="flex flex-col items-start gap-1">
                                                    <flux:button size="xs" variant="primary" icon="pencil-square" class="h-7 justify-center px-2 text-xs" style="width: 2.75rem;" wire:click="editRow({{ $firstRow->id }})" aria-label="{{ __('Edit') }}" />
                                                    <flux:button size="xs" variant="danger" icon="trash" class="h-7 justify-center px-2 text-xs" style="width: 2.75rem;" wire:click="deleteRow({{ $firstRow->id }})" aria-label="{{ __('Delete') }}" />
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td rowspan="{{ $rowSpan }}"
                                    class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words"
                                    style="vertical-align: top !important; border-right: 1px solid var(--border); {{ $editingIndicatorId === (int) $firstRow->ind_id ? 'background-color: #faf3de;' : '' }}">
                                    @if ($editingIndicatorId === (int) $firstRow->ind_id)
                                        <div class="space-y-2">
                                            <flux:select wire:model.blur="editCategory">
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->value }}">{{ $category->label }}</option>
                                                @endforeach
                                            </flux:select>
                                            <textarea data-autosize="true" x-data
                                                x-init="const fit = () => { $el.style.height = 'auto'; $el.style.overflow = 'hidden'; $el.style.height = `${$el.scrollHeight}px`; }; fit(); $nextTick(fit); $el.addEventListener('input', fit);"
                                                wire:model.blur="editActivity" rows="1"
                                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                                                style="resize:none;"></textarea>
                                        </div>
                                    @else
                                        {!! $formatTableValue($firstRow->activity ?? null) !!}
                                    @endif
                                </td>
                            @endif

                            <td class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words"
                                style="vertical-align: top !important; border-right: 1px solid var(--border); {{ $editingIndicatorId === (int) $row->ind_id ? 'background-color: #faf3de;' : '' }}">
                                @if ($editingIndicatorId === (int) $row->ind_id)
                                    <flux:select wire:model.blur="editRows.{{ $row->id }}.semester">
                                        @foreach ($semesters as $semester)
                                            <option value="{{ $semester->value }}">{{ $semester->label }}</option>
                                        @endforeach
                                    </flux:select>
                                @else
                                    {!! $formatTableValue(\App\Support\Semester::label($row->new_semester ?? null)) !!}
                                @endif
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words"
                                style="vertical-align: top !important; border-right: 1px solid var(--border); {{ $editingIndicatorId === (int) $row->ind_id ? 'background-color: #faf3de;' : '' }}">
                                @if ($editingIndicatorId === (int) $row->ind_id)
                                    <textarea data-autosize="true" x-data
                                        x-init="const fit = () => { $el.style.height = 'auto'; $el.style.overflow = 'hidden'; $el.style.height = `${$el.scrollHeight}px`; }; fit(); $nextTick(fit); $el.addEventListener('input', fit);"
                                        wire:model.blur="editRows.{{ $row->id }}.description" rows="1"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                                        style="resize:none;"></textarea>
                                @else
                                    {!! $formatTableValue($row->description ?? null) !!}
                                @endif
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words"
                                style="vertical-align: top !important; border-right: 1px solid var(--border); {{ $editingIndicatorId === (int) $row->ind_id ? 'background-color: #faf3de;' : '' }}">
                                @if ($editingIndicatorId === (int) $row->ind_id)
                                    <textarea data-autosize="true" x-data
                                        x-init="const fit = () => { $el.style.height = 'auto'; $el.style.overflow = 'hidden'; $el.style.height = `${$el.scrollHeight}px`; }; fit(); $nextTick(fit); $el.addEventListener('input', fit);"
                                        wire:model.blur="editRows.{{ $row->id }}.efficiency" rows="1"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                                        style="resize:none;"></textarea>
                                @else
                                    {!! $formatScoreValue($row->rg_efficiency_ ?? null) !!}
                                @endif
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words"
                                style="vertical-align: top !important; border-right: 1px solid var(--border); {{ $editingIndicatorId === (int) $row->ind_id ? 'background-color: #faf3de;' : '' }}">
                                @if ($editingIndicatorId === (int) $row->ind_id)
                                    <textarea data-autosize="true" x-data
                                        x-init="const fit = () => { $el.style.height = 'auto'; $el.style.overflow = 'hidden'; $el.style.height = `${$el.scrollHeight}px`; }; fit(); $nextTick(fit); $el.addEventListener('input', fit);"
                                        wire:model.blur="editRows.{{ $row->id }}.quality" rows="1"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                                        style="resize:none;"></textarea>
                                @else
                                    {!! $formatScoreValue($row->rg_quality_ ?? null) !!}
                                @endif
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words"
                                style="vertical-align: top !important; border-right: 1px solid var(--border); {{ $editingIndicatorId === (int) $row->ind_id ? 'background-color: #faf3de;' : '' }}">
                                @if ($editingIndicatorId === (int) $row->ind_id)
                                    <textarea data-autosize="true" x-data
                                        x-init="const fit = () => { $el.style.height = 'auto'; $el.style.overflow = 'hidden'; $el.style.height = `${$el.scrollHeight}px`; }; fit(); $nextTick(fit); $el.addEventListener('input', fit);"
                                        wire:model.blur="editRows.{{ $row->id }}.timeliness" rows="1"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                                        style="resize:none;"></textarea>
                                @else
                                    {!! $formatScoreValue($row->rg_timeliness_ ?? null) !!}
                                @endif
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 align-top whitespace-normal break-words"
                                style="vertical-align: top !important; border-right: 1px solid hsl(var(--border)); {{ $editingIndicatorId === (int) $row->ind_id ? 'background-color: #faf3de;' : '' }}">
                                @if ($editingIndicatorId === (int) $row->ind_id)
                                    <textarea data-autosize="true" x-data
                                        x-init="const fit = () => { $el.style.height = 'auto'; $el.style.overflow = 'hidden'; $el.style.height = `${$el.scrollHeight}px`; }; fit(); $nextTick(fit); $el.addEventListener('input', fit);"
                                        wire:model.blur="editRows.{{ $row->id }}.movs" rows="1"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                                        style="resize:none;"></textarea>
                                @else
                                    {!! $formatTableValue($row->rg_mov_ ?? null) !!}
                                @endif
                            </td>
                            <td class="border-b border-l border-border px-3 py-3 align-top whitespace-normal break-words"
                                style="vertical-align: top !important; border-left: 1px solid var(--border); {{ $editingIndicatorId === (int) $row->ind_id ? 'background-color: #faf3de;' : '' }}">
                                @if ($editingIndicatorId === (int) $row->ind_id)
                                    <textarea data-autosize="true" x-data
                                        x-init="const fit = () => { $el.style.height = 'auto'; $el.style.overflow = 'hidden'; $el.style.height = `${$el.scrollHeight}px`; }; fit(); $nextTick(fit); $el.addEventListener('input', fit);"
                                        wire:model.blur="editRows.{{ $row->id }}.remarks" rows="1"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                                        style="resize:none;"></textarea>
                                @else
                                    {!! $formatTableValue($row->rg_remarks_ ?? null) !!}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @endforeach
                    @empty
                    <tr>
                        <td colspan="9" class="border-b border-border px-3 py-10 text-center text-muted-foreground">
                            {{ __('No annual target records found.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $annualTargets->links('vendor.pagination.users-pagination') }}
        </div>
    </div>

    <flux:modal wire:model="showDeleteModal" dismissible>
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Delete annual target') }}</flux:heading>
                <flux:subheading>
                    {{ __('This will permanently delete the selected annual target group and all of its related rows.') }}
                </flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:button variant="ghost" type="button" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="button" class="bg-red-600 text-white hover:bg-red-700"
                    wire:click="confirmDelete">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
