<section class="w-full space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-1">
            <flux:heading size="lg" level="1">{{ $semesterHeading }}</flux:heading>
            <flux:subheading size="sm">{{ __('Review semestral target entries and performance indicators.') }}
            </flux:subheading>
        </div>
        <div>
            <flux:button href="{{ route('myratings.index') }}" wire:navigate size="sm" icon="arrow-left"
                variant="subtle">
                {{ __('Back to My Ratings') }}
            </flux:button>
        </div>
    </div>

    @if ($unauthorizedErrorMessage)
        <div class="rounded-2xl border border-red-300 bg-gradient-to-r from-red-500/10 via-rose-500/10 to-red-500/10 p-5 text-red-900 dark:border-red-800/80 dark:from-red-950/70 dark:via-rose-950/70 dark:to-red-950/70 dark:text-red-200 shadow-md flex items-start gap-4">
            <div class="flex size-10 items-center justify-center rounded-xl bg-red-600 text-white shadow-sm shrink-0">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="flex-1 space-y-1.5">
                <h4 class="font-bold text-base text-red-700 dark:text-red-300">{{ __('Access Denied') }}</h4>
                <p class="text-xs leading-relaxed text-muted-foreground dark:text-red-300/90 font-medium">{{ $unauthorizedErrorMessage }}</p>
                <div class="pt-2">
                    <flux:button href="{{ route('myratings.index') }}" wire:navigate size="sm" icon="arrow-left" class="bg-red-600 text-white hover:bg-red-700 dark:bg-red-600 dark:text-white dark:hover:bg-red-700 shadow-sm font-semibold">
                        {{ __('Return to My Ratings') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <!-- Container for Login / User Profile Info -->
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

    <!-- Filters and Table Container -->
    <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
        <div class="mb-4 border-b border-border pb-4 relative z-30">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between relative z-30">
                <div class="overflow-x-visible">
                    <table class="border-0 border-collapse">
                        <tbody>
                            <tr class="align-top">
                                <td class="px-2 py-1 whitespace-nowrap">
                                    <div class="relative">
                                        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')"
                                            :placeholder="__('Search semestral targets...')" class="[&_input]:pr-8" />
                                        <div wire:loading wire:target="search"
                                            class="absolute right-2.5 bottom-[9px] flex items-center justify-center pointer-events-none z-10 bg-card dark:bg-card">
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
                                    <x-select2 wire:model.live="categoryFilter" :label="__('Category')"
                                        :placeholder="__('All categories')" :options="$categories" minWidth="160px" />
                                </td>
                                <td class="px-2 py-1 whitespace-nowrap">
                                    <x-select2 wire:model.live="perPage" :label="__('Records Per Page')"
                                        :placeholder="__('Select')" :options="$this->perPageOptions()" minWidth="120px"
                                        :searchable="false" />
                                </td>
                                <td class="px-1 py-1 whitespace-nowrap align-bottom">
                                    <div class="flex h-full items-end -ml-1">
                                        <flux:button variant="primary" type="button" wire:click="resetFilters"
                                            class="bg-slate-600 text-white hover:bg-slate-700 dark:bg-slate-500 dark:text-white dark:hover:bg-slate-400">
                                            {{ __('Reset') }}
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-2 py-1 whitespace-nowrap align-bottom flex items-end gap-2">
                    <flux:button type="button" icon="document-duplicate" wire:click="openCopyModal"
                        class="bg-violet-600 text-white hover:bg-violet-700 dark:bg-violet-500 dark:hover:bg-violet-400">
                        {{ __('Copy Target from Previous Semester') }}
                    </flux:button>

                    <flux:dropdown position="bottom-end">
                        <flux:button variant="primary" icon="printer" icon-trailing="chevron-down"
                            class="bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:text-white dark:hover:bg-emerald-700">
                            {{ __('Print') }}
                        </flux:button>

                        <flux:menu>
                            <flux:menu.item icon="document-text" wire:click="printIpcrf">
                                {{ __('Print IPCR-F') }}
                            </flux:menu.item>
                            <flux:menu.item icon="clipboard-document-check" wire:click="printCheckpoint">
                                {{ __('Print Checkpoint') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>

        <div class="w-full overflow-x-auto rounded-xl border border-border">
            <table class="w-full min-w-[1100px] table-fixed border-separate border-spacing-0 text-sm">
                <colgroup>
                    <col style="width: 6%; !important;">
                    <col style="width: 18%; !important;">
                    <col style="width: 18%; !important;">
                    <col style="width: 15%; !important;">
                    <col style="width: 15%; !important;">
                    <col style="width: 14%; !important;">
                    <col style="width: 7%; !important;">
                    <col style="width: 7%; !important;">
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

                @php
                    $isAllCategories = empty($categoryFilter);
                    $isNotAllPerPage = (int) $perPage !== -1;
                @endphp

                @foreach ($visibleCategories as $category)
                    @php
                        $categoryRows = collect($semestralTargets->items())->filter(fn($row) => (int) ($row->kra_category ?? 0) === (int) $category->value);
                        $groupedByIndicator = $categoryRows->groupBy(fn($row) => (int) ($row->sem_target_id ?? 0));
                        $hideIfEmptySlice = $isAllCategories && $isNotAllPerPage && $groupedByIndicator->isEmpty();
                    @endphp

                    @if (!$hideIfEmptySlice)
                        <tbody wire:key="semestral-target-category-heading-{{ $category->value }}"
                            x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                            x-on:drop.prevent="dropOn($event, { type: 'category', kra: {{ (int) $category->value }}, indicatorId: 0, itemId: 0 })">
                            <tr class="bg-muted/30">
                                <td colspan="8" class="border-b border-border px-3 py-2">
                                    <div class="flex items-center justify-between font-bold text-foreground">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ $category->label }}</span>
                                        <flux:button size="xs" variant="ghost" icon="plus" wire:click="openAddTargetModal({{ (int) $category->value }})">
                                            {{ __('Add Target') }}
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                        @forelse ($groupedByIndicator as $indId => $rows)
                            @php
                                $groupRows = $rows->values();
                            @endphp
                            <livewire:semestral-target.indicator-rows :indicator-id="(int) $indId" :rows="$groupRows->map(fn($row) => (array) $row)->all()" :key="'semestral-target-indicator-' . $indId . '-' . $groupRows->pluck('sem_item_id')->join('-')" />
                        @empty
                            @if (!($isAllCategories && $isNotAllPerPage))
                                <tbody wire:key="semestral-target-empty-{{ $category->value }}">
                                    <tr>
                                        <td colspan="8" class="border-b border-border px-3 py-6 text-center text-muted-foreground">
                                            {{ __('No semestral target entries under :category', ['category' => $category->label]) }}
                                        </td>
                                    </tr>
                                </tbody>
                            @endif
                        @endforelse
                    @endif
                @endforeach

                @if ($semestralTargets->total() === 0)
                    <tbody wire:key="semestral-target-empty-total">
                        <tr>
                            <td colspan="8" class="border-b border-border px-3 py-6 text-center text-muted-foreground">
                                {{ __('No semestral target entries found.') }}
                            </td>
                        </tr>
                    </tbody>
                @endif
            </table>
        </div>

        <div class="mt-4">
            {{ $semestralTargets->links() }}
        </div>
    </div>

    <!-- Add Target Modal -->
    <flux:modal wire:model="showAddModal"
        style="width: min(72rem, calc(100vw - 2rem)); max-width: min(72rem, calc(100vw - 2rem));">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Add Target') }}</flux:heading>
                <flux:subheading>
                    {{ __('Create a new semestral target entry inside the selected KRA category.') }}
                </flux:subheading>
            </div>

            <table class="w-full table-fixed border-collapse" style="border: 0;">
                <tbody>
                    <tr>
                        <td class="w-1/2 align-top pe-3" style="border: 0;">
                            <div class="grid gap-1">
                                <flux:label>{{ __('KRA Category') }}</flux:label>
                                <div>
                                    <flux:badge color="blue">
                                        {{ \App\Support\KraCategory::label($addingKraCategory ?? 1) }}
                                    </flux:badge>
                                </div>
                            </div>
                        </td>
                        <td class="w-1/2 align-top ps-3" style="border: 0;">
                            <div class="grid gap-1">
                                <flux:label>{{ __('Semester Period') }}</flux:label>
                                <div>
                                    <flux:badge color="zinc">
                                        {{ $this->semesterHeading() }}
                                    </flux:badge>
                                </div>
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
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="button" class="bg-emerald-600 text-white hover:bg-emerald-700"
                    wire:click="saveAdd">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Delete Target Modal -->
    <flux:modal wire:model="showDeleteModal" dismissible>
        <div class="space-y-4">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Delete Target Entry') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to delete this semestral target and all its sub-targets? This action cannot be undone.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="confirmDeleteTarget">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Delete Sub-Target Modal -->
    <flux:modal wire:model="showDeleteSubTargetModal" dismissible>
        <div class="space-y-4">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Delete Sub-Target Entry') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to delete this sub-target item? This action cannot be undone.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="confirmDeleteSubTarget">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Copy Target Modal -->
    <flux:modal wire:model="showCopyModal" dismissible style="width: 80%; max-width: 80%; height: 90%; max-height: 90%;"
        class="overflow-y-auto">
        @include('livewire.semestral-target.copy-target-modal')
    </flux:modal>

    <!-- Confirm Copy All Modal -->
    <flux:modal wire:model="showCopyAllConfirmModal" dismissible>
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Copy all filtered targets?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to copy all filtered target results to your semestral target list?') }}
                </flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="button" class="bg-emerald-600 text-white hover:bg-emerald-700"
                    wire:click="confirmCopyAll">
                    {{ __('Confirm and Copy') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>