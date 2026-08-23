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

    <!-- Modal Header -->
    <div class="flex flex-col gap-3 border-b border-border pb-3 sm:flex-row sm:items-center sm:justify-between shrink-0">
        <div>
            <h3 class="text-base font-semibold text-foreground">{{ __('Copy Target from Previous Semester') }}</h3>
            <p class="text-xs text-muted-foreground">
                {{ __('Copy target entries from your previous semestral target lists into your current semestral target list.') }}
            </p>
        </div>
    </div>

    <!-- Semestral Target Content -->
    <div class="flex flex-1 flex-col gap-4 min-h-0">
        <!-- Filters -->
        <div class="shrink-0 relative z-30">
            <table class="w-full border-0 border-collapse">
                <tbody>
                    <tr class="align-top">
                        <td class="px-2 py-1 whitespace-nowrap w-36">
                            <x-select2 wire:model.live="copySourceYear" :label="__('Year')" :placeholder="__('Select Year')" :options="$this->copySourceYears()" minWidth="120px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            <x-select2 wire:model.live="copySourceSemester" :label="__('Semester')" :placeholder="__('All semesters')" :options="$this->semesters()" minWidth="160px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            <x-select2 wire:model.live="copySourceCategory" :label="__('Category')" :placeholder="__('All categories')" :options="$this->categories()" minWidth="160px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            <x-select2 wire:model.live="copySourceStatusFilter" :label="__('Status')" :placeholder="__('All Status')" :options="[
                                ['value' => 'new', 'label' => __('New Only')],
                                ['value' => 'existing', 'label' => __('Already Existing Only')]
                            ]" minWidth="160px" />
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap">
                            <div class="relative">
                                <flux:input wire:model.live.debounce.300ms="copySourceSearch" :label="__('Search')"
                                    :placeholder="__('Search activity/description...')" class="[&_input]:pr-8" />
                                <div wire:loading wire:target="copySourceSearch" class="absolute right-2.5 bottom-[9px] flex items-center justify-center pointer-events-none z-10 bg-card dark:bg-card">
                                    <svg class="animate-spin size-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                        </td>
                        <td class="px-2 py-1 whitespace-nowrap align-bottom">
                            <div class="flex h-full items-end">
                                <flux:button variant="primary" type="button" icon="document-duplicate" wire:click="requestCopyAllSemestral"
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
                $semestralTargetGroups = $this->copySemestralTargetGroups();
                $existingActivities = $this->existingActivities;
            @endphp

            @if ($semestralTargetGroups->isEmpty())
                <div class="flex h-full items-center justify-center p-12 text-center text-sm text-muted-foreground">
                    {{ __('No previous semestral targets found matching the filters.') }}
                </div>
            @else
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="sticky top-0 z-10 bg-card shadow-sm">
                        <tr class="border-b border-border">
                            <th class="px-3 py-2 font-semibold w-36 text-center">{{ __('Action') }}</th>
                            <th class="px-3 py-2 font-semibold w-64">{{ __('Activity / Indicator') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Sub-Targets & Measures') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($semestralTargetGroups as $indId => $items)
                            @php
                                $first = $items->first();
                                $isExisting = in_array(trim(mb_strtolower((string) ($first->activity ?? ''))), $existingActivities, true);
                                if ($copySourceStatusFilter === 'new' && $isExisting)
                                    continue;
                                if ($copySourceStatusFilter === 'existing' && !$isExisting)
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
                                            <button type="button" wire:click="copySemestralTargetGroup({{ (int) $indId }})"
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
                                        <button type="button" wire:click="copySemestralTargetGroup({{ (int) $indId }})"
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
                                        KRA Category: {{ \App\Support\KraCategory::label($first->kra_category ?? 1) }}
                                        @if (filled($first->target_year))
                                            | Year: {{ $first->target_year }}
                                        @endif
                                        @if (filled($first->target_sem))
                                            | Semester: {{ $formatSemester($first->target_sem) }}
                                        @endif
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
                                                        {!! nl2br(e($formatValue($item->rg_quantity ?? null))) !!}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Quality:</span>
                                                        {!! nl2br(e($formatValue($item->rg_quality ?? null))) !!}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Timeliness:</span>
                                                        {!! nl2br(e($formatValue($item->rg_timeliness ?? null))) !!}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Rating Period:</span>
                                                        {!! nl2br(e($formatValue($item->rg_ratingperiod ?? null))) !!}</div>
                                                    <div class="whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">MOV:</span>
                                                        {!! nl2br(e($formatValue($item->rg_movs ?? null))) !!}</div>
                                                    <div class="col-span-full whitespace-normal break-words"><span
                                                            class="font-medium text-foreground">Remarks:</span>
                                                        {!! nl2br(e($formatValue($item->rg_remarks ?? null))) !!}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Pagination controls below the table -->
        @if (is_object($semestralTargetGroups) && method_exists($semestralTargetGroups, 'total') && $semestralTargetGroups->total() > 0)
            <div class="flex flex-col gap-3 border-t border-border pt-3.5 pb-1 sm:flex-row sm:items-center sm:justify-between shrink-0">
                <div class="text-xs text-muted-foreground font-medium">
                    {{ __('Showing') }}
                    <span class="font-semibold text-foreground">{{ $semestralTargetGroups->firstItem() ?? 0 }}</span>
                    {{ __('to') }}
                    <span class="font-semibold text-foreground">{{ $semestralTargetGroups->lastItem() ?? 0 }}</span>
                    {{ __('of') }}
                    <span class="font-semibold text-foreground">{{ $semestralTargetGroups->total() }}</span>
                    {{ __('results') }}
                </div>

                <div class="flex items-center gap-2.5">
                    <button type="button"
                        wire:click="previousCopyPage"
                        @if ($semestralTargetGroups->onFirstPage()) disabled @endif
                        class="relative inline-flex h-8 w-20 shrink-0 items-center justify-center rounded-md border border-border bg-card px-3 py-1.5 text-xs font-medium text-foreground hover:bg-muted disabled:opacity-50 disabled:pointer-events-none transition-all shadow-sm">
                        <span wire:loading.remove wire:target="previousCopyPage" class="truncate">
                            {{ __('Previous') }}
                        </span>
                        <span wire:loading wire:target="previousCopyPage" class="absolute inset-0 flex items-center justify-center">
                            <svg class="animate-spin size-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>

                    <span class="text-xs text-muted-foreground font-medium px-1 select-none">
                        {{ __('Page') }} {{ $semestralTargetGroups->currentPage() }} {{ __('of') }} {{ $semestralTargetGroups->lastPage() }}
                    </span>

                    <button type="button"
                        wire:click="nextCopyPage"
                        @if (!$semestralTargetGroups->hasMorePages()) disabled @endif
                        class="relative inline-flex h-8 w-20 shrink-0 items-center justify-center rounded-md border border-border bg-card px-3 py-1.5 text-xs font-medium text-foreground hover:bg-muted disabled:opacity-50 disabled:pointer-events-none transition-all shadow-sm">
                        <span wire:loading.remove wire:target="nextCopyPage" class="truncate">
                            {{ __('Next') }}
                        </span>
                        <span wire:loading wire:target="nextCopyPage" class="absolute inset-0 flex items-center justify-center">
                            <svg class="animate-spin size-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal Footer -->
    <div class="flex items-center justify-end border-t border-border pt-3 shrink-0">
        <button type="button" wire:click="closeCopyModal"
            class="rounded-lg px-3 py-1.5 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors">
            {{ __('Close') }}
        </button>
    </div>
</div>
