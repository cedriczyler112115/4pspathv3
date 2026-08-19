<section class="w-full">
    <div class="mb-6">
        <flux:heading size="lg" level="1">{{ __('User Level') }}</flux:heading>
        <flux:subheading size="sm">{{ __('Create, update, search, and manage user levels with pagination.') }}</flux:subheading>
    </div>

    <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
        <div class="mb-4 flex flex-col gap-4 border-b border-border pb-4">
            <div class="grid gap-4 lg:grid-cols-3">
                <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" :placeholder="__('Level name')" />

                <flux:select wire:model.live="perPage" :label="__('Per page')">
                    @foreach ($this->perPageOptions() as $option)
                        <flux:select.option value="{{ $option->value }}">{{ $option->label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex items-end">
                    <flux:button variant="primary" icon="plus" wire:click="create" class="w-full">
                        {{ __('Add User Level') }}
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="w-full overflow-x-auto rounded-xl border border-border">
            <table class="w-full border-separate border-spacing-0 text-sm">
                <thead class="bg-muted/50 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th class="border-b border-r border-border px-2 py-3 whitespace-nowrap text-center first:rounded-tl-xl">#</th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">{{ __('Level Name') }}</th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">{{ __('Status') }}</th>
                        <th class="border-b border-border px-3 py-3 text-right whitespace-nowrap last:rounded-tr-xl">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($userLevels as $userLevel)
                        @php
                            $isEditing = $showInlineEdit && (int) $editingId === (int) $userLevel->level_id;
                        @endphp

                        <tr class="odd:bg-background even:bg-muted/25 hover:bg-accent/45 transition-colors">
                            <td class="border-b border-r border-border px-2 py-3 text-center text-muted-foreground whitespace-nowrap">
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
                                @if (! $isEditing)
                                    <span class="rounded-full px-2 py-1 text-xs font-medium {{ (int) $userLevel->is_status === 1 ? 'bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/15' : 'bg-muted text-muted-foreground hover:bg-muted/80' }}">
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
                                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="$set('pendingId', {{ $userLevel->level_id }}); openEditor()">
                                            {{ __('Edit') }}
                                        </flux:button>
                                        <flux:button size="sm" variant="ghost" icon="trash" class="text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10" wire:click="$set('pendingId', {{ $userLevel->level_id }}); openDeleteConfirm()">
                                            {{ __('Delete') }}
                                        </flux:button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="border-b border-border px-3 py-8 text-center text-muted-foreground">
                                {{ __('No user levels found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $userLevels->links('vendor.pagination.users-pagination') }}
        </div>
    </div>

    <flux:modal wire:model="showDeleteModal" dismissible class="max-w-lg">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Delete user level') }}</flux:heading>
                <flux:subheading>{{ __('This action cannot be undone.') }}</flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:button variant="ghost" type="button" wire:click="cancel">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="button" class="bg-red-600 text-white hover:bg-red-700" wire:click="delete">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
