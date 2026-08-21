<?php

namespace App\Observers;

use App\Models\SidebarMenuItem;
use App\Services\SidebarMenuTree;

final class SidebarMenuItemObserver
{
    public function __construct(private readonly SidebarMenuTree $tree) {}

    public function created(SidebarMenuItem $item): void
    {
        $this->tree->forget();
    }

    public function updated(SidebarMenuItem $item): void
    {
        $this->tree->forget();
    }

    public function deleted(SidebarMenuItem $item): void
    {
        $this->tree->forget();
    }
}
