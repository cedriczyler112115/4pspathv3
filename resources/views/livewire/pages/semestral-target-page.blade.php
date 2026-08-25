@push('header_actions')
    @if ($semId)
        <div class="flex items-center gap-2 text-xs">
            <div class="flex items-center gap-2 rounded-lg border border-border bg-muted/40 px-2.5 py-1">
                <span class="text-muted-foreground font-medium">{{ __('Final Rating:') }}</span>
                <span class="font-bold text-foreground">{{ $finalRating ?: '0.00' }}</span>
                <span class="text-muted-foreground font-medium ms-1">{{ __('Adjectival:') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $adjectivalRating ?: 'N/A' }}</span>
            </div>
        </div>
    @endif
@endpush

<section class="w-full space-y-6" x-data="{
        saveAllScores() {
            let payload = [];
            document.querySelectorAll('[data-score-row]').forEach(row => {
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
        }
    }" x-on:save-all-scores.window="saveAllScores()"
    x-on:open-new-tab.window="window.open($event.detail.url, '_blank')">
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
        <div
            class="rounded-2xl border border-red-300 bg-gradient-to-r from-red-500/10 via-rose-500/10 to-red-500/10 p-5 text-red-900 dark:border-red-800/80 dark:from-red-950/70 dark:via-rose-950/70 dark:to-red-950/70 dark:text-red-200 shadow-md flex items-start gap-4">
            <div class="flex size-10 items-center justify-center rounded-xl bg-red-600 text-white shadow-sm shrink-0">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="flex-1 space-y-1.5">
                <h4 class="font-bold text-base text-red-700 dark:text-red-300">{{ __('Access Denied') }}</h4>
                <p class="text-xs leading-relaxed text-muted-foreground dark:text-red-300/90 font-medium">
                    {{ $unauthorizedErrorMessage }}
                </p>
                <div class="pt-2">
                    <flux:button href="{{ route('myratings.index') }}" wire:navigate size="sm" icon="arrow-left"
                        class="bg-red-600 text-white hover:bg-red-700 dark:bg-red-600 dark:text-white dark:hover:bg-red-700 shadow-sm font-semibold">
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
                                <td class="px-2 py-1 whitespace-nowrap align-bottom">
                                    <div class="flex items-center gap-2 pb-2">
                                        <flux:checkbox wire:model.live="hasCheckpointTarget"
                                            :label="__('Has Checkpoint Target')"
                                            class="cursor-pointer font-medium text-xs text-foreground" />
                                        <div wire:loading wire:target="hasCheckpointTarget"
                                            class="flex items-center justify-center">
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
                    @if ($this->isSemestralTargetLocked())
                        <flux:button variant="primary" type="button" icon="check-circle" wire:click="imReady"
                            class="bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:text-white dark:hover:bg-emerald-700 font-semibold cursor-pointer">
                            {{ __("I'm Ready") }}
                        </flux:button>
                    @else
                        <flux:button variant="primary" type="button" icon="lock-closed" wire:click="openLockConfirmModal"
                            class="bg-amber-600 text-white hover:bg-amber-700 dark:bg-amber-600 dark:text-white dark:hover:bg-amber-700 font-semibold">
                            {{ __('Save and Lock Semestral Target') }}
                        </flux:button>
                    @endif

                    <flux:dropdown position="bottom-end">
                        <flux:button variant="primary" icon="adjustments-horizontal" icon-trailing="chevron-down"
                            class="bg-violet-600 text-white hover:bg-violet-700 dark:bg-violet-600 dark:text-white dark:hover:bg-violet-700">
                            {{ __('Options') }}
                        </flux:button>

                        <flux:menu>
                            <flux:menu.item icon="document-duplicate" wire:click="openCopyModal">
                                {{ __('Copy Target from Previous Semester') }}
                            </flux:menu.item>
                            @if ($this->isSemestralTargetLocked())
                                <flux:menu.separator />
                                <flux:menu.item icon="lock-open" wire:click="openUnlockConfirmModal">
                                    {{ __('Unlock Semestral Target') }}
                                </flux:menu.item>
                            @endif
                            <flux:menu.separator />
                            <flux:menu.item icon="arrow-path" wire:click="openRecoverModal">
                                {{ __('Recover Deleted Targets') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>

                    <flux:dropdown position="bottom-end">
                        <flux:button variant="primary" icon="printer" icon-trailing="chevron-down"
                            class="bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:text-white dark:hover:bg-emerald-700">
                            {{ __('Print') }}
                        </flux:button>

                        <flux:menu>
                            <flux:menu.item icon="document-text" wire:click="printIpcrf">
                                {{ __('Print IPCR-F') }}
                            </flux:menu.item>
                            @if ($semId)
                                <flux:menu.item icon="clipboard-document-check" as="a"
                                    href="{{ route('myratings.semestral-target.print-checkpoint', ['sem_id' => $semId]) }}"
                                    target="_blank">
                                    {{ __('Print Checkpoint') }}
                                </flux:menu.item>
                            @else
                                <flux:menu.item icon="clipboard-document-check" wire:click="printCheckpoint">
                                    {{ __('Print Checkpoint') }}
                                </flux:menu.item>
                            @endif
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>

        @php
            $isSemesterLocked = $this->isSemestralTargetLocked();
        @endphp

        <div class="w-full rounded-xl border border-border">
            <table class="w-full table-fixed border-separate border-spacing-0 text-sm">
                @if ($isSemesterLocked)
                    <colgroup>
                        <col style="width: 4%;">
                        <col style="width: 14%;">
                        <col style="width: 14%;">
                        <col style="width: 14%;">
                        <col style="width: 10%;">
                        <col style="width: 10%;">
                        <col style="width: 10%;">
                        <col style="width: 5%;">
                        <col style="width: 10%;">
                        <col style="width: 9%;">
                    </colgroup>
                @else
                    <colgroup>
                        <col style="width: 5%;">
                        <col style="width: 17%;">
                        <col style="width: 17%;">
                        <col style="width: 15%;">
                        <col style="width: 15%;">
                        <col style="width: 14%;">
                        <col style="width: 8.5%;">
                        <col style="width: 8.5%;">
                    </colgroup>
                @endif
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
                        @if ($isSemesterLocked)
                            <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap"
                                style="border-right: 1px solid var(--border);">
                                {{ __('Actual Accomplishment') }}
                            </th>
                        @endif
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
                        @if ($isSemesterLocked)
                            <th class="border-b border-r border-border px-2 py-3 text-center whitespace-nowrap"
                                style="border-right: 1px solid var(--border);">
                                {{ __('AVE') }}
                            </th>
                        @endif
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
                                <td colspan="{{ $isSemesterLocked ? 10 : 8 }}" class="border-b border-border px-3 py-2">
                                    <div class="font-bold text-foreground">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ $category->label }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                        @forelse ($groupedByIndicator as $indId => $rows)
                            @php
                                $groupRows = $rows->values();
                            @endphp
                            <livewire:semestral-target.indicator-rows :indicator-id="(int) $indId" :rows="$groupRows->map(fn($row) => (array) $row)->all()" :is-semester-locked="$isSemesterLocked" :key="'semestral-target-indicator-' . $indId . '-' . md5(json_encode($groupRows->all()))" />
                        @empty
                            @if (!($isAllCategories && $isNotAllPerPage))
                                <tbody wire:key="semestral-target-empty-{{ $category->value }}">
                                    <tr>
                                        <td colspan="{{ $isSemesterLocked ? 10 : 8 }}"
                                            class="border-b border-border px-3 py-6 text-center text-muted-foreground">
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
                    <flux:label>{{ __('Key Result Area') }} <span class="text-red-500">*</span></flux:label>
                    <textarea data-autosize="true" wire:model="addActivity" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                    <flux:error name="addActivity" />
                </div>

                <div class="grid gap-1">
                    <flux:label>{{ __('Success Indicator') }} <span class="text-red-500">*</span></flux:label>
                    <textarea data-autosize="true" wire:model="addDescription" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                    <flux:error name="addDescription" />
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
                    <flux:label>{{ __('Efficiency') }} <span class="text-red-500">*</span></flux:label>
                    <textarea data-autosize="true" wire:model="addEfficiency" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                    <flux:error name="addEfficiency" />
                </div>

                <div class="grid gap-1">
                    <flux:label>{{ __('Quality') }} <span class="text-red-500">*</span></flux:label>
                    <textarea data-autosize="true" wire:model="addQuality" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                    <flux:error name="addQuality" />
                </div>

                <div class="grid gap-1">
                    <flux:label>{{ __('Timeliness') }} <span class="text-red-500">*</span></flux:label>
                    <textarea data-autosize="true" wire:model="addTimeliness" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                    <flux:error name="addTimeliness" />
                </div>

                <div class="grid gap-1">
                    <flux:label>{{ __('MOVs') }} <span class="text-red-500">*</span></flux:label>
                    <textarea data-autosize="true" wire:model="addMovs" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                    <flux:error name="addMovs" />
                </div>

                <div class="grid gap-1 md:col-span-2" style="grid-column: 1 / -1;">
                    <flux:label>{{ __('Remarks') }} <span
                            class="text-xs text-muted-foreground">({{ __('Optional') }})</span></flux:label>
                    <textarea data-autosize="true" wire:model="addRemarks" rows="1"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        style="resize:none;"></textarea>
                    <flux:error name="addRemarks" />
                </div>

                <div class="grid gap-1 md:col-span-2" style="grid-column: 1 / -1;">
                    <flux:label>{{ __('Justification') }} @if($this->is2026SecondSemesterOrBeyond()) <span
                    class="text-red-500">*</span> @else <span
                            class="text-xs text-muted-foreground">({{ __('Optional') }})</span> @endif</flux:label>
                    <textarea data-autosize="true" wire:model="addJustification" rows="2"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                        placeholder="{{ __('Enter justification for adding this target...') }}"
                        style="resize:none;"></textarea>
                    <flux:error name="addJustification" />
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
                    {{ __('Are you sure you want to delete this semestral target entry? It can be recovered later using the Recover Deleted Targets menu.') }}
                </flux:subheading>
            </div>

            <div class="grid gap-1">
                <flux:label>{{ __('Justification') }} @if($this->is2026SecondSemesterOrBeyond()) <span
                class="text-red-500">*</span> @else <span
                        class="text-xs text-muted-foreground">({{ __('Optional') }})</span> @endif</flux:label>
                <textarea data-autosize="true" wire:model="deleteJustification" rows="2"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                    placeholder="{{ __('Enter justification for deleting this target...') }}"
                    style="resize:none;"></textarea>
                <flux:error name="deleteJustification" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" type="button" wire:click="cancelDeleteTarget">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="confirmDeleteTarget">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Recover Deleted Targets Modal -->
    <flux:modal wire:model="showRecoverModal"
        style="width: min(66rem, calc(100vw - 2rem)); max-width: min(66rem, calc(100vw - 2rem));">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Recover Deleted Targets') }}</flux:heading>
                <flux:subheading>
                    {{ __('Review and restore targets that were previously deleted from your semestral target list.') }}
                </flux:subheading>
            </div>

            <div class="max-h-[60vh] overflow-y-auto rounded-xl border border-border">
                <table class="w-full border-collapse text-xs">
                    <thead
                        class="sticky top-0 bg-muted/90 backdrop-blur-md text-left font-semibold uppercase text-muted-foreground border-b border-border">
                        <tr>
                            <th class="border-r border-border px-3 py-2.5">{{ __('KRA Category') }}</th>
                            <th class="border-r border-border px-3 py-2.5">{{ __('Key Result Area / Activity') }}</th>
                            <th class="border-r border-border px-3 py-2.5">{{ __('Deleted Date & User') }}</th>
                            <th class="border-r border-border px-3 py-2.5">{{ __('Justification') }}</th>
                            <th class="px-3 py-2.5 text-center">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($deletedTargetsList as $item)
                            <tr class="border-b border-border/60 hover:bg-muted/20">
                                <td
                                    class="border-r border-border px-3 py-2 align-top font-semibold text-slate-800 dark:text-zinc-200">
                                    {{ $item['kra_category_label'] }}
                                </td>
                                <td class="border-r border-border px-3 py-2 align-top">
                                    <div class="font-bold text-slate-900 dark:text-zinc-100">{{ $item['activity'] }}</div>
                                    @if (!empty($item['description']))
                                        <div class="text-[11px] text-muted-foreground mt-1">
                                            {!! nl2br(e($item['description'])) !!}
                                        </div>
                                    @endif
                                </td>
                                <td
                                    class="border-r border-border px-3 py-2 align-top text-muted-foreground whitespace-nowrap">
                                    <div>{{ $item['deleted_at'] }}</div>
                                    <div class="text-[10px] font-semibold text-slate-500">{{ $item['user_name'] }}</div>
                                </td>
                                <td class="border-r border-border px-3 py-2 align-top italic text-foreground">
                                    {!! nl2br(e($item['justification'])) !!}
                                </td>
                                <td class="px-3 py-2 align-top text-center">
                                    <flux:button size="xs" variant="primary"
                                        class="bg-emerald-600 text-white hover:bg-emerald-700 font-semibold"
                                        wire:click="recoverTarget({{ $item['sem_target_id'] }})">
                                        {{ __('Restore') }}
                                    </flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-muted-foreground">
                                    {{ __('No deleted targets found for recovery.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('Close') }}
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <!-- Delete Sub-Target Modal -->
    <flux:modal wire:model="showDeleteSubTargetModal" dismissible>
        <div class="space-y-4">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Delete Sub-Target Entry') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to delete this sub-target item? It can be recovered later using the Recover Deleted Targets menu.') }}
                </flux:subheading>
            </div>

            <div class="grid gap-1">
                <flux:label>{{ __('Justification') }} @if($this->is2026SecondSemesterOrBeyond()) <span
                class="text-red-500">*</span> @else <span
                        class="text-xs text-muted-foreground">({{ __('Optional') }})</span> @endif</flux:label>
                <textarea data-autosize="true" wire:model="deleteJustification" rows="2"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm leading-5 text-foreground shadow-sm"
                    placeholder="{{ __('Enter justification for deleting this sub-target...') }}"
                    style="resize:none;"></textarea>
                <flux:error name="deleteJustification" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" type="button" wire:click="cancelDeleteSubTarget">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="confirmDeleteSubTarget">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Reordering / Sorting Global Loading Overlay -->
    <div wire:loading wire:target="targetDropped"
        class="fixed inset-0 z-50 flex items-center justify-center bg-background/50 backdrop-blur-xs">
        <div class="flex items-center gap-3 rounded-2xl bg-card px-6 py-4 shadow-xl border border-border">
            <svg class="animate-spin size-6 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span class="text-sm font-bold text-foreground">{{ __('Updating target positions...') }}</span>
        </div>
    </div>

    <!-- Centered 20% Width Loading Overlay Modal for Batch Save -->
    <div wire:loading.flex wire:target="batchSaveScores"
        class="fixed inset-0 z-[9999999] items-center justify-center bg-black/40 backdrop-blur-xs"
        style="position: fixed !important; inset: 0 !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; z-index: 9999999 !important; background-color: rgba(0, 0, 0, 0.4) !important; backdrop-filter: blur(4px) !important;">
        <div class="flex flex-col items-center justify-center gap-3 rounded-2xl bg-card p-6 shadow-2xl border border-border text-center"
            style="width: 20% !important; min-width: 240px !important; max-width: 320px !important; margin: auto !important;">
            <svg class="animate-spin size-8 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span class="text-sm font-bold text-foreground leading-tight">{{ __('Saving...') }}</span>
        </div>
    </div>

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

    <!-- Show Edit History Modal -->
    <flux:modal wire:model="showHistoryModal"
        style="width: min(66rem, calc(100vw - 2rem)); max-width: min(66rem, calc(100vw - 2rem));">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Edit History') }}</flux:heading>
                <flux:subheading>
                    {{ __('Review all modifications, edits, and justifications recorded for the selected target.') }}
                </flux:subheading>
            </div>

            <div class="max-h-[60vh] overflow-y-auto rounded-xl border border-border">
                @php
                    $formatHistoryValue = static function (mixed $value): string {
                        if ($value === null || $value === '') {
                            return '-';
                        }
                        $text = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
                        $text = str_replace(["\r\n", "\r"], "\n", $text ?? '-');

                        return trim($text) === '' ? '-' : $text;
                    };
                @endphp
                <table class="w-full border-collapse text-xs">
                    <thead
                        class="sticky top-0 bg-muted/90 backdrop-blur-md text-left font-semibold uppercase text-muted-foreground border-b border-border">
                        <tr>
                            <th class="border-r border-border px-3 py-2.5">{{ __('Field / Type') }}</th>
                            <th class="border-r border-border px-3 py-2.5">{{ __('Original / Old Value') }}</th>
                            <th class="border-r border-border px-3 py-2.5">{{ __('New Value') }}</th>
                            <th class="border-r border-border px-3 py-2.5">{{ __('Date & User') }}</th>
                            <th class="px-3 py-2.5">{{ __('Justification') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($historyRecords as $history)
                            @if ($history['is_separator'] ?? false)
                                <tr class="bg-muted/50 border-y border-border text-muted-foreground text-[11px] font-semibold">
                                    <td colspan="3"
                                        class="border-r border-border px-3 py-1.5 text-center tracking-wide uppercase bg-slate-100 dark:bg-zinc-800/70 text-slate-600 dark:text-zinc-300 align-top max-w-0">
                                        <div class="truncate max-w-full"
                                            title="{{ '--- ' . ($history['separator_title'] ?? __('Group')) . ' ---' }}">
                                            {{ '--- ' . ($history['separator_title'] ?? __('Group')) . ' ---' }}
                                        </div>
                                    </td>
                                    <td
                                        class="border-r border-border px-3 py-2 align-top text-muted-foreground whitespace-nowrap bg-background">
                                        <div>{{ $history['date_created'] }}</div>
                                        <div class="text-[10px] font-semibold text-slate-500">{{ $history['user_name'] }}</div>
                                    </td>
                                    @if (($history['justification_rowspan'] ?? 1) > 0)
                                        <td @if(($history['justification_rowspan'] ?? 1) > 1)
                                        rowspan="{{ $history['justification_rowspan'] }}" @endif
                                            class="px-3 py-2 align-top text-foreground italic bg-background">
                                            {!! nl2br(e($history['justification'])) !!}
                                        </td>
                                    @endif
                                </tr>
                            @else
                                <tr class="border-b border-border/60 hover:bg-muted/20">
                                    <td
                                        class="border-r border-border px-3 py-2 align-top font-semibold text-slate-800 dark:text-zinc-200 uppercase">
                                        {{ $history['field_label'] ?? str_replace('_', ' ', $history['field_name']) }}
                                    </td>
                                    <td
                                        class="border-r border-border px-3 py-2 align-top text-muted-foreground whitespace-pre-line">
                                        {!! nl2br(e($formatHistoryValue($history['old_value'] ?: ($history['original_value'] ?: '-')))) !!}
                                    </td>
                                    <td
                                        class="border-r border-border px-3 py-2 align-top font-medium text-emerald-600 dark:text-emerald-400 whitespace-pre-line">
                                        {!! nl2br(e($formatHistoryValue($history['new_value'] ?: '-'))) !!}
                                    </td>
                                    <td
                                        class="border-r border-border px-3 py-2 align-top text-muted-foreground whitespace-nowrap">
                                        <div>{{ $history['date_created'] }}</div>
                                        <div class="text-[10px] font-semibold text-slate-500">{{ $history['user_name'] }}</div>
                                    </td>
                                    @if (($history['justification_rowspan'] ?? 1) > 0)
                                        <td @if(($history['justification_rowspan'] ?? 1) > 1)
                                        rowspan="{{ $history['justification_rowspan'] }}" @endif
                                            class="px-3 py-2 align-top text-foreground italic">
                                            {!! nl2br(e($history['justification'])) !!}
                                        </td>
                                    @endif
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-muted-foreground">
                                    {{ __('No edit history records found for this target entry.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between gap-2 pt-2">
                @if (!$this->isHistoryTargetLocked())
                    <flux:button variant="danger" type="button" icon="trash" wire:click="discardEditHistory"
                        :disabled="empty($historyRecords)"
                        class="bg-red-600 text-white hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700">
                        {{ __('Discard') }}
                    </flux:button>
                @endif

                <flux:modal.close>
                    <flux:button variant="ghost" type="button" wire:click="closeEditHistoryModal">
                        {{ __('Close') }}
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <!-- Save and Lock Confirm Modal -->
    <flux:modal wire:model="showLockConfirmModal" dismissible>
        <div class="space-y-4">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Save and Lock Semestral Target') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to save and lock all targets for this semester? Once locked, targets cannot be edited, deleted, or modified via right-click.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" type="button" wire:click="cancelLockConfirm">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="button"
                    class="bg-amber-600 text-white hover:bg-amber-700 font-semibold"
                    wire:click="saveAndLockSemestralTarget">
                    {{ __('Confirm and Lock') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Unlock Confirm Modal -->
    <flux:modal wire:model="showUnlockConfirmModal" dismissible>
        <div class="space-y-4">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Unlock Semestral Target') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to unlock all targets for this semester? Once unlocked, targets can be edited, deleted, or modified via right-click.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button variant="ghost" type="button" wire:click="cancelUnlockConfirm">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="button"
                    class="bg-emerald-600 text-white hover:bg-emerald-700 font-semibold"
                    wire:click="saveAndUnlockSemestralTarget">
                    {{ __('Confirm and Unlock') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    @php
        $targetSemId = request()->query('sem_id') ?: $semId;
        $isSemLocked = false;
        if ($targetSemId) {
            $isSemLocked = (int) \Illuminate\Support\Facades\DB::table('ipc_semester')->where('id', $targetSemId)->value('lock') === 1;
        }
    @endphp

    @if ($isSemLocked)
        <div style="position: fixed !important; bottom: 2rem !important; right: 2rem !important; z-index: 999999 !important;"
            x-data="{ isSaving: false }"
            x-on:semestral-target-scores-saved.window="isSaving = false"
            class="flex items-center gap-3">
            <button type="button" x-on:click="isSaving = true; $dispatch('save-all-scores')" :disabled="isSaving"
                class="inline-flex items-center justify-center gap-3 rounded-full bg-emerald-600 px-8 py-4 min-h-[58px] text-base font-black text-white shadow-2xl hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/50 dark:bg-emerald-500 dark:hover:bg-emerald-600 cursor-pointer transition-all duration-200 hover:scale-105 active:scale-95 border-2 border-white/40 ring-4 ring-black/10 disabled:opacity-85 disabled:cursor-not-allowed disabled:transform-none"
                style="cursor: pointer !important;">

                <template x-if="isSaving">
                    <svg class="animate-spin size-6 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </template>

                <template x-if="!isSaving">
                    <svg class="size-6 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                </template>

                <span x-text="isSaving ? '{{ __('Saving Scores...') }}' : '{{ __('Save Scores') }}'"
                    class="text-base font-black tracking-wider uppercase"></span>
            </button>
        </div>
    @endif
</section>