<section class="w-full">
    <div class="mb-6">
        <flux:heading size="lg" level="1">{{ __('Administration Users') }}</flux:heading>
        <flux:subheading size="sm">{{ __('Browse and review users registered in the system.') }}</flux:subheading>
    </div>

    <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
        <div class="mb-4 flex flex-col gap-4 border-b border-border pb-4">
            <div class="grid gap-4 lg:grid-cols-4">
                <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')"
                    :placeholder="__('Full name, position, or designation')" />

                <flux:select wire:model.live="divisionFilter" :label="__('Division')">
                    <option value="">{{ __('All divisions') }}</option>
                    @foreach ($divisions as $division)
                        <option value="{{ $division->id }}">{{ $division->division_name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="sectionFilter" :label="__('Section')">
                    <option value="">{{ __('All sections') }}</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}">{{ $section->section_name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="statusFilter" :label="__('Status')">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="1">{{ __('Active') }}</option>
                    <option value="0">{{ __('Inactive') }}</option>
                </flux:select>
            </div>

        </div>

        <div class="w-full overflow-x-auto rounded-xl border border-border">
            <table class="w-full border-separate border-spacing-0 text-sm">
                <thead
                    class="bg-muted/50 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th
                            class="border-b border-r border-border px-2 py-3 whitespace-nowrap text-center first:rounded-tl-xl">
                            #</th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">{{ __('Full Name') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">{{ __('Email') }}</th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">
                            {{ __('Contact Number') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">{{ __('Position') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">{{ __('Division') }}
                        </th>
                        <th class="border-b border-border px-3 py-3 whitespace-nowrap last:rounded-tr-xl">
                            {{ __('Status') }}
                        </th>
                        <th class="border-b border-border px-3 py-3 text-right whitespace-nowrap">{{ __('Action') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="odd:bg-background even:bg-muted/25 hover:bg-accent/45 transition-colors">
                            <td
                                class="border-b border-r border-border px-2 py-3 text-center text-muted-foreground whitespace-nowrap">
                                {{ ($users->firstItem() ?? 1) + $loop->index }}
                            </td>
                            <td class="border-b border-r border-border px-3 py-3">
                                @php
                                    $fullName = trim(
                                        ($user->last_name ?? '') .
                                        (filled($user->last_name) ? ', ' : '') .
                                        collect([$user->first_name, $user->middle_name, $user->extension_name])->filter()->join(' ')
                                    );
                                @endphp
                                {{ strtoupper($fullName) }}
                            </td>
                            <td class="border-b border-r border-border px-3 py-3">{{ $user->email }}</td>
                            <td class="border-b border-r border-border px-3 py-3 whitespace-nowrap">
                                {{ $user->contact_number ?: ' - ' }}
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 truncate">
                                {{ \Illuminate\Support\Str::limit($user->position ?? '', 25, '...') ?: ' - ' }}
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 truncate">
                                {{ \Illuminate\Support\Str::limit($user->division_name ?? $user->division ?? '', 12, '...') ?: ' - ' }}
                            </td>
                            <td class="border-b border-border px-3 py-3 whitespace-nowrap">
                                <button
                                    type="button"
                                    wire:click="toggleStatus({{ $user->id }})"
                                    class="rounded-full px-2 py-1 text-xs font-medium transition cursor-pointer {{ (int) $user->is_status === 1 ? 'bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/15' : 'bg-muted text-muted-foreground hover:bg-muted/80' }}"
                                >
                                    {{ (int) $user->is_status === 1 ? __('Active') : __('Inactive') }}
                                </button>
                            </td>
                            <td class="border-b border-border px-3 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button size="sm" variant="ghost" icon="pencil-square"
                                        wire:click="edit({{ $user->id }})">
                                        {{ __('Edit') }}
                                    </flux:button>
                                    <flux:button size="sm" variant="ghost" icon="trash"
                                        class="text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                                        wire:click="confirmDelete({{ $user->id }})">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="border-b border-border px-3 py-8 text-center text-muted-foreground">
                                {{ __('No users found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links('vendor.pagination.users-pagination') }}
        </div>
    </div>

    <flux:modal wire:model="showEditModal" dismissible
        style="width: min(64rem, calc(100vw - 2rem)); max-width: min(64rem, calc(100vw - 2rem));">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Edit user') }}</flux:heading>
                <flux:subheading>{{ __('Update the selected user profile details.') }}</flux:subheading>
            </div>

            <form wire:submit.prevent="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model.live="editLastName" :label="__('Lastname')" />
                    <flux:input wire:model.live="editFirstName" :label="__('Firstname')" />
                    <flux:input wire:model.live="editMiddleName" :label="__('Middlename')" />
                    <flux:input wire:model.live="editExtensionName" :label="__('Extension Name')" />
                    <flux:input wire:model.live="editPosition" :label="__('Position')" />
                    <flux:input wire:model.live="editDesignation" :label="__('Designation')" />
                    <flux:select wire:model.live="editDivision" :label="__('Division')">
                        <option value="">{{ __('Select division') }}</option>
                        @foreach ($divisions as $division)
                            <option value="{{ $division->id }}">{{ $division->division_name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="editSection" :label="__('Section')">
                        <option value="">{{ __('Select section') }}</option>
                        @foreach ($editSections as $section)
                            <option value="{{ $section->id }}">{{ $section->section_name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="editSupervisorId" :label="__('Supervisor')">
                        <option value="">{{ __('Select supervisor') }}</option>
                            @foreach ($supervisors as $supervisor)
                                @php
                                    $supervisorName = trim(
                                        ($supervisor->last_name ?? '') .
                                        (filled($supervisor->last_name) ? ', ' : '') .
                                        collect([$supervisor->first_name, $supervisor->middle_name, $supervisor->extension_name])->filter()->join(' ')
                                    );
                                @endphp
                                <option value="{{ $supervisor->id }}">{{ strtoupper($supervisorName) }}</option>
                            @endforeach
                    </flux:select>
                    <flux:input wire:model.live="editContactNumber" :label="__('Contact Number')" />
                    <div class="space-y-2">
                        <flux:label>{{ __('Is Supervisor') }}</flux:label>
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-border bg-background px-4 py-3">
                            <input type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                                wire:model.live="editIsSupervisor">
                            <span class="text-sm text-foreground">
                                {{ __('Make as Supervisor') }}
                            </span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" type="button" wire:click="$set('showEditModal', false)">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ __('Save changes') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <flux:modal wire:model="showDeleteModal" dismissible class="max-w-lg">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Delete user') }}</flux:heading>
                <flux:subheading>
                    {{ __('This will permanently remove the selected user from the list.') }}
                </flux:subheading>
            </div>

            <div class="rounded-xl border border-border bg-muted/30 px-4 py-3 text-sm text-muted-foreground">
                <div>{{ __('Selected user:') }} <span class="font-semibold text-foreground">{{ $deleteUserName ?: '-' }}</span></div>
                <div>{{ __('Selected user ID:') }} <span class="font-semibold text-foreground">{{ $deleteUserId ?? '-' }}</span></div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" type="button" wire:click="$set('showDeleteModal', false)">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" type="button" wire:click="delete">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
