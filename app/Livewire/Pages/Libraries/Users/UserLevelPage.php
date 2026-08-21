<?php

namespace App\Livewire\Pages\Libraries\Users;

use App\Actions\Users\ManageUserLevel;
use App\Data\SelectOption;
use App\Models\SidebarMenuItem;
use App\Models\UserLevel;
use App\Services\UserLevelDirectory;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('User Level')]
class UserLevelPage extends Component
{
    use WithPagination;

    public int $perPage = 10;

    public string $search = '';

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public bool $showMenuAccessModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public ?int $pendingId = null;

    public ?int $accessUserLevelId = null;

    public string $accessUserLevelName = '';

    /** @var list<string> */
    public array $selectedMenuItemIds = [];

    public string $menuSearch = '';

    public string $level_name = '';

    public string $is_status = '1';

    public bool $showInlineEdit = false;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->perPage = 10;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [10, 20, 50], true)) {
            $this->perPage = 10;
        }

        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditor(): void
    {
        $id = (int) ($this->pendingId ?? 0);
        $row = UserLevel::query()->where('level_id', $id)->first();

        if (! $row) {
            return;
        }

        $this->editingId = $row->level_id;
        $this->level_name = $row->level_name;
        $this->is_status = (string) ((int) ($row->is_status ?? 1));
        $this->showInlineEdit = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'level_name' => [
                'required',
                'string',
                'max:50',
                'unique:user_level,level_name'.($this->editingId ? ','.$this->editingId.',level_id' : ''),
            ],
            'is_status' => ['required', 'in:0,1'],
        ]);

        app(ManageUserLevel::class)->save($this->editingId, $data);

        if ($this->editingId !== null) {
            Flux::toast(variant: 'success', text: __('User level updated.'));
        } else {
            Flux::toast(variant: 'success', text: __('User level created.'));
        }

        $this->showInlineEdit = false;
        $this->resetForm();
        $this->resetPage();
    }

    public function openDeleteConfirm(): void
    {
        $id = (int) ($this->pendingId ?? 0);
        $row = UserLevel::query()->where('level_id', $id)->first();

        if (! $row) {
            return;
        }

        $this->deletingId = $row->level_id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId === null) {
            return;
        }

        app(ManageUserLevel::class)->delete($this->deletingId);

        $this->deletingId = null;
        $this->showDeleteModal = false;
        $this->resetPage();

        Flux::toast(variant: 'success', text: __('User level deleted.'));
    }

    public function openMenuAccessModal(int $levelId): void
    {
        $userLevel = UserLevel::query()->where('level_id', $levelId)->first();

        if (! $userLevel) {
            return;
        }

        $this->accessUserLevelId = $userLevel->level_id;
        $this->accessUserLevelName = $userLevel->level_name;
        $this->menuSearch = '';

        $allItems = SidebarMenuItem::all();
        $selected = [];

        foreach ($allItems as $item) {
            $levels = array_filter(array_map('intval', (array) ($item->user_levels ?? [])));
            if (empty($levels) || in_array($levelId, $levels, true)) {
                $selected[] = (string) $item->id;
            }
        }

        $this->selectedMenuItemIds = $selected;
        $this->showMenuAccessModal = true;
    }

    public function selectAllMenuAccess(): void
    {
        $this->selectedMenuItemIds = SidebarMenuItem::query()->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function deselectAllMenuAccess(): void
    {
        $this->selectedMenuItemIds = [];
    }

    public function saveMenuAccess(): void
    {
        if ($this->accessUserLevelId === null) {
            return;
        }

        $targetLevelId = (int) $this->accessUserLevelId;
        $allLevelIds = UserLevel::query()->where('is_status', 1)->pluck('level_id')->map(fn ($id) => (int) $id)->all();
        $allItems = SidebarMenuItem::all();

        $selectedIds = array_map('intval', $this->selectedMenuItemIds);

        foreach ($allItems as $item) {
            $itemLevels = array_filter(array_map('intval', (array) ($item->user_levels ?? [])));

            if (empty($itemLevels)) {
                $itemLevels = $allLevelIds;
            }

            $isChecked = in_array((int) $item->id, $selectedIds, true);

            if ($isChecked) {
                if (! in_array($targetLevelId, $itemLevels, true)) {
                    $itemLevels[] = $targetLevelId;
                }
            } else {
                $itemLevels = array_values(array_filter($itemLevels, fn ($id) => $id !== $targetLevelId));
            }

            sort($itemLevels);
            sort($allLevelIds);

            if (empty(array_diff($allLevelIds, $itemLevels)) && empty(array_diff($itemLevels, $allLevelIds))) {
                $item->user_levels = null;
            } else {
                $item->user_levels = array_values($itemLevels);
            }

            $item->save();
        }

        app(\App\Services\SidebarMenuTree::class)->forget();

        $this->showMenuAccessModal = false;
        $this->accessUserLevelId = null;

        Flux::toast(variant: 'success', text: __('Sidebar menu access updated for :level.', ['level' => $this->accessUserLevelName]));
    }

    /** @return list<array{item: SidebarMenuItem, depth: int}> */
    #[Computed]
    public function menuAccessRows(): array
    {
        $items = SidebarMenuItem::query()->orderBy('sort_order')->orderBy('label')->get();
        $byParent = $items->groupBy('parent_id');

        $rows = [];
        $walk = function (?int $parentId, int $depth) use (&$walk, $byParent, &$rows): void {
            foreach ($byParent->get($parentId, collect()) as $item) {
                $rows[] = [
                    'item' => $item,
                    'depth' => $depth,
                ];
                $walk($item->id, $depth + 1);
            }
        };

        $walk(null, 0);

        $search = mb_strtolower(trim($this->menuSearch));
        if ($search !== '') {
            $rows = array_values(array_filter($rows, function (array $row) use ($search): bool {
                $haystack = mb_strtolower($row['item']->label.' '.$row['item']->key.' '.$row['item']->href);

                return str_contains($haystack, $search);
            }));
        }

        return $rows;
    }

    /** @return array{count: int, total: int, is_all: bool} */
    public function accessSummary(int $levelId): array
    {
        $allItems = SidebarMenuItem::query()->active()->get();
        $totalCount = $allItems->count();

        if ($totalCount === 0) {
            return ['count' => 0, 'total' => 0, 'is_all' => true];
        }

        $accessibleCount = 0;
        foreach ($allItems as $item) {
            $levels = array_filter(array_map('intval', (array) ($item->user_levels ?? [])));
            if (empty($levels) || in_array($levelId, $levels, true)) {
                $accessibleCount++;
            }
        }

        return [
            'count' => $accessibleCount,
            'total' => $totalCount,
            'is_all' => $accessibleCount === $totalCount,
        ];
    }

    public function cancel(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->showMenuAccessModal = false;
        $this->showInlineEdit = false;
        $this->pendingId = null;
        $this->accessUserLevelId = null;
        $this->resetForm();
    }

    /** @return LengthAwarePaginator<int, UserLevel> */
    public function userLevels(): LengthAwarePaginator
    {
        return app(UserLevelDirectory::class)->paginate(trim($this->search), $this->perPage);
    }

    /** @return Collection<int, SelectOption> */
    public function perPageOptions(): Collection
    {
        return collect([
            new SelectOption(10, '10'),
            new SelectOption(20, '20'),
            new SelectOption(50, '50'),
        ]);
    }

    public function paginationView(): string
    {
        return 'vendor.pagination.users-pagination';
    }

    public function render(): View
    {
        return view('livewire.pages.libraries.users.user-level-page', [
            'userLevels' => $this->userLevels(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->deletingId = null;
        $this->pendingId = null;
        $this->level_name = '';
        $this->is_status = '1';
        $this->showInlineEdit = false;
    }
}
