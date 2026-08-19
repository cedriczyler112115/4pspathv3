@php
    $route = request()->route();
    $routeName = (string) ($route?->getName() ?? '');
    $segments = collect(request()->segments());

    $labels = [
        'dashboard' => 'Dashboard',
        'administration' => 'Administration',
        'users' => 'Users',
        'annualtarget' => 'Annual Target',
        'settings' => 'Settings',
        'profile' => 'Profile',
        'security' => 'Security',
        'appearance' => 'Appearance',
        'sidebar-menu' => 'Sidebar Menu',
    ];

    $trail = collect();

    if ($routeName === 'dashboard' || $segments->isEmpty()) {
        $trail->push(['label' => '', 'url' => route('dashboard')]);
    } else {
        $trail->push(['label' => '', 'url' => route('dashboard')]);

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
        <flux:icon icon="map" class="size-4 shrink-0 text-red-600" />
        <ol class="flex min-w-0 items-center gap-2 whitespace-nowrap overflow-x-auto">
            @foreach ($trail as $index => $item)
                <li class="flex items-center gap-2">
                    @if ($index > 1)
                        <span class="text-red-500/70">/</span>
                    @endif

                    @if ($index === 0)
                        <a href="{{ $item['url'] }}" wire:navigate class="font-medium text-foreground hover:text-red-600" aria-label="{{ __('Dashboard') }}">
                            <span class="sr-only">{{ __('Dashboard') }}</span>
                        </a>
                    @elseif (! is_null($item['url']) && $index !== $trail->count() - 1)
                        <a href="{{ $item['url'] }}" wire:navigate class="font-medium text-foreground hover:text-red-600">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="font-semibold text-foreground">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
</nav>
