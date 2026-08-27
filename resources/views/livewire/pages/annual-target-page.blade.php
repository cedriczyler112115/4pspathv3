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

    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-1">
            <flux:heading size="lg" level="1">{{ __('Annual Target') }}</flux:heading>
            <flux:subheading size="sm">{{ __('Review annual target entries and manage profile information.') }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2 lg:justify-end">
            @if ($this->isLocked())
                <flux:button type="button" icon="lock-open" variant="primary" wire:click="requestUnlockAnnualTarget">
                    {{ __('Unlock Target') }}
                </flux:button>
            @else
                <flux:button type="button" icon="check" variant="primary" wire:click="requestLockAnnualTarget"
                    :disabled="empty($yearFilter)">
                    {{ __('Save and Lock Annual Target') }}
                </flux:button>
            @endif
            @if (!$this->isLocked())
                <flux:button type="button" icon="document-duplicate" wire:click="openCopyModal"
                    class="bg-violet-600 text-white hover:bg-violet-700 dark:bg-violet-500 dark:hover:bg-violet-400">
                    {{ __('Copy Target') }}
                </flux:button>
            @endif
            <flux:button type="button" icon="printer"
                class="bg-slate-600 text-white hover:bg-slate-700 dark:bg-slate-500 dark:hover:bg-slate-400">
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
        <div class="mb-4 border-b border-border pb-4 relative z-30">
            <div class="relative z-30">
                <table class="w-full border-0 border-collapse">
                    <tbody>
                        <tr class="align-top">
                            <td class="px-2 py-1 whitespace-nowrap">
                                <div class="relative">
                                    <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')"
                                        :placeholder="__('Search annual targets')" class="[&_input]:pr-8" />
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
                                <x-select2 wire:model.live="yearFilter" :label="__('Year')" :placeholder="null"
                                    :options="$years" minWidth="120px" />
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <x-select2 wire:model.live="categoryFilter" :label="__('Category')"
                                    :placeholder="__('All categories')" :options="$categories" minWidth="160px" />
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <x-select2 wire:model.live="semesterFilter" :label="__('Semester')"
                                    :placeholder="__('All semesters')" :options="$semesters" minWidth="160px" />
                            </td>
                            <td class="px-3 py-1 whitespace-nowrap align-bottom" valign="center">
                                <div class="flex h-[38px] items-center px-1" style="margin-bottom:10px !important">
                                    <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                                        <input type="checkbox" wire:model.live="showOnlyDuplicates"
                                            class="rounded border-input text-primary shadow-sm focus:ring-primary size-5" />
                                        <span
                                            class="text-sm font-medium text-foreground">{{ __('Show Duplicate Target') }}</span>
                                    </label>
                                    <div wire:loading wire:target="showOnlyDuplicates"
                                        class="ml-2 flex items-center pointer-events-none">
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
                                    :placeholder="__('Select')" :options="$perPageOptions" minWidth="120px"
                                    :searchable="false" />
                            </td>
                            <td class="px-1 py-1 whitespace-nowrap align-bottom">
                                <div class="flex h-full items-end -ml-1">
                                    <flux:button variant="primary" type="button" icon="arrow-path" wire:click="resetFilters"
                                        class="bg-slate-600 text-white hover:bg-slate-700 dark:bg-slate-500 dark:text-white dark:hover:bg-slate-400">
                                        {{ __('Reset') }}
                                    </flux:button>
                                </div>
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
                @foreach ($visibleCategories as $category)
                @php
                    $categoryRows = collect($annualTargets->items())->filter(fn($row) => (int) ($row->kra_category ?? 0) === (int) $category->value);
                    $groupedByIndicator = $categoryRows->groupBy(fn($row) => (int) ($row->indicator_id ?? $row->ind_id ?? 0));
                    $hasSupportFunctionRows = $categoryFilter !== ''
                        || (string) $category->value !== '3'
                        || $categoryRows->isNotEmpty();
                @endphp
                @continue(!$hasSupportFunctionRows)
                <tbody wire:key="annual-target-category-heading-{{ $category->value }}" x-data
                    x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'move'"
                    x-on:drop.prevent="const raw = $event.dataTransfer.getData('application/json'); if (raw) { $dispatch('annual-target-target-dropped', { source: JSON.parse(raw), target: { type: 'category', kra: {{ (int) $category->value }}, indicatorId: 0, itemId: 0 } }); }">
                    <tr wire:key="annual-target-category-{{ $category->value }}" class="bg-muted/30">
                        <td colspan="9" class="border-b border-border px-3 py-2">
                            <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                {{ $category->label }}
                            </div>
                        </td>
                    </tr>

                </tbody>

                @forelse ($groupedByIndicator as $indId => $rows)
                @php($groupRows = $rows->values())
                @php($firstRowStatus = (int) ($groupRows->first()->target_status ?? 1))
                <livewire:annual-target.indicator-rows :indicator-id="(int) $indId" :rows="$groupRows->map(fn($row) => (array) $row)->all()" :key="'annual-target-indicator-' . $indId . '-' . $firstRowStatus . '-' . $groupRows->pluck('id')->join('-')" />
                @empty
                <tbody wire:key="annual-target-empty-{{ $category->value }}">
                    <tr>
                        <td colspan="9" class="border-b border-border px-3 py-10 text-center text-muted-foreground">
                            {{ __('No record found in this category.') }}
                        </td>
                    </tr>
                </tbody>
                @endforelse
                @endforeach
            </table>
        </div>

        <div class="mt-4">
            {{ $annualTargets->links('vendor.pagination.users-pagination') }}
        </div>
    </div>

    <flux:modal wire:model="showDeleteModal" dismissible>
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Delete selected target and its sub-target') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to delete this target and all of its sub-targets? This action cannot be undone.') }}
                </flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="button" class="bg-red-600 text-white hover:bg-red-700"
                    wire:click="confirmDelete">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showDeleteSubTargetModal" dismissible>
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Delete selected sub-target') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to delete this sub-target? This action cannot be undone.') }}
                </flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="button" class="bg-red-600 text-white hover:bg-red-700"
                    wire:click="confirmDeleteSubTarget">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showLockModal" dismissible>
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Save and Lock Annual Target') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to save and lock your annual target entries? Once locked, these targets will no longer be editable.') }}
                </flux:subheading>
            </div>

            <div
                class="rounded-xl border border-amber-500/30 bg-amber-50/80 p-4 shadow-xs dark:border-amber-500/25 dark:bg-amber-950/30">
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                        <flux:icon icon="exclamation-triangle" class="size-4" />
                    </div>
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-amber-900 dark:text-amber-300">
                            {{ __('Important Notice') }}
                        </h4>
                        <p class="text-xs leading-relaxed text-amber-800 dark:text-amber-300/90">
                            {{ __('This will automatically create the') }}
                            <span
                                class="font-semibold text-amber-950 underline decoration-amber-400/60 underline-offset-2 dark:text-amber-100">
                                {{ __('1st Semester') }}
                            </span>
                            {{ __('and') }}
                            <span
                                class="font-semibold text-amber-950 underline decoration-amber-400/60 underline-offset-2 dark:text-amber-100">
                                {{ __('2nd Semester Target') }}
                            </span>
                            {{ __('in') }}
                            <span class="font-semibold text-amber-950 dark:text-amber-100">
                                {{ __('My Ratings') }}
                            </span>
                            {{ __('link.') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="button" class="bg-emerald-600 text-white hover:bg-emerald-700"
                    wire:click="confirmLockAnnualTarget">
                    {{ __('Confirm and Lock') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showUnlockModal" dismissible>
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Unlock Annual Target') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to unlock your annual target entries? Once unlocked, these targets can be edited and modified.') }}
                </flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="button" wire:click="confirmUnlockAnnualTarget">
                    {{ __('Confirm and Unlock') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showCopyModal" dismissible style="width: 80%; max-width: 80%; height: 90%; max-height: 90%;"
        class="overflow-y-auto">
        @include('livewire.annual-target.copy-target-modal')
    </flux:modal>

    <flux:modal wire:model="showCopyAllConfirmModal" dismissible>
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Copy all filtered targets?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to copy all filtered target results to your annual target list?') }}
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

    <flux:modal wire:model="showMoveConfirmModal" dismissible>
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Move target to another KRA?') }}</flux:heading>
                <flux:subheading>
                    {{ __('This target will be moved to a different Key Result Area category. Confirm to save the new category and position.') }}
                </flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="button" class="bg-blue-600 text-white hover:bg-blue-700"
                    wire:click="confirmTargetMove">
                    {{ __('Confirm move') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

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
</section>
