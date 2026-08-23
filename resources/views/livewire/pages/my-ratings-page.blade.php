<section class="w-full space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-1">
            <flux:heading size="lg" level="1">{{ __('My Ratings') }}</flux:heading>
            <flux:subheading size="sm">{{ __('Review IPCRF semester ratings and performance entries.') }}
            </flux:subheading>
        </div>
    </div>

    <!-- Container for Login / User Profile Info -->
    <div class="rounded-2xl border border-border bg-card p-4 shadow-xs">
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
    <div class="rounded-2xl border border-border bg-card p-4 shadow-xs">
        <div class="mb-4 border-b border-border pb-4 relative z-30">
            <div class="relative z-30">
                <table class="w-full border-0 border-collapse">
                    <tbody>
                        <tr class="align-top">
                            <td class="px-2 py-1 whitespace-nowrap">
                                <div class="relative">
                                    <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')"
                                        :placeholder="__('Search ratings...')" class="[&_input]:pr-8" />
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
                                <x-select2 wire:model.live="yearFilter" :label="__('Year')" :placeholder="__('All years')" :options="$years" minWidth="120px" />
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <x-select2 wire:model.live="semesterFilter" :label="__('Semester')"
                                    :placeholder="__('All semesters')" :options="$semesters" minWidth="160px" />
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap">
                                <x-select2 wire:model.live="perPage" :label="__('Records Per Page')"
                                    :placeholder="__('Select')" :options="$perPageOptions" minWidth="120px"
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
        </div>

        <!-- Table for ipc_semester Data -->
        <div class="w-full overflow-x-auto rounded-xl border border-border">
            <table class="w-full min-w-[900px] table-fixed border-separate border-spacing-0 text-sm">
                <thead
                    class="bg-muted/50 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th class="border-b border-r border-border px-3 py-3 text-center whitespace-nowrap first:rounded-tl-xl"
                            style="width: 50px;">#</th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap" style="width: 90px;">
                            {{ __('Year') }}</th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap" style="width: 130px;">
                            {{ __('Semester') }}</th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap" style="width: 130px;">
                            {{ __('Final Rating') }}</th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap" style="width: 160px;">
                            {{ __('Adjectival Rating') }}</th>
                        <th class="border-b border-r border-border px-4 py-3 text-center whitespace-nowrap"
                            style="width: 220px;">{{ __('Status') }}</th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap" style="width: 160px;">
                            {{ __('Date Created') }}</th>
                        <th class="border-b border-border px-3 py-3 text-center whitespace-nowrap last:rounded-tr-xl"
                            style="width: 120px;">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($myRatings as $rating)
                        <tr wire:key="rating-row-{{ $rating->id }}"
                            class="border-t border-border/60 text-sm hover:bg-muted/20">
                            <td class="border-b border-r border-border px-3 py-3 text-center text-muted-foreground align-middle">
                                {{ ($myRatings->firstItem() ?? 1) + $loop->index }}
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 font-semibold text-foreground align-middle">
                                {{ $rating->year }}
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 font-medium align-middle">
                                {{ (int) $rating->semester === 1 ? __('1st Semester') : ((int) $rating->semester === 2 ? __('2nd Semester') : $rating->semester) }}
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 font-mono align-middle">
                                {{ $rating->final_rating ?: '0.00000' }}
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 align-middle">
                                {{ $rating->adjectival_rating ?: '-' }}
                            </td>
                            <td class="border-b border-r border-border px-4 py-3 align-middle whitespace-nowrap">
                                @if (!empty($rating->date_verified))
                                    <div class="flex items-center gap-2 ml-[10px]">
                                        <svg class="size-5 text-emerald-600 dark:text-emerald-400 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                            <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.352-.272-2.64-.765-3.815a.75.75 0 00-.722-.516l-.143.001a11.209 11.209 0 01-7.59-3.25zm-2.486 11.41l-2.03-2.03a.75.75 0 00-1.06 1.06l2.56 2.56a.75.75 0 001.06 0l5.56-5.56a.75.75 0 00-1.06-1.06l-5.03 5.03z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 whitespace-nowrap">
                                            {{ \Illuminate\Support\Carbon::parse($rating->date_verified)->format('M d, Y h:i A') }}
                                        </span>
                                    </div>
                                @else
                                    <div class="text-center text-muted-foreground">-</div>
                                @endif
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 text-xs text-muted-foreground align-middle">
                                {{ $rating->date_created ? \Illuminate\Support\Carbon::parse($rating->date_created)->format('M d, Y h:i A') : '-' }}
                            </td>
                            <td class="border-b border-border px-3 py-3 align-middle whitespace-nowrap">
                                <div class="inline-flex items-center gap-2 ml-[10px]">
                                    <flux:button size="sm" variant="primary" icon="eye" square
                                        href="{{ route('myratings.semestral-target', ['sem_id' => $rating->id]) }}"
                                        wire:navigate
                                        title="{{ __('View') }}"
                                        class="bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-600 dark:text-white dark:hover:bg-emerald-700" />
                                    @if ((int) ($rating->is_ready ?? 0) !== 1 && empty($rating->date_verified))
                                        <flux:button size="sm" variant="danger" icon="trash" square
                                            title="{{ __('Remove') }}"
                                            class="bg-red-600 text-white hover:bg-red-700 dark:bg-red-600 dark:text-white dark:hover:bg-red-700"
                                            wire:click="confirmDelete({{ $rating->id }})" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="border-b border-border px-3 py-10 text-center text-muted-foreground">
                                {{ __('No rating records found in ipc_semester.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $myRatings->links('vendor.pagination.users-pagination') }}
        </div>
    </div>

    <!-- Confirmation Modal for Removing Semester Rating Record -->
    <flux:modal wire:model="showDeleteModal" dismissible>
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Remove Semester Rating Record') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to remove this semester rating record from ipc_semester? This action cannot be undone.') }}
                </flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:button variant="ghost" type="button" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="button" class="bg-red-600 text-white hover:bg-red-700"
                    wire:click="deleteRating">
                    {{ __('Confirm and Remove') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- View Details Modal -->
    <flux:modal wire:model="showViewModal" dismissible class="max-w-lg">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Semester Rating Details') }}</flux:heading>
                <flux:subheading>{{ __('Detailed performance rating information.') }}</flux:subheading>
            </div>

            @if ($viewingRating)
                <div class="grid gap-3 text-sm">
                    <div class="flex justify-between border-b border-border pb-2">
                        <span class="text-muted-foreground">{{ __('Year') }}:</span>
                        <span class="font-semibold text-foreground">{{ $viewingRating->year }}</span>
                    </div>
                    <div class="flex justify-between border-b border-border pb-2">
                        <span class="text-muted-foreground">{{ __('Semester') }}:</span>
                        <span class="font-semibold text-foreground">
                            {{ (int) $viewingRating->semester === 1 ? __('1st Semester') : ((int) $viewingRating->semester === 2 ? __('2nd Semester') : $viewingRating->semester) }}
                        </span>
                    </div>
                    <div class="flex justify-between border-b border-border pb-2">
                        <span class="text-muted-foreground">{{ __('Final Rating') }}:</span>
                        <span
                            class="font-mono font-semibold text-foreground">{{ $viewingRating->final_rating ?: '0.00000' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-border pb-2">
                        <span class="text-muted-foreground">{{ __('Adjectival Rating') }}:</span>
                        <span class="font-semibold text-foreground">{{ $viewingRating->adjectival_rating ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-border pb-2">
                        <span class="text-muted-foreground">{{ __('Status') }}:</span>
                        <div>
                            @if (!empty($viewingRating->date_verified))
                                <div class="inline-flex items-center gap-1.5">
                                    <svg class="size-6 text-emerald-600 dark:text-emerald-500 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.352-.272-2.64-.765-3.815a.75.75 0 00-.722-.516l-.143.001a11.209 11.209 0 01-7.59-3.25zm-2.486 11.41l-2.03-2.03a.75.75 0 00-1.06 1.06l2.56 2.56a.75.75 0 001.06 0l5.56-5.56a.75.75 0 00-1.06-1.06l-5.03 5.03z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 whitespace-nowrap">
                                        {{ \Illuminate\Support\Carbon::parse($viewingRating->date_verified)->format('M d, Y h:i A') }}
                                    </span>
                                </div>
                            @else
                                <span class="text-muted-foreground">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-1 border-b border-border pb-2">
                        <span class="text-muted-foreground">{{ __('Overall Remarks') }}:</span>
                        <p class="text-xs leading-relaxed text-foreground bg-muted/40 p-2 rounded-md">
                            {{ $viewingRating->overall_remarks ?: '-' }}</p>
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-end">
                <flux:button variant="ghost" type="button" wire:click="cancelView">
                    {{ __('Close') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>