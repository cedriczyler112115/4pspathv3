<?php

namespace Database\Seeders;

use App\Models\SidebarMenuItem;
use Illuminate\Database\Seeder;

class SidebarMenuItemSeeder extends Seeder
{
    public function run(): void
    {
        if (! SidebarMenuItem::query()->exists()) {
            $platform = SidebarMenuItem::query()->create([
                'label' => 'Platform',
                'sort_order' => 0,
                'is_active' => true,
            ]);

            SidebarMenuItem::query()->create([
                'parent_id' => $platform->id,
                'label' => 'Dashboard',
                'key' => 'dashboard',
                'href' => '/dashboard',
                'icon' => 'home',
                'sort_order' => 0,
                'is_active' => true,
            ]);

            SidebarMenuItem::query()->create([
                'parent_id' => $platform->id,
                'label' => 'Users List',
                'key' => 'administration.users.index',
                'href' => '/libraries/users/users-list',
                'icon' => 'users',
                'sort_order' => 10,
                'is_active' => true,
            ]);

            SidebarMenuItem::query()->create([
                'parent_id' => $platform->id,
                'label' => 'Appearance',
                'key' => 'appearance.edit',
                'href' => '/settings/appearance',
                'icon' => 'paint-brush',
                'sort_order' => 20,
                'is_active' => true,
            ]);

            SidebarMenuItem::query()->create([
                'parent_id' => $platform->id,
                'label' => 'Security',
                'key' => 'security.edit',
                'href' => '/myaccount/security',
                'icon' => 'shield-check',
                'sort_order' => 30,
                'is_active' => true,
            ]);

            SidebarMenuItem::query()->create([
                'parent_id' => $platform->id,
                'label' => 'Sidebar Menu',
                'key' => 'sidebar-menu.index',
                'href' => '/administration/sidebar-menu',
                'icon' => 'bars-3-bottom-left',
                'sort_order' => 40,
                'is_active' => true,
            ]);
        }
    }
}
