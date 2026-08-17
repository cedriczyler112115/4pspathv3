<?php

namespace App\Livewire\Pages\Settings;

use App\Data\SidebarMenuNode;
use App\Models\SidebarMenuItem;
use App\Support\SidebarIcons;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Sidebar Menu')]
class SidebarMenuPage extends Component
{
    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public ?int $parent_id = null;

    public string $label = '';

    public ?string $key = null;

    public ?string $href = null;

    public ?string $icon = null;

    public string $iconSearch = '';

    public ?string $badge_text = null;

    public ?string $badge_cls = null;

    public int $sort_order = 0;

    public bool $is_active = true;

    public string $tableSearch = '';

    public string $statusFilter = 'all';

    public string $hierarchyFilter = 'all';

    /** @return list<array{item: SidebarMenuItem, depth: int, children_count: int}> */
    #[Computed]
    public function rows(): array
    {
        if (! Schema::hasTable('sidebar_menu_items')) {
            return [];
        }

        return $this->flatten(SidebarMenuItem::treeAll());
    }

    /** @return list<array{id: int, label: string}> */
    #[Computed]
    public function parentOptions(): array
    {
        $excludedIds = collect();

        if ($this->editingId !== null) {
            $item = SidebarMenuItem::query()->find($this->editingId);

            if ($item) {
                $excludedIds = $item->descendants()->push($item->id);
            }
        }

        return array_values(collect($this->rows())
            ->filter(fn (array $row) => ! $excludedIds->contains($row['item']->id))
            ->map(function (array $row): array {
                $prefix = str_repeat('— ', $row['depth']);

                return [
                    'id' => $row['item']->id,
                    'label' => $prefix.$row['item']->label,
                ];
            })
            ->all());
    }

    /** @return list<string> */
    #[Computed]
    public function availableIcons(): array
    {
        $icons = SidebarIcons::all();

        if (! filled($this->iconSearch)) {
            return $icons;
        }

        $search = mb_strtolower($this->iconSearch);

        return array_values(array_filter($icons, fn (string $icon): bool => str_contains(mb_strtolower($icon), $search)));
    }

    /** @return list<array{item: SidebarMenuItem, depth: int, children_count: int}> */
    #[Computed]
    public function filteredRows(): array
    {
        $search = mb_strtolower(trim($this->tableSearch));

        return array_values(array_filter($this->rows(), function (array $row) use ($search): bool {
            $item = $row['item'];

            if ($this->statusFilter === 'active' && ! $item->is_active) {
                return false;
            }

            if ($this->statusFilter === 'inactive' && $item->is_active) {
                return false;
            }

            if ($this->hierarchyFilter === 'root' && $row['depth'] !== 0) {
                return false;
            }

            if ($this->hierarchyFilter === 'nested' && $row['depth'] === 0) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            $haystack = mb_strtolower(implode(' ', array_filter([
                $item->label,
                $item->key,
                $item->href,
                $item->icon,
                $item->badge_text,
                (string) $item->sort_order,
                $item->is_active ? 'active yes enabled visible' : 'inactive no disabled hidden',
            ], fn ($value) => filled($value))));

            return str_contains($haystack, $search);
        }));
    }

    /** @return array{total: int, active: int, inactive: int, nested: int} */
    #[Computed]
    public function tableStats(): array
    {
        $rows = collect($this->rows());

        return [
            'total' => $rows->count(),
            'active' => $rows->where('item.is_active', true)->count(),
            'inactive' => $rows->where('item.is_active', false)->count(),
            'nested' => $rows->where('depth', '>', 0)->count(),
        ];
    }

    public function create(?int $parentId = null): void
    {
        $this->resetForm();
        $this->parent_id = SidebarMenuItem::query()->whereKey($parentId)->value('id');
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $item = SidebarMenuItem::query()->findOrFail($id);

        $this->editingId = $item->id;
        $this->parent_id = $item->parent_id;
        $this->label = $item->label;
        $this->key = $item->key;
        $this->href = $item->href;
        $this->icon = $item->icon;
        $this->badge_text = $item->badge_text;
        $this->badge_cls = $item->badge_cls;
        $this->sort_order = $item->sort_order;
        $this->is_active = $item->is_active;

        $this->showFormModal = true;
    }

