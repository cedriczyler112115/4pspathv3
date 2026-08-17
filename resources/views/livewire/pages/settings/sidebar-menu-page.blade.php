<section class="w-full">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 flex-1">
            @include('partials.settings-heading')
        </div>

        <flux:button
            variant="primary"
            icon="plus"
            wire:click="create"
            class="shrink-0 w-[100px] sm:mt-1"
        >
            {{ __('Add item') }}
        </flux:button>
    </div>

    <flux:heading class="sr-only">{{ __('Sidebar Menu') }}</flux:heading>

    <x-pages::settings.layout
        :heading="__('Sidebar Menu')"
        :subheading="__('Manage the sidebar structure, visibility, and nested navigation links')"
        contentClass="mt-5 w-full"
    >
        <div class="mt-6 space-y-6">
            <div class="rounded-2xl border border-border bg-muted/30 p-4 text-sm text-muted-foreground">
                {{ __('Tip: items with children become grouped navigation sections in the sidebar. An item can also keep its own link while still containing nested children.') }}
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">{{ __('Total items') }}</div>
                    <div class="mt-2 text-2xl font-semibold text-foreground">{{ $this->tableStats['total'] }}</div>
                    <div class="mt-1 text-sm text-muted-foreground">{{ __('All configured sidebar records') }}</div>
                </div>

                <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">{{ __('Active') }}</div>
                    <div class="mt-2 text-2xl font-semibold text-foreground">{{ $this->tableStats['active'] }}</div>
                    <div class="mt-1 text-sm text-muted-foreground">{{ __('Visible in the application sidebar') }}</div>
                </div>

                <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">{{ __('Inactive') }}</div>
                    <div class="mt-2 text-2xl font-semibold text-foreground">{{ $this->tableStats['inactive'] }}</div>
                    <div class="mt-1 text-sm text-muted-foreground">{{ __('Hidden from the application sidebar') }}</div>
                </div>

                <div class="rounded-2xl border border-border bg-card p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">{{ __('Nested items') }}</div>
                    <div class="mt-2 text-2xl font-semibold text-foreground">{{ $this->tableStats['nested'] }}</div>
                    <div class="mt-1 text-sm text-muted-foreground">{{ __('Child links assigned under a parent') }}</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-3xl border border-border bg-card shadow-sm">
                <div class="border-b border-border bg-muted/20 px-5 py-4">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div class="space-y-1">
                            <flux:heading size="sm">{{ __('Sidebar records') }}</flux:heading>
                            <flux:subheading>{{ __('Search by label, route key, href, icon, badge, or filter by visibility and hierarchy.') }}</flux:subheading>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                            <span class="rounded-full border border-border bg-background px-3 py-1.5">
                                {{ trans_choice('{0} No results|{1} :count result|[2,*] :count results', count($this->filteredRows), ['count' => count($this->filteredRows)]) }}
                            </span>

                            @if (filled($tableSearch) || $statusFilter !== 'all' || $hierarchyFilter !== 'all')
                                <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearTableFilters">
                                    {{ __('Clear filters') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,2fr)_minmax(180px,1fr)_minmax(180px,1fr)]">
                        <flux:input wire:model.live.debounce.300ms="tableSearch" :label="__('Search')" placeholder="dashboard, profile.edit, /settings, home..." />

                        <flux:select wire:model.live="statusFilter" :label="__('Status')">
                            <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
                            <flux:select.option value="active">{{ __('Active only') }}</flux:select.option>
                            <flux:select.option value="inactive">{{ __('Inactive only') }}</flux:select.option>
                        </flux:select>

                        <flux:select wire:model.live="hierarchyFilter" :label="__('Hierarchy')">
                            <flux:select.option value="all">{{ __('All levels') }}</flux:select.option>
                            <flux:select.option value="root">{{ __('Top level only') }}</flux:select.option>
                            <flux:select.option value="nested">{{ __('Nested only') }}</flux:select.option>
                        </flux:select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <flux:table>
                        <thead>
                            <tr class="bg-muted/30 text-xs text-muted-foreground">
                                <th class="px-4 py-3 text-left font-medium">{{ __('Label') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Key') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Href') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Icon') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Badge') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Order') }}</th>
                                <th class="px-4 py-3 text-left font-medium">{{ __('Active') }}</th>
                                <th class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->filteredRows as $row)
                                @php($item = $row['item'])
                                @php($depth = $row['depth'])
                                <tr class="border-t border-border/60 text-sm transition hover:bg-muted/20">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3" style="padding-left: {{ $depth * 1.25 }}rem">
                                            @if (filled($item->icon) && \App\Support\SidebarIcons::isValid($item->icon))
                                                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-border bg-muted/40">
                                                    <flux:icon :icon="$item->icon" class="size-4 text-foreground" />
                                                </div>
                                            @endif

                                            <div class="min-w-0">
                                                <div class="font-medium text-foreground">{{ $item->label }}</div>
                                                <div class="truncate text-xs text-muted-foreground">
                                                    {{ $depth === 0 ? __('Top level item') : __('Nested level :level', ['level' => $depth]) }}
                                                </div>
                                            </div>

                                            @if ($row['children_count'] > 0)
                                                <span class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                                    {{ trans_choice('{0} No children|{1} :count child|[2,*] :count children', $row['children_count'], ['count' => $row['children_count']]) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ $item->key ?: '—' }}</td>
                                    <td class="max-w-xs px-4 py-3 text-muted-foreground">
                                        <span class="block truncate">{{ $item->href ?: '—' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">
                                        @if (filled($item->icon) && \App\Support\SidebarIcons::isValid($item->icon))
                                            <div class="inline-flex items-center gap-2 rounded-full border border-border bg-background px-2.5 py-1">
                                                <flux:icon :icon="$item->icon" class="size-4 text-foreground" />
                                                <span>{{ $item->icon }}</span>
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if (filled($item->badge_text))
                                            <span class="inline-flex items-center gap-2">
                                                <flux:navlist.badge :color="$item->badge_cls">{{ $item->badge_text }}</flux:navlist.badge>
                                            </span>
                                        @else
                                            <span class="text-muted-foreground">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">
                                        <span class="inline-flex min-w-10 items-center justify-center rounded-full border border-border bg-background px-2.5 py-1 text-xs font-medium text-foreground">
                                            {{ $item->sort_order }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $item->is_active ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-200' : 'bg-zinc-500/15 text-zinc-600 dark:text-zinc-300' }}">
                                            {{ $item->is_active ? __('Active') : __('Inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <flux:button size="sm" variant="ghost" icon="plus" wire:click="create({{ $item->id }})">
                                                {{ __('Child') }}
                                            </flux:button>
                                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $item->id }})">
                                                {{ __('Edit') }}
                                            </flux:button>
                                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-600 dark:text-red-400" wire:click="confirmDelete({{ $item->id }})">
                                                {{ __('Delete') }}
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-sm text-muted-foreground">
                                        <div class="space-y-2">
                                            <div class="font-medium text-foreground">{{ __('No sidebar menu items found.') }}</div>
                                            <div>{{ __('Try adjusting your search or filters to find a matching menu item.') }}</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </flux:table>
                </div>
            </div>
        </div>

        <flux:modal wire:model="showFormModal" dismissible>
            <div class="space-y-6">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ $editingId ? __('Edit sidebar item') : __('Create sidebar item') }}</flux:heading>
                    <flux:subheading>{{ __('Configure label, route key, link, nesting, icon, badge, order, and visibility.') }}</flux:subheading>
                </div>

                <form class="space-y-5" wire:submit.prevent="save">
                    <flux:select wire:model="parent_id" :label="__('Parent item')" placeholder="{{ __('None (top level group)') }}">
                        <flux:select.option value="">{{ __('None (top level group)') }}</flux:select.option>
                        @foreach ($this->parentOptions as $option)
                            <flux:select.option value="{{ $option['id'] }}">{{ $option['label'] }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="label" :label="__('Label')" required />

                    <flux:input wire:model="key" :label="__('Route key (optional)')" placeholder="dashboard or profile.edit" />

                    <flux:input wire:model="href" :label="__('Href (optional)')" placeholder="/dashboard or https://example.com" />

                    <flux:input wire:model="sort_order" :label="__('Sort order')" type="number" />

                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <flux:label>{{ __('Icon (optional)') }}</flux:label>
                                <p class="text-sm text-muted-foreground">{{ __('Choose from the integrated icon set to keep sidebar rendering safe.') }}</p>
                            </div>

                            @if (filled($icon))
                                <flux:button type="button" size="sm" variant="ghost" icon="x-mark" wire:click="selectIcon(null)">
                                    {{ __('Clear') }}
                                </flux:button>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 rounded-xl border border-border bg-muted/30 px-4 py-3">
                            <div class="flex size-10 items-center justify-center rounded-lg border border-border bg-background">
                                @if (filled($icon) && \App\Support\SidebarIcons::isValid($icon))
                                    <flux:icon :icon="$icon" class="size-5 text-foreground" />
                                @else
                                    <flux:icon icon="paint-brush" class="size-5 text-muted-foreground" />
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="text-sm font-medium text-foreground">
                                    {{ filled($icon) ? $icon : __('No icon selected') }}
                                </div>
                                <div class="text-xs text-muted-foreground">{{ __('Selected icon preview') }}</div>
                            </div>
                        </div>

                        <flux:input wire:model.live="iconSearch" :label="__('Search icons')" placeholder="bars, user, home..." />

                        <div class="max-h-64 overflow-y-auto rounded-xl border border-border bg-background p-3">
                            <div class="grid grid-cols-4 gap-2 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-7">
                                @forelse ($this->availableIcons as $availableIcon)
                                    <button
                                        type="button"
                                        class="group flex flex-col items-center justify-center gap-1 rounded-lg border p-2 text-xs transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {{ $icon === $availableIcon ? 'border-primary bg-primary/10 text-foreground' : 'border-transparent bg-background text-muted-foreground hover:border-border hover:bg-muted' }}"
                                        wire:click="selectIcon('{{ $availableIcon }}')"
                                        aria-label="{{ $availableIcon }}"
                                    >
                                        <div class="flex h-9 w-9 items-center justify-center rounded-md border border-border bg-muted group-hover:bg-background">
                                            <flux:icon :icon="$availableIcon" class="size-5 text-foreground" />
                                        </div>
                                        <div class="w-full truncate text-center">{{ $availableIcon }}</div>
                                    </button>
                                @empty
                                    <div class="col-span-full rounded-lg border border-dashed border-border px-3 py-6 text-center text-sm text-muted-foreground">
                                        {{ __('No icons match your search.') }}
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <flux:input wire:model="badge_text" :label="__('Badge text (optional)')" placeholder="New" />
                        <flux:select wire:model="badge_cls" :label="__('Badge color (optional)')" placeholder="{{ __('Default') }}">
                            <flux:select.option value="">{{ __('Default') }}</flux:select.option>
                            @foreach (\App\Models\SidebarMenuItem::BADGE_COLORS as $color)
                                <flux:select.option value="{{ $color }}">{{ ucfirst($color) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <flux:checkbox wire:model="is_active" :label="__('Active (visible in sidebar)')" />

                    <div class="flex items-center justify-end gap-2">
                        <flux:button variant="ghost" type="button" wire:click="$set('showFormModal', false)">
                            {{ __('Cancel') }}
                        </flux:button>
                        <flux:button variant="primary" type="submit">
                            {{ $editingId ? __('Save changes') : __('Create') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

        <flux:modal wire:model="showDeleteModal" dismissible>
            <div class="space-y-5">
                <div class="space-y-1">
                    <flux:heading size="lg">{{ __('Delete sidebar item') }}</flux:heading>
                    <flux:subheading>{{ __('Deleting a parent will also delete its nested children.') }}</flux:subheading>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <flux:button variant="ghost" type="button" wire:click="$set('showDeleteModal', false)">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="button" class="bg-red-600 text-white hover:bg-red-700" wire:click="delete">
                        {{ __('Delete') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    </x-pages::settings.layout>
</section>
