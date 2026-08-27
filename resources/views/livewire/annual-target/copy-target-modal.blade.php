<div class="flex h-full flex-col space-y-4">
    @php
        $formatValue = static function (mixed $value): string {
            $text = html_entity_decode((string) ($value ?? '-'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/<br\s*\/?>/i', "\n", $text);

            return str_replace(["\r\n", "\r"], "\n", $text ?? '-');
        };

        $formatSemester = static function (mixed $value): string {
            $val = (int) $value;
            return match ($val) {
                1 => '1st Semester',
                2 => '2nd Semester',
                3 => 'Both Semester',
                default => (string) ($value ?? '-'),
            };
        };
    @endphp

    <!-- Modal Header & Tabs -->
    <div
        class="flex flex-col gap-3 border-b border-border pb-3 sm:flex-row sm:items-center sm:justify-between shrink-0">
        <div>
            <h3 class="text-base font-semibold text-foreground">{{ __('Copy Target') }}</h3>
            <p class="text-xs text-muted-foreground">
                {{ __('Copy target entries from staff annual targets or harmonized IPC targets into your list.') }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 rounded-xl bg-muted p-1" style="margin-right:50px">
                <button type="button" wire:click="$set('copyTab', 'staff')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors {{ $copyTab === 'staff' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                    {{ __('Staff Target') }}
                </button>
                <button type="button" wire:click="$set('copyTab', 'harmonized')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors {{ $copyTab === 'harmonized' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                    {{ __('Harmonized Target') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Tab 1: Staff Target Content -->
    <div x-show="$wire.copyTab === 'staff'" class="flex flex-1 flex-col gap-4 min-h-0">
        <!-- Filters -->
        <div class="shrink-0 relative z-30">
            <table class="w-full border-0 border-collapse">
                <tbody>
                    <tr class="align-top">
                        <td class="px-2 py-1 whitespace-nowrap" style="width:350px">
                            <x-select2 wire:model.live="copyStaffUserId" :label="__('Staff Name')" :options="$this->copyStaffUsers()->map(fn($u) => ['value' => (string)$u->id, 'label' => $u->full_name, 'sublabel' => $u->position ?? ''])->values()" minWidth="320px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap w-36" style="width:150px">
                            <x-select2 wire:model.live="copyStaffYear" :label="__('Year')" :placeholder="__('Select Year')" :options="$this->copyStaffYears()" minWidth="120px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            <x-select2 wire:model.live="copyStaffCategory" :label="__('Category')" :placeholder="__('All categories')" :options="$this->categories()" minWidth="160px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            <x-select2 wire:model.live="copyStaffSemester" :label="__('Semester')" :placeholder="__('All semesters')" :options="$this->semesters()" minWidth="160px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            <x-select2 wire:model.live="copyStaffStatusFilter" :label="__('Existing Target')" :placeholder="__('All Status')" :options="[
                                ['value' => 'new', 'label' => __('New Only')],
                                ['value' => 'existing', 'label' => __('Already Existing Only')]
                            ]" minWidth="160px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            <div class="relative">
                                <flux:input wire:model.live.debounce.300ms="copyStaffSearch" :label="__('Search')"
                                    :placeholder="__('Search activity/description...')" class="[&_input]:pr-8" />
                                <div wire:loading wire:target="copyStaffSearch" class="absolute right-2.5 bottom-[9px] flex items-center justify-center pointer-events-none z-10 bg-card dark:bg-card">
                                    <svg class="animate-spin size-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap align-bottom">
                                <flux:button variant="primary" type="button" icon="document-duplicate" wire:click="requestCopyAllStaff"
                                    class="bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:text-white dark:hover:bg-emerald-700">
                                    {{ __('Copy All Filtered Result') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Target Listing Table -->
        <div class="flex-1 min-h-[250px] rounded-xl border border-border bg-card overflow-y-auto" style="max-height: 55vh; overflow-y: auto;">
            @php
                $staffPaginator = $this->copyStaffTargetGroupsPaginator();
                $staffTargetGroups = $staffPaginator->getCollection();
                $existingActivities = $this->existingActivities;
            @endphp

            @if ($copyStaffUserId === '')
                <div class="flex h-full items-center justify-center p-12 text-center text-sm text-muted-foreground">
                    {{ __('Please select a staff member to view their targets.') }}
                </div>
            @elseif ($staffTargetGroups->isEmpty())
                <div class="flex h-full items-center justify-center p-12 text-center text-sm text-muted-foreground">
                    {{ __('No targets found for the selected staff member and year.') }}
                </div>
            @else
                <div class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-border bg-card px-3 py-2 text-xs text-muted-foreground shadow-sm">
                    <div>
                        {{ __('Showing :from to :to of :total groups', ['from' => $staffPaginator->firstItem(), 'to' => $staffPaginator->lastItem(), 'total' => $staffPaginator->total()]) }}
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                            wire:click="goToCopyStaffPage(1)" @disabled($staffPaginator->onFirstPage())>{{ __('First') }}</button>
                        <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                            wire:click="copyStaffPreviousPage" @disabled($staffPaginator->onFirstPage())>{{ __('Previous') }}</button>
                        @foreach ($this->paginationElements($staffPaginator) as $element)
                            @if ($element === 'start-ellipsis' || $element === 'end-ellipsis')
                                <span class="px-2 text-muted-foreground">...</span>
                            @elseif ($element === $staffPaginator->currentPage())
                                <span class="rounded-md bg-primary px-3 py-1 text-xs font-semibold text-primary-foreground">{{ $element }}</span>
                            @else
                                <button type="button" class="rounded-md border border-border px-3 py-1 text-xs font-medium hover:bg-muted"
                                    wire:click="goToCopyStaffPage({{ $element }})">{{ $element }}</button>
                            @endif
                        @endforeach
                        <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                            wire:click="copyStaffNextPage" @disabled(!$staffPaginator->hasMorePages())>{{ __('Next') }}</button>
                        <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                            wire:click="goToCopyStaffPage({{ $staffPaginator->lastPage() }})" @disabled($staffPaginator->onLastPage())>{{ __('Last') }}</button>
                    </div>
                </div>
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="sticky top-[41px] z-20 bg-card shadow-sm">
                        <tr class="border-b border-border">
                            <th class="px-3 py-2 font-semibold w-36 text-center">{{ __('Action') }}</th>
                            <th class="px-3 py-2 font-semibold w-64">{{ __('Activity / Indicator') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Sub-Targets & Measures') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($staffTargetGroups as $indId => $items)
                            @php
                                $first = $items->first();
                                $isExisting = in_array(trim(mb_strtolower((string) ($first->activity ?? ''))), $existingActivities, true);
                                if ($copyStaffStatusFilter === 'new' && $isExisting)
                                    continue;
                                if ($copyStaffStatusFilter === 'existing' && !$isExisting)
                                    continue;
                            @endphp
                            <tr class="border-b border-border/60 hover:bg-muted/20">
                                <td class="px-3 py-3 align-top text-center whitespace-nowrap">
                                    @if ($isExisting)
                                        <div class="flex flex-col items-center gap-1.5">
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2 py-0.5 text-[10px] font-medium text-amber-600 dark:text-amber-400">
                                                <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ __('Already Existing') }}
                                            </span>
                                            <button type="button" wire:click="copyStaffTargetGroup({{ (int) $indId }})"
                                                class="inline-flex items-center gap-1 rounded-md bg-primary px-2.5 py-1 text-xs font-medium text-primary-foreground hover:bg-primary/90 transition-colors shadow-sm"
                                                title="{{ __('Override and copy anyway') }}">
                                                <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2 2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                                {{ __('Copy Anyway') }}
                                            </button>
                                        </div>
                                    @else
                                        <button type="button" wire:click="copyStaffTargetGroup({{ (int) $indId }})"
                                            class="inline-flex items-center gap-1 rounded-md bg-primary px-2.5 py-1 text-xs font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
                                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            {{ __('Copy') }}
                                        </button>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-top font-medium text-foreground whitespace-normal break-words">
                                    {!! nl2br(e($formatValue($first->activity ?? null))) !!}
                                    <div class="mt-1 text-[11px] font-normal text-muted-foreground">
                                        KRA Category: {{ \App\Support\KraCategory::label($first->kra_category ?? 1) }} | Year:
                                        {{ $first->target_year }}
                                    </div>
                                </td>
                                <td class="px-3 py-3 align-top">
                                    <div class="space-y-2">
                                        @foreach ($items as $item)
                                            <div class="rounded-lg border border-border/40 bg-muted/10 p-2 space-y-1">
                                                <div class="font-medium text-foreground whitespace-normal break-words">
                                                    {!! nl2br(e($formatValue($item->description ?? null))) !!}</div>
                                                <div
                                                    class="grid grid-cols-2 gap-2 text-[11px] text-muted-foreground sm:grid-cols-3">
                                                    <div><span class="font-medium text-foreground">Semester:</span>
                                                        {{ $formatSemester($item->new_semester) }}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Efficiency:</span>
                                                        {!! nl2br(e($formatValue($item->rg_efficiency_ ?? null))) !!}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Quality:</span>
                                                        {!! nl2br(e($formatValue($item->rg_quality_ ?? null))) !!}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Timeliness:</span>
                                                        {!! nl2br(e($formatValue($item->rg_timeliness_ ?? null))) !!}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Rating Period:</span>
                                                        {!! nl2br(e($formatValue($item->rg_ratingperiod_ ?? null))) !!}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">MOV:</span>
                                                        {!! nl2br(e($formatValue($item->rg_mov_ ?? null))) !!}</div>
                                                    <div class="col-span-full whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Remarks:</span>
                                                        {!! nl2br(e($formatValue($item->rg_remarks_ ?? null))) !!}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="sticky bottom-0 z-30 flex items-center justify-end gap-1 border-t border-border bg-card px-3 py-2 text-xs text-muted-foreground shadow-sm">
                    <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                        wire:click="goToCopyStaffPage(1)" @disabled($staffPaginator->onFirstPage())>{{ __('First') }}</button>
                    <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                        wire:click="copyStaffPreviousPage" @disabled($staffPaginator->onFirstPage())>{{ __('Previous') }}</button>
                    @foreach ($this->paginationElements($staffPaginator) as $element)
                        @if ($element === 'start-ellipsis' || $element === 'end-ellipsis')
                            <span class="px-2 text-muted-foreground">...</span>
                        @elseif ($element === $staffPaginator->currentPage())
                            <span class="rounded-md bg-primary px-3 py-1 text-xs font-semibold text-primary-foreground">{{ $element }}</span>
                        @else
                            <button type="button" class="rounded-md border border-border px-3 py-1 text-xs font-medium hover:bg-muted"
                                wire:click="goToCopyStaffPage({{ $element }})">{{ $element }}</button>
                        @endif
                    @endforeach
                    <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                        wire:click="copyStaffNextPage" @disabled(!$staffPaginator->hasMorePages())>{{ __('Next') }}</button>
                    <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                        wire:click="goToCopyStaffPage({{ $staffPaginator->lastPage() }})" @disabled($staffPaginator->onLastPage())>{{ __('Last') }}</button>
                </div>
            @endif
        </div>
    </div>

    <!-- Tab 2: Harmonized Target Content -->
    <div x-show="$wire.copyTab === 'harmonized'" class="flex flex-1 flex-col gap-4 min-h-0" x-cloak>
        <!-- Filters -->
        <div class="shrink-0 relative z-30">
            <table class="w-full border-0 border-collapse">
                <tbody>
                    <tr class="align-top">
                        <td class="px-2 py-1 whitespace-nowrap">
                            <x-select2 wire:model.live="copyHarmonizedPositionId" :label="__('Harmonized Position')" :placeholder="__('Select Position')" :options="$this->copyHarmonizedPositions()" minWidth="200px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap w-36">
                            <x-select2 wire:model.live="copyHarmonizedYear" :label="__('Year')" :placeholder="__('Select Year')" :options="$this->copyHarmonizedYears()" minWidth="120px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            <x-select2 wire:model.live="copyHarmonizedCategory" :label="__('Category')" :placeholder="__('All categories')" :options="$this->categories()" minWidth="160px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            <x-select2 wire:model.live="copyHarmonizedSemester" :label="__('Semester')" :placeholder="__('All semesters')" :options="$this->semesters()" minWidth="160px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            <x-select2 wire:model.live="copyHarmonizedStatusFilter" :label="__('Status')" :placeholder="__('All Status')" :options="[
                                ['value' => 'new', 'label' => __('New Only')],
                                ['value' => 'existing', 'label' => __('Already Existing Only')]
                            ]" minWidth="160px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            <div class="relative">
                                <flux:input wire:model.live.debounce.300ms="copyHarmonizedSearch" :label="__('Search')"
                                    :placeholder="__('Search activity/description...')" class="[&_input]:pr-8" />
                                <div wire:loading wire:target="copyHarmonizedSearch" class="absolute right-2.5 bottom-[9px] flex items-center justify-center pointer-events-none z-10 bg-card dark:bg-card">
                                    <svg class="animate-spin size-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap align-bottom">
                            <div class="flex h-full items-end">
                                <flux:button variant="primary" type="button" icon="document-duplicate" wire:click="requestCopyAllHarmonized"
                                    class="bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:text-white dark:hover:bg-emerald-700">
                                    {{ __('Copy All Filtered Result') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Target Listing Table -->
        <div class="flex-1 min-h-[250px] rounded-xl border border-border bg-card overflow-y-auto" style="max-height: 55vh; overflow-y: auto;">
            @php
                $harmonizedPaginator = $this->copyHarmonizedTargetGroupsPaginator();
                $harmonizedTargetGroups = $harmonizedPaginator->getCollection();
                $existingActivities = $this->existingActivities;
            @endphp

            @if ($copyHarmonizedPositionId === '')
                <div class="flex h-full items-center justify-center p-12 text-center text-sm text-muted-foreground">
                    {{ __('Please select a harmonized position to view targets.') }}
                </div>
            @elseif ($harmonizedTargetGroups->isEmpty())
                <div class="flex h-full items-center justify-center p-12 text-center text-sm text-muted-foreground">
                    {{ __('No harmonized targets found for the selected position and year.') }}
                </div>
            @else
                <div class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b border-border bg-card px-3 py-2 text-xs text-muted-foreground shadow-sm">
                    <div>
                        {{ __('Showing :from to :to of :total groups', ['from' => $harmonizedPaginator->firstItem(), 'to' => $harmonizedPaginator->lastItem(), 'total' => $harmonizedPaginator->total()]) }}
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                            wire:click="goToCopyHarmonizedPage(1)" @disabled($harmonizedPaginator->onFirstPage())>{{ __('First') }}</button>
                        <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                            wire:click="copyHarmonizedPreviousPage" @disabled($harmonizedPaginator->onFirstPage())>{{ __('Previous') }}</button>
                        @foreach ($this->paginationElements($harmonizedPaginator) as $element)
                            @if ($element === 'start-ellipsis' || $element === 'end-ellipsis')
                                <span class="px-2 text-muted-foreground">...</span>
                            @elseif ($element === $harmonizedPaginator->currentPage())
                                <span class="rounded-md bg-primary px-3 py-1 text-xs font-semibold text-primary-foreground">{{ $element }}</span>
                            @else
                                <button type="button" class="rounded-md border border-border px-3 py-1 text-xs font-medium hover:bg-muted"
                                    wire:click="goToCopyHarmonizedPage({{ $element }})">{{ $element }}</button>
                            @endif
                        @endforeach
                        <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                            wire:click="copyHarmonizedNextPage" @disabled(!$harmonizedPaginator->hasMorePages())>{{ __('Next') }}</button>
                        <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                            wire:click="goToCopyHarmonizedPage({{ $harmonizedPaginator->lastPage() }})" @disabled($harmonizedPaginator->onLastPage())>{{ __('Last') }}</button>
                    </div>
                </div>
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="sticky top-[41px] z-20 bg-card shadow-sm">
                        <tr class="border-b border-border">
                            <th class="px-3 py-2 font-semibold w-36 text-center">{{ __('Action') }}</th>
                            <th class="px-3 py-2 font-semibold w-64">{{ __('Activity / Indicator') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Sub-Targets & Measures') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($harmonizedTargetGroups as $indId => $items)
                            @php
                                $first = $items->first();
                                $isExisting = in_array(trim(mb_strtolower((string) ($first->activity ?? ''))), $existingActivities, true);
                                if ($copyHarmonizedStatusFilter === 'new' && $isExisting)
                                    continue;
                                if ($copyHarmonizedStatusFilter === 'existing' && !$isExisting)
                                    continue;
                            @endphp
                            <tr class="border-b border-border/60 hover:bg-muted/20">
                                <td class="px-3 py-3 align-top text-center whitespace-nowrap">
                                    @if ($isExisting)
                                        <div class="flex flex-col items-center gap-1.5">
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2 py-0.5 text-[10px] font-medium text-amber-600 dark:text-amber-400">
                                                <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ __('Already Existing') }}
                                            </span>
                                            <button type="button" wire:click="copyHarmonizedTargetGroup({{ (int) $indId }})"
                                                class="inline-flex items-center gap-1 rounded-md bg-primary px-2.5 py-1 text-xs font-medium text-primary-foreground hover:bg-primary/90 transition-colors shadow-sm"
                                                title="{{ __('Override and copy anyway') }}">
                                                <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                                {{ __('Copy Anyway') }}
                                            </button>
                                        </div>
                                    @else
                                        <button type="button" wire:click="copyHarmonizedTargetGroup({{ (int) $indId }})"
                                            class="inline-flex items-center gap-1 rounded-md bg-primary px-2.5 py-1 text-xs font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
                                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            {{ __('Copy') }}
                                        </button>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-top font-medium text-foreground whitespace-normal break-words">
                                    {!! nl2br(e($formatValue($first->activity ?? null))) !!}
                                    <div class="mt-1 text-[11px] font-normal text-muted-foreground">
                                        KRA Category: {{ \App\Support\KraCategory::label($first->kra_category ?? 1) }} | Year:
                                        {{ $first->target_year }}
                                    </div>
                                </td>
                                <td class="px-3 py-3 align-top">
                                    <div class="space-y-2">
                                        @foreach ($items as $item)
                                            <div class="rounded-lg border border-border/40 bg-muted/10 p-2 space-y-1">
                                                <div class="font-medium text-foreground whitespace-normal break-words">
                                                    {!! nl2br(e($formatValue($item->description ?? null))) !!}</div>
                                                <div
                                                    class="grid grid-cols-2 gap-2 text-[11px] text-muted-foreground sm:grid-cols-3">
                                                    <div><span class="font-medium text-foreground">Semester:</span>
                                                        {{ $formatSemester($item->new_semester) }}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Efficiency:</span>
                                                        {!! nl2br(e($formatValue($item->rg_efficiency_ ?? null))) !!}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Quality:</span>
                                                        {!! nl2br(e($formatValue($item->rg_quality_ ?? null))) !!}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Timeliness:</span>
                                                        {!! nl2br(e($formatValue($item->rg_timeliness_ ?? null))) !!}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Rating Period:</span>
                                                        {!! nl2br(e($formatValue($item->rg_ratingperiod_ ?? null))) !!}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">MOV:</span>
                                                        {!! nl2br(e($formatValue($item->rg_mov_ ?? null))) !!}</div>
                                                    <div class="col-span-full whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Remarks:</span>
                                                        {!! nl2br(e($formatValue($item->rg_remarks_ ?? null))) !!}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="sticky bottom-0 z-30 flex items-center justify-end gap-1 border-t border-border bg-card px-3 py-2 text-xs text-muted-foreground shadow-sm">
                    <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                        wire:click="goToCopyHarmonizedPage(1)" @disabled($harmonizedPaginator->onFirstPage())>{{ __('First') }}</button>
                    <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                        wire:click="copyHarmonizedPreviousPage" @disabled($harmonizedPaginator->onFirstPage())>{{ __('Previous') }}</button>
                    @foreach ($this->paginationElements($harmonizedPaginator) as $element)
                        @if ($element === 'start-ellipsis' || $element === 'end-ellipsis')
                            <span class="px-2 text-muted-foreground">...</span>
                        @elseif ($element === $harmonizedPaginator->currentPage())
                            <span class="rounded-md bg-primary px-3 py-1 text-xs font-semibold text-primary-foreground">{{ $element }}</span>
                        @else
                            <button type="button" class="rounded-md border border-border px-3 py-1 text-xs font-medium hover:bg-muted"
                                wire:click="goToCopyHarmonizedPage({{ $element }})">{{ $element }}</button>
                        @endif
                    @endforeach
                    <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                        wire:click="copyHarmonizedNextPage" @disabled(!$harmonizedPaginator->hasMorePages())>{{ __('Next') }}</button>
                    <button type="button" class="rounded-md border border-border px-2 py-1 text-xs font-medium disabled:opacity-50"
                        wire:click="goToCopyHarmonizedPage({{ $harmonizedPaginator->lastPage() }})" @disabled($harmonizedPaginator->onLastPage())>{{ __('Last') }}</button>
                </div>
            @endif
        </div>
    </div>

    <div class="flex items-center justify-end border-t border-border pt-3 shrink-0">
        <flux:modal.close>
            <button type="button"
                class="rounded-lg px-3 py-1.5 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors">
                {{ __('Close') }}
            </button>
        </flux:modal.close>
    </div>
</div>