    public function selectIcon(?string $icon): void
    {
        if ($icon === null) {
            $this->icon = null;
            $this->iconSearch = '';

            return;
        }

        if (SidebarIcons::isValid($icon)) {
            $this->icon = $icon;
        }
    }

    public function save(): void
    {
        if (! Schema::hasTable('sidebar_menu_items')) {
            Flux::toast(variant: 'danger', text: __('The sidebar menu table is missing. Run migrations first.'));

            return;
        }

        $itemId = $this->editingId;

        $validated = $this->validate([
            'parent_id' => ['nullable', 'integer', Rule::exists('sidebar_menu_items', 'id')],
            'label' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sidebar_menu_items', 'label')
                    ->where(fn ($query) => $query->where('parent_id', $this->parent_id))
                    ->ignore($itemId),
            ],
            'key' => ['nullable', 'string', 'max:255', Rule::unique('sidebar_menu_items', 'key')->ignore($itemId)],
            'href' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255', Rule::in(SidebarIcons::all())],
            'badge_text' => ['nullable', 'string', 'max:255'],
            'badge_cls' => [
                'nullable',
                'string',
                'max:255',
                Rule::in(SidebarMenuItem::BADGE_COLORS),
            ],
            'sort_order' => ['required', 'integer'],
            'is_active' => ['boolean'],
        ]);

        $validated['key'] = filled($validated['key'] ?? null) ? $validated['key'] : null;
        $validated['href'] = filled($validated['href'] ?? null) ? $validated['href'] : null;
        $validated['icon'] = filled($validated['icon'] ?? null) ? $validated['icon'] : null;
        $validated['badge_text'] = filled($validated['badge_text'] ?? null) ? $validated['badge_text'] : null;
        $validated['badge_cls'] = filled($validated['badge_cls'] ?? null) ? $validated['badge_cls'] : null;

        if ($itemId !== null && (int) ($validated['parent_id'] ?? 0) > 0) {
            $item = SidebarMenuItem::query()->findOrFail($itemId);

            if ($item->descendants()->contains((int) $validated['parent_id']) || (int) $validated['parent_id'] === $item->id) {
                $this->addError('parent_id', __('Please choose a parent outside the current item branch.'));

                return;
            }
        }

        if ($itemId) {
            SidebarMenuItem::query()->whereKey($itemId)->update($validated);
            Flux::toast(variant: 'success', text: __('Sidebar menu item updated.'));
        } else {
            SidebarMenuItem::query()->create($validated);
            Flux::toast(variant: 'success', text: __('Sidebar menu item created.'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function clearTableFilters(): void
    {
        $this->tableSearch = '';
        $this->statusFilter = 'all';
        $this->hierarchyFilter = 'all';
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $id = $this->deletingId;

        if ($id === null) {
            return;
        }

        SidebarMenuItem::query()->whereKey($id)->delete();

        $this->showDeleteModal = false;
        $this->deletingId = null;

        Flux::toast(variant: 'success', text: __('Sidebar menu item deleted.'));
    }

    public function render(): View
    {
        return view('livewire.pages.settings.sidebar-menu-page');
    }

    protected function resetForm(): void
    {
        $this->reset(
            'showDeleteModal',
            'editingId',
            'deletingId',
            'parent_id',
            'label',
            'key',
            'href',
            'icon',
            'iconSearch',
            'badge_text',
            'badge_cls',
            'sort_order',
            'is_active',
        );

        $this->sort_order = 0;
        $this->is_active = true;
    }

    /**
     * @param  list<SidebarMenuNode>  $nodes
     * @return list<array{item: SidebarMenuItem, depth: int, children_count: int}>
     */
    protected function flatten(array $nodes, int $depth = 0): array
    {
        $rows = [];

        foreach ($nodes as $node) {
            $rows[] = [
                'item' => $node->item,
                'depth' => $depth,
                'children_count' => count($node->children),
            ];

            if ($node->children !== []) {
                $rows = array_merge($rows, $this->flatten($node->children, $depth + 1));
            }
        }

        return $rows;
    }
}
