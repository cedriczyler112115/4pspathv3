<?php

namespace App\Services;

use App\Data\SidebarMenuNode;
use App\Models\ApplicationSetting;
use App\Models\SidebarMenuItem;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SidebarMenuTree
{
    private const CACHE_KEY = 'sidebar-menu.tree.active';

    /** @return list<SidebarMenuNode> */
    public function active(?User $user = null): array
    {
        if (! Schema::hasTable('sidebar_menu_items')) {
            return [];
        }

        $currentUser = $user ?? auth()->user();
        $userLevelId = (int) ($currentUser?->user_level_id ?? 0);
        $userKey = ($currentUser?->id === 3 && $userLevelId === 0) ? 'superadmin' : ($userLevelId > 0 ? 'level_'.$userLevelId : 'guest');
        $canScorecard = (int) ($currentUser?->can_scorecard ?? 0);
        $userId = (int) ($currentUser?->id ?? 0);
        $cacheKey = self::CACHE_KEY.':u_'.$userId.':lvl_'.$userLevelId.':sc_'.$canScorecard;

        $serializedTree = Cache::rememberForever($cacheKey, function () use ($currentUser): array {
            $nodes = SidebarMenuItem::tree(true, $currentUser);

            return $this->serializeNodes($nodes);
        });

        $nodes = $this->unserializeNodes($serializedTree);
        $this->applyVerificationBadge($nodes, $currentUser);

        return $nodes;
    }

    public function forget(): void
    {
        Cache::flush();
    }

    public function forgetUser(?User $user = null): void
    {
        if (! $user) {
            Cache::flush();

            return;
        }

        $userLevelId = (int) ($user->user_level_id ?? 0);
        $userKey = ($user->id === 3 && $userLevelId === 0) ? 'superadmin' : ($userLevelId > 0 ? 'level_'.$userLevelId : 'guest');
        $canScorecard = (int) ($user->can_scorecard ?? 0);
        $userId = (int) $user->id;

        Cache::forget(self::CACHE_KEY.':u_'.$userId.':lvl_'.$userLevelId.':sc_'.$canScorecard);
        Cache::forget(self::CACHE_KEY.':'.$userKey.':sc_'.$canScorecard);
    }

    public function clearCache(): void
    {
        $this->forget();
    }

    /**
     * @param  list<SidebarMenuNode>  $nodes
     * @return list<array{item: array<string, mixed>, children: array}>
     */
    private function serializeNodes(array $nodes): array
    {
        return array_map(function (SidebarMenuNode $node): array {
            return [
                'item' => $node->item->toArray(),
                'children' => $this->serializeNodes($node->children),
            ];
        }, $nodes);
    }

    /**
     * @param  list<array{item: array<string, mixed>, children: array}>  $data
     * @return list<SidebarMenuNode>
     */
    private function unserializeNodes(array $data): array
    {
        return array_map(function (array $nodeData): SidebarMenuNode {
            $item = new SidebarMenuItem();
            $rawItem = $nodeData['item'] ?? [];
            if (isset($rawItem['user_levels']) && is_array($rawItem['user_levels'])) {
                $rawItem['user_levels'] = json_encode($rawItem['user_levels']);
            }
            if (isset($rawItem['user_ids']) && is_array($rawItem['user_ids'])) {
                $rawItem['user_ids'] = json_encode($rawItem['user_ids']);
            }
            $item->setRawAttributes($rawItem, true);
            $item->exists = true;

            $children = $this->unserializeNodes($nodeData['children'] ?? []);

            return new SidebarMenuNode($item, $children);
        }, $data);
    }

    /** @param list<SidebarMenuNode> $nodes */
    private function applyVerificationBadge(array $nodes, ?User $user): void
    {
        if (! $user || ! Schema::hasTable('users') || ! Schema::hasTable('ipc_semester')) {
            return;
        }

        $defaultYear = ApplicationSetting::defaultYear();
        $defaultSemester = ApplicationSetting::defaultSemester();
        $totalStaff = DB::table('users')
            ->where('users.supervisor_id', $user->id)
            ->where('users.is_status', 1)
            ->distinct()
            ->count('users.id');

        $staffQuery = DB::table('users')
            ->join('ipc_semester', 'ipc_semester.user_id', '=', 'users.id')
            ->where('users.supervisor_id', $user->id)
            ->where('users.is_status', 1)
            ->where('ipc_semester.year', $defaultYear)
            ->where('ipc_semester.semester', $defaultSemester);

        $verifiedStaff = (clone $staffQuery)
            ->whereNotNull('ipc_semester.date_verified')
            ->distinct()
            ->count('users.id');

        $badgeText = $verifiedStaff.' / '.$totalStaff;
        $this->setVerificationBadge($nodes, $badgeText);
    }

    /** @param list<SidebarMenuNode> $nodes */
    private function setVerificationBadge(array $nodes, string $badgeText): void
    {
        foreach ($nodes as $node) {
            $href = rtrim((string) ($node->item->href ?? ''), '/');
            if (in_array($href, ['/verification', '/ipcrf/verification'], true)) {
                $node->item->badge_text = $badgeText;
            }

            if ($node->children !== []) {
                $this->setVerificationBadge($node->children, $badgeText);
            }
        }
    }
}
