@php
    $route = request()->route();
    $routeName = (string) ($route?->getName() ?? '');
    $segments = collect(request()->segments());

    $labels = [
        'dashboard' => 'Dashboard',
        'administration' => 'Administration',
        'users' => 'Users',
        'annualtarget' => 'Annual Target',
        'rpmo-management' => 'RPMO Management',
        'harmonized-ipc' => 'Harmonized IPC',
        'harmonized-staff' => 'Harmonized Staff',
        'settings' => 'Settings',
        'profile' => 'Profile',
        'security' => 'Security',
        'appearance' => 'Appearance',
        'sidebar-menu' => 'Sidebar Menu',
    ];

    $trail = collect();

    if ($routeName === 'dashboard' || $segments->isEmpty()) {
        $trail->push(['label' => __('Dashboard'), 'url' => route('dashboard')]);
    } else {
        $trail->push(['label' => __('Dashboard'), 'url' => route('dashboard')]);

        $path = '';
        foreach ($segments as $segment) {
            $path .= '/'.$segment;
            $label = $labels[$segment] ?? \Illuminate\Support\Str::of($segment)->replace(['-', '_'], ' ')->title()->toString();
            $trail->push([
                'label' => __($label),
                'url' => $path === request()->path() ? null : url($path),
            ]);
        }
    }
@endphp

<nav aria-label="{{ __('Breadcrumb') }}" class="flex items-center gap-2 text-sm text-muted-foreground">
    <ol class="flex min-w-0 items-center gap-1.5 whitespace-nowrap overflow-x-auto">
        @foreach ($trail as $index => $item)
            <li class="flex items-center gap-1.5">
                @if ($index > 0)
                    <span class="text-xs text-muted-foreground/60">/</span>
                @endif

                @if ($index === 0)
                    @if ($trail->count() === 1)
                        <span class="flex items-center gap-1.5 font-semibold text-foreground">
                            <flux:icon icon="map" class="size-4 shrink-0 text-red-600" />
                            <span>{{ $item['label'] }}</span>
                        </span>
                    @else
                        <a href="{{ $item['url'] }}" wire:navigate class="flex items-center gap-1.5 font-medium text-muted-foreground hover:text-red-600 transition-colors">
                            <flux:icon icon="map" class="size-4 shrink-0 text-red-600" />
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endif
                @elseif (! is_null($item['url']) && $index !== $trail->count() - 1)
                    <a href="{{ $item['url'] }}" wire:navigate class="font-medium text-muted-foreground hover:text-red-600 transition-colors">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="font-semibold text-foreground">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
