<section class="w-full space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-1">
            <flux:heading size="lg" level="1">{{ __('Harmonized Staff') }}</flux:heading>
            <flux:subheading size="sm">{{ __('Manage positions and roles for harmonized staff.') }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button type="button" variant="primary" icon="plus" wire:click="create">
                {{ __('Add Position') }}
            </flux:button>
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
                                        :placeholder="__('Search positions...')" class="[&_input]:pr-8" />
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
                                <x-select2 wire:model.live="perPage" :label="__('Records Per Page')" :placeholder="__('Select')" :options="$perPageOptions" minWidth="120px" :searchable="false" />
                            </td>
                            <td class="px-2 py-1 whitespace-nowrap align-bottom">
                                <flux:button type="button" variant="ghost" icon="arrow-path" wire:click="resetFilters"
                                    class="mb-0.5">
                                    {{ __('Reset Filters') }}
                                </flux:button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full border-separate border-spacing-0 text-sm">
                <thead
                    class="bg-muted/50 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    <tr>
                        <th class="border-b border-r border-border px-3 py-3 text-center first:rounded-tl-xl"
                            style="width: 60px;">
                            {{ __('#') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 whitespace-nowrap">
                            {{ __('Position Name') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 text-center whitespace-nowrap"
                            style="width: 120px;">
                            {{ __('Sort Order') }}
                        </th>
                        <th class="border-b border-r border-border px-3 py-3 text-center whitespace-nowrap"
                            style="width: 120px;">
                            {{ __('Status') }}
                        </th>
                        <th class="border-b border-border px-3 py-3 text-center whitespace-nowrap last:rounded-tr-xl"
                            style="width: 120px;">
                            {{ __('Action') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($positions as $index => $pos)
                        <tr wire:key="harmonized-pos-{{ $pos->id }}"
                            class="border-t border-border/60 text-sm hover:bg-muted/20">
                            <td class="border-b border-r border-border px-3 py-3 text-center text-muted-foreground">
                                {{ $positions->firstItem() + $index }}
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 font-medium text-foreground">
                                {{ $pos->name }}
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 text-center text-foreground">
                                {{ $pos->sort_order }}
                            </td>
                            <td class="border-b border-r border-border px-3 py-3 text-center">
                                @if ($pos->is_active)
                                    <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                                @endif
                            </td>
                            <td class="border-b border-border px-3 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <flux:button size="xs" variant="ghost" icon="pencil-square"
                                        wire:click="edit({{ $pos->id }})" aria-label="{{ __('Edit') }}" />
                                    <flux:button size="xs" variant="ghost" icon="trash"
                                        class="text-red-600 hover:text-red-700" wire:click="confirmDelete({{ $pos->id }})"
                                        aria-label="{{ __('Delete') }}" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="border-b border-border px-3 py-10 text-center text-muted-foreground">
                                {{ __('No positions found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $positions->links('vendor.pagination.users-pagination') }}
        </div>
    </div>

    <flux:modal wire:model="showFormModal" dismissible class="max-w-md">
        <form wire:submit.prevent="save" class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">
                    {{ $editingId ? __('Edit Position') : __('Add Position') }}
                </flux:heading>
                <flux:subheading>
                    {{ $editingId ? __('Update position details below.') : __('Enter position details to add a new record.') }}
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="name" :label="__('Position Name')" :placeholder="__('e.g., Provincial Link')"
                    required />

                <flux:input type="number" wire:model="sort_order" :label="__('Sort Order')" min="0" required />

                <flux:select wire:model="is_active" :label="__('Status')">
                    <option value="1">{{ __('Active') }}</option>
                    <option value="0">{{ __('Inactive') }}</option>
                </flux:select>
            </div>

            <div class="flex items-center justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        {{ __('Cancel') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">
                    {{ $editingId ? __('Save Changes') : __('Create Position') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showDeleteModal" dismissible class="max-w-md">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Delete Position') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to delete this position? This action cannot be undone.') }}
                </flux:subheading>
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