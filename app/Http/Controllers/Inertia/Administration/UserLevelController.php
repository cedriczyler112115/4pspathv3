<?php

namespace App\Http\Controllers\Inertia\Administration;

use App\Actions\Users\ManageUserLevel;
use App\Http\Controllers\Controller;
use App\Models\SidebarMenuItem;
use App\Models\UserLevel;
use App\Services\SidebarMenuTree;
use App\Services\UserLevelDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserLevelController extends Controller
{
    public function index(Request $request, UserLevelDirectory $directory): Response
    {
        $filters = [
            'search' => (string) $request->string('search'),
            'perPage' => (int) ($request->integer('perPage') ?: 10),
        ];

        $levels = $directory->paginate($filters['search'], $filters['perPage']);

        return Inertia::render('Administration/UserLevel', [
            'filters' => $filters,
            'userLevels' => [
                'data' => array_map(fn (UserLevel $level) => [
                    'levelId' => (int) $level->level_id,
                    'levelName' => (string) $level->level_name,
                    'isStatus' => (int) $level->is_status,
                    'menuAccessSummary' => $this->accessSummary((int) $level->level_id),
                ], $levels->items()),
                'from' => $levels->firstItem(),
                'total' => $levels->total(),
                'currentPage' => $levels->currentPage(),
                'lastPage' => $levels->lastPage(),
            ],
            'perPageOptions' => [
                ['value' => 10, 'label' => '10'],
                ['value' => 20, 'label' => '20'],
                ['value' => 50, 'label' => '50'],
            ],
            'menuAccess' => $this->menuAccessRows(),
        ]);
    }

    public function save(Request $request, ManageUserLevel $manageUserLevel): RedirectResponse
    {
        $editingId = $request->input('editingId') ? (int) $request->input('editingId') : null;

        $data = $request->validate([
            'level_name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('user_level', 'level_name')->ignore($editingId, 'level_id'),
            ],
            'is_status' => ['required', 'in:0,1'],
        ]);

        $manageUserLevel->save($editingId, $data);

        return back()->with('success', $editingId ? __('User level updated.') : __('User level created.'));
    }

    public function destroy(ManageUserLevel $manageUserLevel, int $levelId): RedirectResponse
    {
        $manageUserLevel->delete($levelId);

        return back()->with('success', __('User level deleted.'));
    }

    public function saveMenuAccess(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'levelId' => ['required', 'integer', Rule::exists('user_level', 'level_id')],
            'selectedMenuItemIds' => ['nullable', 'array'],
            'selectedMenuItemIds.*' => ['integer', Rule::exists('sidebar_menu_items', 'id')],
        ]);

        $targetLevelId = (int) $data['levelId'];
        $selectedIds = array_map('intval', $data['selectedMenuItemIds'] ?? []);
        $allLevelIds = UserLevel::query()->where('is_status', 1)->pluck('level_id')->map(fn ($id) => (int) $id)->all();
        $allItems = SidebarMenuItem::query()->get();

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

        app(SidebarMenuTree::class)->forget();

        return back()->with('success', __('Sidebar menu access updated.'));
    }

    private function accessSummary(int $levelId): array
    {
        $allItems = SidebarMenuItem::query()->active()->get();
        $total = $allItems->count();

        if ($total === 0) {
            return ['count' => 0, 'total' => 0, 'isAll' => true];
        }

        $count = 0;
        foreach ($allItems as $item) {
            $levels = array_filter(array_map('intval', (array) ($item->user_levels ?? [])));
            if (empty($levels) || in_array($levelId, $levels, true)) {
                $count++;
            }
        }

        return ['count' => $count, 'total' => $total, 'isAll' => $count === $total];
    }

    private function menuAccessRows(): array
    {
        $items = SidebarMenuItem::query()->orderBy('sort_order')->orderBy('label')->get();
        $byParent = $items->groupBy('parent_id');
        $rows = [];

        $walk = function (?int $parentId, int $depth) use (&$walk, $byParent, &$rows): void {
            foreach ($byParent->get($parentId, collect()) as $item) {
                $rows[] = [
                    'id' => (int) $item->id,
                    'label' => (string) $item->label,
                    'key' => $item->key,
                    'href' => $item->href,
                    'icon' => $item->icon,
                    'depth' => $depth,
                    'userLevels' => array_filter(array_map('intval', (array) ($item->user_levels ?? []))),
                ];
                $walk($item->id, $depth + 1);
            }
        };

        $walk(null, 0);

        return $rows;
    }
}
