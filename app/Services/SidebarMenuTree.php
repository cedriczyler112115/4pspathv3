<?php

namespace App\Services;

use App\Data\SidebarMenuNode;
use App\Models\SidebarMenuItem;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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
        $cacheKey = self::CACHE_KEY.':'.$userKey;

        $serializedTree = Cache::rememberForever($cacheKey, function () use ($currentUser): array {
            $nodes = SidebarMenuItem::tree(true, $currentUser);

            return $this->serializeNodes($nodes);
        });

        return $this->unserializeNodes($serializedTree);
    }

    public function forget(): void
    {
        Cache::flush();
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
            $item->setRawAttributes($nodeData['item'] ?? [], true);
            $item->exists = true;

            $children = $this->unserializeNodes($nodeData['children'] ?? []);

            return new SidebarMenuNode($item, $children);
        }, $data);
    }
}
