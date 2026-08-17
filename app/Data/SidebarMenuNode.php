<?php

namespace App\Data;

use App\Models\SidebarMenuItem;

final readonly class SidebarMenuNode
{
    /**
     * @param  list<self>  $children
     */
    public function __construct(
        public SidebarMenuItem $item,
        public array $children = [],
    ) {}
}
