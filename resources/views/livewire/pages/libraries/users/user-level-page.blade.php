<section class="w-full">
    <div class="mb-6">
        <flux:heading size="lg" level="1">{{ __('User Level') }}</flux:heading>
        <flux:subheading size="sm">{{ __('Create, update, search, and manage user levels with pagination.') }}
        </flux:subheading>
    </div>

    <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
        <div class="mb-4 flex flex-col gap-4 border-b border-border pb-4">
            <div class="grid gap-4 lg:grid-cols-3">
                <div class="relative">
                    <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')"
                        :placeholder="__('Level name')" class="[&_input]:pr-8" />
                    <div wire:loading wire:target="search"
                        class="absolute right-2.5 bottom-[9px] flex items-center justify-center pointer-events-none z-10 bg-card dark:bg-card">
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

                <div>
                    <x-select2 wire:model.live="perPage" :label="__('Per page')" :placeholder="__('Select')" :options="$this->perPageOptions()" minWidth="120px" :searchable="false" />
                </div>

                <div class="flex items-end">
                    <flux:button variant="primary" icon="plus" wire:click="create" class="w-full">
                        {{ __('Add User Level') }}
                    </flux:button>
                </div>
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
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">{{ __('Level Name') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">
                            {{ __('Sidebar Menu Access') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">{{ __('Status') }}</th>
                        <th class="border-b border-border px-3 py-3 text-right whitespace-nowrap last:rounded-tr-xl">
                            {{ __('Action') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($userLevels as $userLevel)
                    @php
                        $isEditing = $showInlineEdit && (int) $editingId === (int) $userLevel->level_id;
                    @endphp

                    <tr class="border-t border-border/60 text-sm hover:bg-muted/20">
                        <td
                            class="border-b border-r border-border px-2 py-3 text-center text-muted-foreground whitespace-nowrap">
                            {{ ($userLevels->firstItem() ?? 1) + $loop->index }}
                        </td>
                        <td class="border-b border-r border-border px-3 py-3">
                            @if ($isEditing)
                                <div class="space-y-3">
                                    <flux:input wire:model="level_name" :label="__('Level Name')" />
                                    <flux:select wire:model="is_status" :label="__('Status')">
                                        <flux:select.option value="1">{{ __('Active') }}</flux:select.option>
                                        <flux:select.option value="0">{{ __('Inactive') }}</flux:select.option>
                                    </flux:select>
                                </div>
                            @else
                                <div class="font-medium text-foreground">{{ $userLevel->level_name }}</div>
                            @endif
                        </td>
                        <td class="border-b border-r border-border px-3 py-3 whitespace-nowrap">
                            @php($summary = $this->accessSummary($userLevel->level_id))
                            @if ($summary['is_all'])
                                <span
                                    class="inline-flex items-center rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300">
                                    {{ __('Full Access (All Items)') }}
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center rounded-full bg-violet-500/10 px-2.5 py-0.5 text-xs font-medium text-violet-700 dark:text-violet-300">
                                    {{ __(':count of :total Items', ['count' => $summary['count'], 'total' => $summary['total']]) }}
                                </span>
                            @endif
                        </td>
                        <td class="border-b border-r border-border px-3 py-3 whitespace-nowrap">
                            @if (!$isEditing)
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-medium {{ (int) $userLevel->is_status === 1 ? 'bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/15' : 'bg-muted text-muted-foreground hover:bg-muted/80' }}">
                                    {{ (int) $userLevel->is_status === 1 ? __('Active') : __('Inactive') }}
                                </span>
                            @else
                                <span class="text-xs text-muted-foreground">{{ __('Editing inline') }}</span>
                            @endif
                        </td>
                        <td class="border-b border-border px-3 py-3 text-right whitespace-nowrap">
                            @if ($isEditing)
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="cancel">
                                        {{ __('Cancel') }}
                                    </flux:button>
                                    <flux:button size="sm" variant="primary" icon="check" wire:click="save">
                                        {{ __('Save') }}
                                    </flux:button>
                                </div>
                            @else
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button size="sm" variant="ghost" icon="key"
                                        wire:click="openMenuAccessModal({{ $userLevel->level_id }})">
                                        {{ __('Menu Access') }}
                                    </flux:button>
                                    <flux:button size="sm" variant="ghost" icon="pencil-square"
                                        wire:click="$set('pendingId', {{ $userLevel->level_id }}); openEditor()">
                                        {{ __('Edit') }}
                                    </flux:button>
                                    <flux:button size="sm" variant="ghost" icon="trash"
                                        class="text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                                        wire:click="$set('pendingId', {{ $userLevel->level_id }}); openDeleteConfirm()">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <x-table-empty-state :colspan="5" :message="__('No user levels found.')" />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $userLevels->links('vendor.pagination.users-pagination') }}
        </div>
    </div>

    <flux:modal wire:model="showMenuAccessModal" dismissible class="max-w-2xl">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Configure Sidebar Menu Access') }}</flux:heading>
                <flux:subheading>
                    {{ __('Select which sidebar menu items are accessible for :level.', ['level' => $accessUserLevelName]) }}
                </flux:subheading>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <flux:input wire:model.live.debounce.250ms="menuSearch" :placeholder="__('Search menu items...')"
                    class="w-full sm:w-64" />

                <div class="flex items-center gap-2">
                    <flux:button size="sm" variant="ghost" wire:click="selectAllMenuAccess">
                        {{ __('Select All') }}
                    </flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="deselectAllMenuAccess">
                        {{ __('Deselect All') }}
                    </flux:button>
                </div>
            </div>

            <div class="max-h-96 overflow-y-auto rounded-xl border border-border bg-background p-3 space-y-1.5">
                @forelse ($this->menuAccessRows as $row)
                @php($item = $row['item'])
                @php($depth = $row['depth'])
                <label
                    class="flex items-center gap-3 rounded-lg border border-border/50 p-2.5 hover:bg-muted/30 cursor-pointer transition-colors"
                    style="margin-left: {{ $depth * 1.25 }}rem">
                    <input type="checkbox" wire:model="selectedMenuItemIds" value="{{ $item->id }}"
                        class="size-4 rounded border-border text-primary focus:ring-primary" />
                    @if (filled($item->icon) && \App\Support\SidebarIcons::isValid($item->icon))
                        <div
                            class="flex size-7 shrink-0 items-center justify-center rounded-md border border-border bg-muted/40">
                            <flux:icon :icon="$item->icon" class="size-3.5 text-foreground" />
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-foreground">{{ $item->label }}</div>
                        @if (filled($item->href) || filled($item->key))
                            <div class="text-xs text-muted-foreground truncate">{{ $item->key ?: $item->href }}</div>
                        @endif
                    </div>
                </label>
                @empty
                <div class="p-6 text-center text-sm text-muted-foreground">
                    {{ __('No menu items match your search.') }}
                </div>
                @endforelse
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="button" wire:click="saveMenuAccess">
                    {{ __('Save Access Permissions') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showDeleteModal" dismissible class="max-w-lg">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Delete user level') }}</flux:heading>
                <flux:subheading>{{ __('This action cannot be undone.') }}</flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="button" class="bg-red-600 text-white hover:bg-red-700"
                    wire:click="delete">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>