<?php

namespace App\View\Composers;

use App\Models\SidebarMenuItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SidebarMenuComposer
{
    public function compose(View $view): void
    {
        $nodes = [];

        if (Schema::hasTable('sidebar_menu_items')) {
            try {
                $nodes = SidebarMenuItem::tree();
            } catch (QueryException) {
                $nodes = [];
            }
        }

        $view->with('sidebarMenuNodes', $nodes);
    }
}
