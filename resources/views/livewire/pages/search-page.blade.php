<section class="w-full">
    <div class="mb-6">
        <flux:heading size="lg" level="1">{{ __('Search Users') }}</flux:heading>
        <flux:subheading size="sm">{{ __('Find users by full name, division, or section.') }}</flux:subheading>
    </div>

    <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
        <div class="w-full overflow-hidden rounded-xl border border-border">
            <div class="overflow-x-auto border-b border-border bg-muted/25">
                <div class="flex min-w-[52rem] items-end gap-4 p-4">
                    <div class="min-w-0 flex-[1.4]">
                        <flux:input wire:model="search" icon:trailing="magnifying-glass" :label="__('Full Name')"
                            :placeholder="__('Search full name')" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <flux:select wire:model.live="divisionFilter" :label="__('Division')">
                            <option value="">{{ __('All divisions') }}</option>
                            @foreach ($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->division_name }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="min-w-0 flex-1">
                        <flux:field>
                            <flux:label>{{ __('Section') }}</flux:label>
                            <div class="relative" style="position: relative;">
                                <flux:select wire:model="sectionFilter" style="padding-left: 2.25rem;"
                                    wire:loading.attr="disabled" wire:target="divisionFilter">
                                    <option value="">{{ __('All sections') }}</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section->id }}">{{ $section->section_name }}</option>
                                    @endforeach
                                </flux:select>
                                <div wire:loading.flex wire:target="divisionFilter"
                                    class="pointer-events-none items-center text-muted-foreground"
                                    style="position: absolute; left: 0.75rem; top: 50%; z-index: 10; transform: translateY(-50%);">
                                    <flux:icon icon="arrow-path" class="size-4 animate-spin" />
                                </div>
                            </div>
                        </flux:field>
                    </div>

                    <flux:button type="button" variant="primary" icon="magnifying-glass" wire:click="applyFilters"
                        wire:loading.attr="disabled" wire:target="applyFilters">
                        <span wire:loading.remove wire:target="applyFilters">{{ __('Search') }}</span>
                        <span wire:loading wire:target="applyFilters">{{ __('Searching...') }}</span>
                    </flux:button>
                </div>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full border-separate border-spacing-0 text-sm">
                    <thead
                        class="bg-muted/50 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="border-b border-r border-border px-3 py-3 text-center first:rounded-tl-xl">#</th>
                            <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">
                                {{ __('Full Name') }}</th>
                            <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">{{ __('Division') }}
                            </th>
                            <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">{{ __('Section') }}
                            </th>
                            <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">{{ __('Position') }}
                            </th>
                            <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">
                                {{ __('Contact Number') }}</th>
                            <th class="border-b border-border px-3 py-3 whitespace-nowrap last:rounded-tr-xl">
                                {{ __('Email') }}</th>
                        </tr>
                    </thead>
                    <tbody wire:loading.class="opacity-50" wire:target="applyFilters,page">
                        @forelse ($users as $user)
                            @php
                                $fullName = trim(
                                    ($user->last_name ?? '') .
                                    (filled($user->last_name) ? ', ' : '') .
                                    collect([$user->first_name, $user->middle_name, $user->extension_name])->filter()->join(' ')
                                );
                            @endphp
                            <tr wire:key="search-user-{{ $user->id }}"
                                class="border-t border-border/60 text-sm hover:bg-muted/20">
                                <td class="border-b border-r border-border px-3 py-3 text-center text-muted-foreground">
                                    {{ ($users->firstItem() ?? 1) + $loop->index }}
                                </td>
                                <td class="border-b border-r border-border px-3 py-3 font-medium whitespace-nowrap">
                                    {{ strtoupper($fullName) }}
                                </td>
                                <td class="border-b border-r border-border px-3 py-3">{{ $user->division_name ?: '-' }}</td>
                                <td class="border-b border-r border-border px-3 py-3">{{ $user->section_name ?: '-' }}</td>
                                <td class="border-b border-r border-border px-3 py-3">{{ $user->position ?: '-' }}</td>
                                <td class="border-b border-r border-border px-3 py-3 whitespace-nowrap">
                                    {{ $user->contact_number ?: '-' }}</td>
                                <td class="border-b border-border px-3 py-3">{{ $user->email ?: '-' }}</td>
                            </tr>
                        @empty
                            <x-table-empty-state :colspan="7" :message="__('No users found for the selected filters.')" />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $users->links('vendor.pagination.users-pagination') }}
        </div>
    </div>
</section>