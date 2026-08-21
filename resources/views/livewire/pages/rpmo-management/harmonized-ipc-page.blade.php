<section class="w-full space-y-6">
    @php
        $formatTableValue = function ($value): string {
            $text = html_entity_decode((string) ($value ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
            $text = str_replace(["\r\n", "\r"], "\n", $text);

            return $text ?? '-';
        };

        $formatScoreValue = function ($value): string {
            $text = html_entity_decode((string) ($value ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
            $text = str_replace(["\r\n", "\r"], "\n", $text);

            return $text ?? '-';
        };
    @endphp

    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div class="space-y-1">
            <flux:heading size="lg" level="1">{{ __('Harmonized IPC') }}</flux:heading>
            <flux:subheading size="sm">{{ __('Review harmonized IPC entries and manage targets.') }}
            </flux:subheading>
        </div>

        <div class="flex items-end gap-3">
            <div class="min-w-[20rem] w-80 sm:w-[24rem] shrink-0 relative" style="min-width: 20rem; width: 24rem;">
                <flux:select wire:model.live="positionFilter" :label="__('Select Position')"
                    class="w-full min-w-[20rem] [&_select]:pr-10" style="min-width: 20rem; width: 100%;">
                    <option value="">{{ __('Select Position') }}</option>
                    @foreach ($positions as $pos)
                        <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                    @endforeach
                </flux:select>
                <div wire:loading wire:target="positionFilter"
                    class="absolute right-8 bottom-0 h-[38px] flex items-center justify-center pointer-events-none z-10 w-6 mb-[13px]"
                    style="margin-bottom: 13px;">
                    <svg class="animate-spin size-4 text-emerald-600 dark:text-emerald-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
        <div class="mb-4 border-b border-border pb-4">
            <div class="overflow-x-auto">
                <table class="w-full border-0 border-collapse">
                    <tbody>
                        <tr class="align-top">
                            <td class="px-2 py-1 whitespace-nowrap">
                                <div class="relative">
                                    <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')"
                                        :placeholder="__('Search harmonized targets')" class="[&_input]:pr-8" />
                                    <div wire:loading wire:target="search"
                                        class="absolute right-2.5 bottom-0 h-[38px] flex items-center justify-center pointer-events-none z-10 w-6 mb-[13px]"
                                        style="margin-bottom: 13px;">
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
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <div class="relative">
                                    <flux:select wire:model.live="yearFilter" :label="__('Year')"
                                        class="[&_select]:pr-10">
                                        <option value="">{{ __('All years') }}</option>
                                        @foreach ($years as $yearOption)
                                            <option value="{{ $yearOption->target_year }}">{{ $yearOption->target_year }}
                                            </option>
                                        @endforeach
                                    </flux:select>
                                    <div wire:loading wire:target="yearFilter"
                                        class="absolute right-8 bottom-0 h-[38px] flex items-center justify-center pointer-events-none z-10 w-6 mb-[13px]"
                                        style="margin-bottom: 13px;">
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
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <div class="relative">
                                    <flux:select wire:model.live="categoryFilter" :label="__('Category')"
                                        class="[&_select]:pr-10">
                                        <option value="">{{ __('All categories') }}</option>
                                        @foreach ($categories as $categoryOption)
                                            <option value="{{ $categoryOption->value }}">{{ $categoryOption->label }}
                                            </option>
                                        @endforeach
                                    </flux:select>
                                    <div wire:loading wire:target="categoryFilter"
                                        class="absolute right-8 bottom-0 h-[38px] flex items-center justify-center pointer-events-none z-10 w-6 mb-[13px]"
                                        style="margin-bottom: 13px;">
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
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <div class="relative">
                                    <flux:select wire:model.live="semesterFilter" :label="__('Semester')"
                                        class="[&_select]:pr-10">
                                        <option value="">{{ __('All semesters') }}</option>
                                        @foreach ($semesters as $semesterOption)
                                            <option value="{{ $semesterOption->value }}">{{ $semesterOption->label }}
                                            </option>
                                        @endforeach
                                    </flux:select>
                                    <div wire:loading wire:target="semesterFilter"
                                        class="absolute right-8 bottom-0 h-[38px] flex items-center justify-center pointer-events-none z-10 w-6 mb-[13px]"
                                        style="margin-bottom: 13px;">
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
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <div class="relative">
                                    <flux:select wire:model.live="perPage" :label="__('Records Per Page')"
                                        class="w-28 [&_select]:pr-10">
                                        @foreach ($perPageOptions as $option)
                                            <option value="{{ $option->value }}">
                                                {{ $option->label }}
                                            </option>
                                        @endforeach
                                    </flux:select>
                                    <div wire:loading wire:target="perPage"
                                        class="absolute right-8 bottom-0 h-[38px] flex items-center justify-center pointer-events-none z-10 w-6 mb-[13px]"
                                        style="margin-bottom: 13px;">
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
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap align-bottom">
                                <flux:button type="button" variant="ghost" icon="arrow-path" wire:click="resetFilters"
                                    class="mb-0.5">
                                    {{ __('Reset Filters') }}
                                </flux:button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full border-separate border-spacing-0 text-sm">
                <thead
                    class="bg-muted/50 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th class="border-b border-r border-border px-3 py-3 text-center first:rounded-tl-xl"
                            style="width: 70px;">
                            {{ __('Action') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap" style="width: 250px;">
                            {{ __('Activity / Indicator') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap" style="width: 140px;">
                            {{ __('Semester') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                            style="min-width: 220px;">
                            {{ __('Target / Measure') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap" style="width: 110px;">
                            {{ __('Efficiency') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap" style="width: 110px;">
                            {{ __('Quality') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap" style="width: 110px;">
                            {{ __('Timeliness') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap" style="width: 160px;">
                            {{ __('MOVs') }}
                        </th>
                        <th class="border-b border-border px-3 py-3 whitespace-nowrap last:rounded-tr-xl"
                            style="width: 180px;">
                            {{ __('Remarks') }}
                        </th>
                    </tr>
                </thead>

                @foreach ($visibleCategories as $category)
                @php
                    $categoryRows = collect($annualTargets->items())->where('kra_category', $category->value);
                    $groupedByIndicator = $categoryRows->groupBy('ind_id');
                @endphp

                <tbody wire:key="harmonized-ipc-category-heading-{{ $category->value }}" x-data="{
                            dropOnCategory(event) {
                                const raw = event.dataTransfer.getData('application/json');
                                if (raw) $dispatch('harmonized-ipc-target-dropped', { source: JSON.parse(raw), target: { type: 'category', kra: {{ (int) $category->value }}, indicatorId: 0, itemId: 0 } });
                            }
                        }" x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                    x-on:drop.prevent="dropOnCategory($event)">
                    <tr wire:key="harmonized-ipc-category-{{ $category->value }}" class="bg-muted/30">
                        <td colspan="9" class="border-b border-border px-3 py-2.5">
                            <div class="font-bold text-foreground">
                                <span>{{ $category->label }}</span>
                            </div>
                        </td>
                    </tr>
                </tbody>

                @forelse ($groupedByIndicator as $indId => $rows)
                @php($groupRows = $rows->values())
                <livewire:harmonized-ipc.indicator-rows :indicator-id="(int) $indId" :rows="$groupRows->map(fn($row) => (array) $row)->all()" :position-filter="$positionFilter"
                    :key="'harmonized-ipc-indicator-' . $indId . '-' . $positionFilter . '-' . ($groupRows->first()->target_status ?? 1) . '-' . $groupRows->pluck('id')->join('-')" />
                @empty
                <tbody wire:key="harmonized-ipc-empty-{{ $category->value }}">
                    <tr>
                        <td colspan="9" class="border-b border-border px-3 py-10 text-center text-muted-foreground">
                            {{ __('No record found in this category.') }}
                        </td>
                    </tr>
                </tbody>
                @endforelse

                <tbody wire:key="harmonized-ipc-category-tail-{{ $category->value }}">
                </tbody>
                @endforeach
            </table>
        </div>

        {{ $annualTargets->links('vendor.pagination.users-pagination') }}
    </div>

    <flux:modal wire:model="showAddModal"
        style="width: min(72rem, calc(100vw - 2rem)); max-width: min(72rem, calc(100vw - 2rem));">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Add target') }}</flux:heading>
                <flux:subheading>
                    {{ __('Create a new target entry inside the selected KRA category.') }}
                </flux:subheading>
            </div>

            <table class="w-full table-fixed border-collapse" style="border: 0;">
                <tbody>
                    <tr>
                        <td class="w-1/3 align-top pe-3" style="border: 0;">
                            <div class="grid gap-1">
                                <flux:label>{{ __('KRA Category') }}</flux:label>
                                <div>
                                    <flux:badge color="blue">
                                        {{ \App\Support\KraCategory::label($addingKraCategory ?? 1) }}
                                    </flux:badge>
                                </div>
                            </div>
                        </td>
                        <td class="w-1/3 align-top px-3" style="border: 0;">
                            <div class="grid gap-1">
                                <flux:label>{{ __('Year') }}</flux:label>
                                <div>
                                    <flux:badge color="zinc">
                                        {{ $addingYear ?? now()->year }}
                                    </flux:badge>
                                </div>
                            </div>
                        </td>
                        <td class="w-1/3 align-top ps-3" style="border: 0;">
                            <div class="grid gap-1">
                                <flux:label>{{ __('Semester') }}</flux:label>
                                <flux:select wire:model="addSemester">
                                    <option value="">{{ __('Select') }}</option>
                                    @foreach ($semesters as $semester)
                                        <option value="{{ $semester->value }}">{{ $semester->label }}</option>
                                    @endforeach
                                </flux:select>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="grid items-start gap-4 md:grid-cols-2">
                <div class="grid gap-1">
                    <flux:label>{{ __('Key Result Area') }}</flux:label>
                    <textarea data-autosize="true" wire:model="addActivity" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                </div>

                <div class="grid gap-1">
                    <flux:label>{{ __('Success Indicator') }}</flux:label>
                    <textarea data-autosize="true" wire:model="addDescription" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                </div>
            </div>

            <div class="flex items-center gap-3" role="separator" aria-label="{{ __('Rating Guide') }}">
                <div class="h-px flex-1 bg-border"></div>
                <span class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ __('Rating Guide') }}
                </span>
                <div class="h-px flex-1 bg-border"></div>
            </div>

            <div class="grid items-start gap-4 md:grid-cols-2">
                <div class="grid gap-1">
                    <flux:label>{{ __('Efficiency') }}</flux:label>
                    <textarea data-autosize="true" wire:model="addEfficiency" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                </div>

                <div class="grid gap-1">
                    <flux:label>{{ __('Quality') }}</flux:label>
                    <textarea data-autosize="true" wire:model="addQuality" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                </div>

                <div class="grid gap-1">
                    <flux:label>{{ __('Timeliness') }}</flux:label>
                    <textarea data-autosize="true" wire:model="addTimeliness" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                </div>

                <div class="grid gap-1">
                    <flux:label>{{ __('MOVs') }}</flux:label>
                    <textarea data-autosize="true" wire:model="addMovs" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                </div>

                <div class="grid gap-1 md:col-span-2" style="grid-column: 1 / -1;">
                    <flux:label>{{ __('Remarks') }}</flux:label>
                    <textarea data-autosize="true" wire:model="addRemarks" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:button variant="ghost" type="button" wire:click="cancelAdd">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="button" class="bg-emerald-600 text-white hover:bg-emerald-700"
                    wire:click="saveAdd">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showDeleteModal" dismissible class="max-w-lg">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Delete selected target and its sub-target') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to delete this target and all of its sub-targets? This action cannot be undone.') }}
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

    <flux:modal wire:model="showDeleteSubTargetModal" dismissible class="max-w-lg">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Delete selected sub-target') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to delete this sub-target? This action cannot be undone.') }}
                </flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:button variant="ghost" type="button" wire:click="cancelDeleteSubTarget">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="button" class="bg-red-600 text-white hover:bg-red-700"
                    wire:click="confirmDeleteSubTarget">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showMoveConfirmModal" dismissible class="max-w-lg">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Move Target to Different Category?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to move this target to another category?') }}
                </flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:button variant="ghost" type="button" wire:click="cancelTargetMove">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="button" wire:click="confirmTargetMove">
                    {{ __('Confirm Move') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>