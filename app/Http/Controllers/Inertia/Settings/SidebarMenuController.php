<?php

namespace App\Http\Controllers\Inertia\Settings;

use App\Http\Controllers\Controller;
use App\Models\SidebarMenuItem;
use App\Models\UserLevel;
use App\Services\SidebarMenuTree;
use App\Support\SidebarIcons;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SidebarMenuController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->string('search'),
            'status' => (string) $request->string('status', 'all'),
            'hierarchy' => (string) $request->string('hierarchy', 'all'),
            'userLevel' => (string) $request->string('userLevel', 'all'),
        ];

        $allRows = $this->rows();

        $filteredRows = array_values(array_filter($allRows, function (array $row) use ($filters): bool {
            $item = $row['item'];
            if ($filters['status'] === 'active' && ! $item->is_active) {
                return false;
            }
            if ($filters['status'] === 'inactive' && $item->is_active) {
                return false;
            }
            if ($filters['hierarchy'] === 'root' && $row['depth'] !== 0) {
                return false;
            }
            if ($filters['hierarchy'] === 'nested' && $row['depth'] === 0) {
                return false;
            }
            if ($filters['userLevel'] !== 'all') {
                $levelId = (int) $filters['userLevel'];
                $itemLevels = array_filter(array_map('intval', (array) ($item->user_levels ?? [])));
                if (! empty($itemLevels) && ! in_array($levelId, $itemLevels, true)) {
                    return false;
                }
            }
            $search = mb_strtolower(trim($filters['search']));
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
            ])));

            return str_contains($haystack, $search);
        }));

        $stats = [
            'total' => count($allRows),
            'active' => count(array_filter($allRows, fn ($r) => $r['item']->is_active)),
            'inactive' => count(array_filter($allRows, fn ($r) => ! $r['item']->is_active)),
            'nested' => count(array_filter($allRows, fn ($r) => $r['depth'] > 0)),
        ];

        return Inertia::render('Settings/SidebarMenu', [
            'filters' => $filters,
            'stats' => $stats,
            'rows' => array_map(fn ($r) => [
                'item' => [
                    'id' => $r['item']->id,
                    'parent_id' => $r['item']->parent_id,
                    'label' => $r['item']->label,
                    'key' => $r['item']->key,
                    'href' => $r['item']->href,
                    'icon' => $r['item']->icon,
                    'badge_text' => $r['item']->badge_text,
                    'badge_cls' => $r['item']->badge_cls,
                    'sort_order' => $r['item']->sort_order,
                    'is_active' => (bool) $r['item']->is_active,
                    'user_levels' => array_filter(array_map('intval', (array) ($r['item']->user_levels ?? []))),
                ],
                'depth' => $r['depth'],
            ], $filteredRows),
            'availableUserLevels' => $this->availableUserLevels(),
            'parentOptions' => $this->parentOptions(),
            'availableIcons' => SidebarIcons::all(),
            'badgeColors' => SidebarMenuItem::BADGE_COLORS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        SidebarMenuItem::query()->create($data);
        app(SidebarMenuTree::class)->forget();

        return back()->with('success', __('Sidebar menu item created.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $item = SidebarMenuItem::query()->findOrFail($id);
        $data = $this->validatePayload($request, $id);

        if ($data['parent_id'] && ($item->descendants()->contains($data['parent_id']) || $data['parent_id'] === $item->id)) {
            return back()->withErrors(['parent_id' => __('Please choose a parent outside the current item branch.')]);
        }

        $item->fill($data)->save();
        app(SidebarMenuTree::class)->forget();

        return back()->with('success', __('Sidebar menu item updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        SidebarMenuItem::query()->findOrFail($id)->delete();
        app(SidebarMenuTree::class)->forget();

        return back()->with('success', __('Sidebar menu item deleted.'));
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $input = $request->all();
        if (array_key_exists('parent_id', $input) && ($input['parent_id'] === '' || $input['parent_id'] === '0')) {
            $input['parent_id'] = null;
        }
        if (array_key_exists('key', $input) && $input['key'] === '') {
            $input['key'] = null;
        }
        if (array_key_exists('href', $input) && $input['href'] === '') {
            $input['href'] = null;
        }
        if (array_key_exists('icon', $input) && $input['icon'] === '') {
            $input['icon'] = null;
        }
        if (array_key_exists('badge_text', $input) && $input['badge_text'] === '') {
            $input['badge_text'] = null;
        }
        if (array_key_exists('badge_cls', $input) && $input['badge_cls'] === '') {
            $input['badge_cls'] = null;
        }
        $request->replace($input);

        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', Rule::exists('sidebar_menu_items', 'id')],
            'label' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sidebar_menu_items', 'label')
                    ->where(fn ($q) => $q->where('parent_id', $request->input('parent_id')))
                    ->ignore($ignoreId),
            ],
            'key' => ['nullable', 'string', 'max:255', Rule::unique('sidebar_menu_items', 'key')->ignore($ignoreId)],
            'href' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'badge_text' => ['nullable', 'string', 'max:255'],
            'badge_cls' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer'],
            'is_active' => ['boolean'],
            'user_levels' => ['nullable', 'array'],
            'user_levels.*' => ['integer'],
        ]);

        $data['parent_id'] = filled($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null;
        $data['key'] = filled($data['key'] ?? null) ? $data['key'] : null;
        $data['href'] = filled($data['href'] ?? null) ? $data['href'] : null;
        $data['icon'] = filled($data['icon'] ?? null) ? $data['icon'] : null;
        $data['badge_text'] = filled($data['badge_text'] ?? null) ? $data['badge_text'] : null;
        $data['badge_cls'] = filled($data['badge_cls'] ?? null) ? $data['badge_cls'] : null;
        $data['user_levels'] = ! empty($data['user_levels']) ? array_values(array_filter(array_map('intval', $data['user_levels']))) : null;

        return $data;
    }

    private function rows(): array
    {
        if (! Schema::hasTable('sidebar_menu_items')) {
            return [];
        }

        $items = SidebarMenuItem::treeAll();
        $flatten = function (array $nodes, int $depth = 0) use (&$flatten): array {
            $rows = [];
            foreach ($nodes as $node) {
                $rows[] = ['item' => $node->item, 'depth' => $depth];
                $rows = array_merge($rows, $flatten($node->children, $depth + 1));
            }

            return $rows;
        };

        return $flatten($items);
    }

    private function availableUserLevels(): array
    {
        if (! Schema::hasTable('user_level')) {
            return [];
        }

        return UserLevel::query()
            ->where('is_status', 1)
            ->orderBy('level_name')
            ->get(['level_id', 'level_name'])
            ->map(fn ($lvl) => ['level_id' => (int) $lvl->level_id, 'level_name' => (string) $lvl->level_name])
            ->all();
    }

    private function parentOptions(): array
    {
        return array_map(fn ($row) => [
            'id' => (int) $row['item']->id,
            'label' => str_repeat('— ', $row['depth']).$row['item']->label,
        ], $this->rows());
    }
}
