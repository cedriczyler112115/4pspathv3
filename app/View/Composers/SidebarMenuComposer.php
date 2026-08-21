<?php

namespace App\View\Composers;

use App\Services\SidebarMenuTree;
use Illuminate\Database\QueryException;
use Illuminate\View\View;

class SidebarMenuComposer
{
    public function compose(View $view, SidebarMenuTree $sidebarMenuTree): void
    {
        $nodes = [];

        try {
            $nodes = $sidebarMenuTree->active(auth()->user());
        } catch (QueryException) {
            $nodes = [];
        }

        $view->with('sidebarMenuNodes', $nodes);
    }
}
