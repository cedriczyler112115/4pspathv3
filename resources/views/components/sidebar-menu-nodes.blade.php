@props([
    'nodes' => [],
    'depth' => 0,
])

@php
    $routeMatches = function (?string $routeKey): bool {
        if (blank($routeKey)) {
            return false;
        }

        if (request()->routeIs($routeKey)) {
            return true;
        }

        if (\Illuminate\Support\Str::endsWith($routeKey, '.index')) {
            $routeGroup = \Illuminate\Support\Str::beforeLast($routeKey, '.index');

            return request()->routeIs($routeGroup.'.*');
        }

        return false;
    };

    $branchHasActive = function (\App\Data\SidebarMenuNode $branch) use (&$branchHasActive, $routeMatches): bool {
        $branchItem = $branch->item;
        $branchHref = $branchItem->href;
        $branchPath = filled($branchHref) ? (parse_url($branchHref, PHP_URL_PATH) ?: $branchHref) : null;

        if ($routeMatches($branchItem->key)) {
            return true;
        }

        if (
            filled($branchPath)
            && ! \Illuminate\Support\Str::startsWith($branchHref, ['http://', 'https://'])
            && request()->is(ltrim($branchPath, '/'))
        ) {
            return true;
        }

        foreach ($branch->children as $childBranch) {
            if ($branchHasActive($childBranch)) {
                return true;
            }
        }

        return false;
    };
@endphp

@php
    $activeNodeId = null;

    foreach ($nodes as $node) {
        $item = $node->item;
        $children = $node->children;
        $hasChildren = count($children) > 0;

        $hrefValue = $item->href;
        $isExternal = filled($hrefValue) && \Illuminate\Support\Str::startsWith($hrefValue, ['http://', 'https://']);
        $path = filled($hrefValue) ? (parse_url($hrefValue, PHP_URL_PATH) ?: $hrefValue) : null;

        $current = false;
        if (filled($item->key)) {
            $current = $routeMatches($item->key);
        } elseif (filled($path) && ! $isExternal) {
            $current = request()->is(ltrim($path, '/'));
        }

        $hasCurrentChild = collect($children)->contains(fn (\App\Data\SidebarMenuNode $child): bool => $branchHasActive($child));

        if (($current || $hasCurrentChild) && $hasChildren) {
            $activeNodeId = $item->id;
            break;
        }
    }
@endphp

<ul role="list" class="space-y-1" x-data="{ openItem: {{ $activeNodeId ?? 'null' }} }">
    @foreach ($nodes as $node)
        @php($item = $node->item)
        @php($children = $node->children)
        @php($hasChildren = count($children) > 0)
        @php($icon = \App\Support\SidebarIcons::isValid($item->icon) ? $item->icon : null)

        @php($hrefValue = $item->href)
        @php($isExternal = filled($hrefValue) && \Illuminate\Support\Str::startsWith($hrefValue, ['http://', 'https://']))
        @php($url = filled($hrefValue) ? ($isExternal ? $hrefValue : url($hrefValue)) : null)
        @php($path = filled($hrefValue) ? (parse_url($hrefValue, PHP_URL_PATH) ?: $hrefValue) : null)

        @php($current = false)
        @if (filled($item->key))
            @php($current = $routeMatches($item->key))
        @elseif (filled($path) && ! $isExternal)
            @php($current = request()->is(ltrim($path, '/')))
        @endif

        @php($hasCurrentChild = collect($children)->contains(fn (\App\Data\SidebarMenuNode $child): bool => $branchHasActive($child)))

        @php($isExpanded = $current || $hasCurrentChild)
        @php($indentPx = $depth > 0 ? $depth * 12 : 0)
        @php($rowClass = $current ? 'bg-sidebar-primary text-sidebar-primary-foreground hover:bg-sidebar-primary hover:text-sidebar-primary-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground')
        @php($groupClass = $isExpanded ? 'bg-sidebar-accent/60 text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground')

        <li class="space-y-1">
            @if ($hasChildren)
                <details class="group rounded-lg" x-bind:open="openItem === {{ $item->id }} || {{ $isExpanded ? 'true' : 'false' }}">
                    <summary
                        class="flex list-none items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium transition marker:content-none cursor-pointer {{ $groupClass }}"
                        style="padding-left: {{ 12 + $indentPx }}px"
                        x-on:click.prevent="openItem = openItem === {{ $item->id }} ? null : {{ $item->id }}"
                    >
                        @if (filled($icon))
                            <flux:icon :icon="$icon" class="size-4 shrink-0" />
                        @endif
                        <span class="min-w-0 flex-1 truncate">{{ __($item->label) }}</span>
                        @if (filled($item->badge_text))
                            <span class="inline-flex items-center rounded-full bg-sidebar-background px-2 py-0.5 text-[11px] font-medium text-sidebar-foreground">
                                {{ $item->badge_text }}
                            </span>
                        @endif
                        <flux:icon icon="chevron-right" class="size-4 shrink-0 transition group-open:rotate-90" />
                    </summary>

                    @if (filled($url))
                        <a
                            href="{{ $url }}"
                            @if (! $isExternal) wire:navigate @endif
                            @if ($isExternal) target="_blank" rel="noreferrer noopener" @endif
                            class="mt-1 flex cursor-pointer items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium transition {{ $rowClass }}"
                            style="padding-left: {{ 24 + $indentPx }}px"
                            aria-current="{{ $current ? 'page' : 'false' }}"
                        >
                            <flux:icon icon="arrow-right" class="size-4 shrink-0 opacity-70" />
                            <span class="min-w-0 flex-1 truncate">{{ __('Open :label', ['label' => $item->label]) }}</span>
                        </a>
                    @endif

                    <div class="mt-1 border-s border-sidebar-border/70 ps-2">
                        <x-sidebar-menu-nodes :nodes="$children" :depth="$depth + 1" />
                    </div>
                </details>
            @else
                <a
                    href="{{ $url ?? '#' }}"
                    @if (! $isExternal && $url) wire:navigate @endif
                    @if ($isExternal) target="_blank" rel="noreferrer noopener" @endif
                    class="flex cursor-pointer items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium transition {{ $rowClass }}"
                    style="padding-left: {{ 12 + $indentPx }}px"
                    aria-current="{{ $current ? 'page' : 'false' }}"
                >
                    @if (filled($icon))
                        <flux:icon :icon="$icon" class="size-4 shrink-0" />
                    @endif
                    <span class="min-w-0 flex-1 truncate">{{ __($item->label) }}</span>
                    @if (filled($item->badge_text))
                        <span class="inline-flex items-center rounded-full bg-sidebar-accent px-2 py-0.5 text-[11px] font-medium text-sidebar-accent-foreground">
                            {{ $item->badge_text }}
                        </span>
                    @endif
                </a>
            @endif
        </li>
    @endforeach
</ul>
