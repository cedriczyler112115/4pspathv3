<!DOCTYPE html>
@php
    $allowedThemes = [
        'neutral',
        'stone',
        'zinc',
        'gray',
        'amber',
        'blue',
        'cyan',
        'emerald',
        'fuchsia',
        'green',
        'indigo',
        'lime',
        'orange',
        'pink',
        'purple',
        'red',
        'rose',
        'sky',
        'teal',
        'violet',
        'yellow',
        'mauve',
        'olive',
        'mist',
        'taupe',
    ];

    $cookieTheme = request()->cookie('lgu_theme');
    $htmlTheme = in_array($cookieTheme, $allowedThemes, true) ? $cookieTheme : null;
    $htmlAppearance = request()->cookie('lgu_appearance');
    $htmlClass = $htmlAppearance === 'dark' ? 'dark' : '';
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $htmlClass }}" @if ($htmlTheme)
data-theme="{{ $htmlTheme }}" @endif>

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-background text-foreground">
    @php
        $menuNodes = $sidebarMenuNodes ?? [];

        if (empty($menuNodes) && \Illuminate\Support\Facades\Schema::hasTable('sidebar_menu_items')) {
            $menuNodes = app(\App\Services\SidebarMenuTree::class)->active(auth()->user());
        }
    @endphp

    <div x-data="{ sidebarOpen: false }" class="flex min-h-screen w-full text-[0.98rem]">
        @persist('app-sidebar')
        <aside data-debug-sidebar="desktop"
            class="hidden sm:flex sticky top-0 h-dvh min-h-screen w-64 shrink-0 self-stretch flex-col border-e border-sidebar-border bg-sidebar text-sidebar-foreground">
            <div class="flex h-10 shrink-0 items-center gap-2 border-b px-2.5">
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            </div>

            <nav class="flex-1 overflow-y-auto px-2 pb-2.5" style="margin-top: 10px;">
                <div class="space-y-2.5">
                    <div class="space-y-0.5">
                        @if (!empty($menuNodes))
                            <x-sidebar-menu-nodes :nodes="$menuNodes" />
                        @else
                            <a href="{{ route('dashboard') }}" wire:navigate
                                class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.25 text-sm font-medium text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                                {{ __('Dashboard') }}
                            </a>
                        @endif
                    </div>
                </div>
            </nav>

            <div class="shrink-0 border-t border-sidebar-border px-2 py-3">
                <x-sidebar-theme-dropdown />
            </div>

        </aside>
        @endpersist

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="sticky top-0 z-50 hidden h-10 items-center justify-between gap-2 border-b border-border bg-background bg-white dark:bg-zinc-900 px-2.5 sm:flex">
                <div class="min-w-0 overflow-hidden">
                    <x-breadcrumbs />
                </div>

                <div class="flex items-center gap-2">
                    @stack('header_actions')
                    <div x-data class="flex items-center gap-0.5 rounded-md border border-border bg-background p-0.5">
                        <button type="button"
                            class="inline-flex items-center justify-center rounded-md px-1 py-0.5 text-xs transition"
                            x-on:click="$flux.appearance = 'light'"
                            :class="$flux.appearance === 'light' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                            aria-label="{{ __('Light mode') }}">
                            <flux:icon icon="sun" class="size-4" />
                        </button>
                        <button type="button"
                            class="inline-flex items-center justify-center rounded-md px-1 py-0.5 text-xs transition"
                            x-on:click="$flux.appearance = 'dark'"
                            :class="$flux.appearance === 'dark' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                            aria-label="{{ __('Dark mode') }}">
                            <flux:icon icon="moon" class="size-4" />
                        </button>
                    </div>

                    <flux:dropdown position="bottom" align="end" x-data="themePreferences()" x-init="init()">
                        <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                        <flux:menu>
                            <flux:menu.radio.group>
                                <div class="p-0 text-sm font-normal">
                                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                        <flux:avatar :name="auth()->user()->name"
                                            :initials="auth()->user()->initials()" />
                                        <div class="grid flex-1 text-start text-sm leading-tight">
                                            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                        </div>
                                    </div>
                                </div>
                            </flux:menu.radio.group>

                            <flux:menu.separator />

                            <flux:menu.radio.group>
                                <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>
                                    {{ __('Profile') }}
                                </flux:menu.item>
                                <flux:menu.item :href="route('security.edit')" icon="shield-check" wire:navigate>
                                    {{ __('Security') }}
                                </flux:menu.item>
                                <flux:menu.item :href="route('sidebar-menu.index')" icon="list-bullet" wire:navigate>
                                    {{ __('Sidebar Menu') }}
                                </flux:menu.item>
                            </flux:menu.radio.group>

                            <flux:menu.separator />

                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                    class="w-full cursor-pointer" data-test="logout-button">
                                    {{ __('Log out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </header>

            <header
                class="sticky top-0 z-50 flex h-10 items-center justify-between gap-2 border-b border-border bg-background bg-white dark:bg-zinc-900 px-2 sm:hidden">
                <button type="button"
                    class="inline-flex items-center justify-center rounded-md border border-border bg-background px-2 py-1 text-xs font-medium shadow-sm hover:bg-muted"
                    x-on:click="sidebarOpen = true" aria-label="{{ __('Open sidebar') }}">
                    <flux:icon icon="bars-2" class="size-4" />
                </button>

                <div
                    class="flex items-center gap-2 font-semibold text-sidebar-foreground hover:text-sidebar-accent-foreground">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                </div>

                <div class="flex items-center gap-1.5">
                    <div x-data class="flex items-center gap-0.5 rounded-md border border-border bg-background p-0.5">
                        <button type="button"
                            class="inline-flex items-center justify-center rounded-md px-1 py-0.5 text-xs transition"
                            x-on:click="$flux.appearance = 'light'"
                            :class="$flux.appearance === 'light' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                            aria-label="{{ __('Light mode') }}">
                            <flux:icon icon="sun" class="size-4" />
                        </button>
                        <button type="button"
                            class="inline-flex items-center justify-center rounded-md px-1 py-0.5 text-xs transition"
                            x-on:click="$flux.appearance = 'dark'"
                            :class="$flux.appearance === 'dark' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                            aria-label="{{ __('Dark mode') }}">
                            <flux:icon icon="moon" class="size-4" />
                        </button>
                    </div>

                    <flux:dropdown position="bottom" align="end" x-data="themePreferences()" x-init="init()">
                        <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                        <flux:menu>
                            <flux:menu.radio.group>
                                <div class="p-0 text-sm font-normal">
                                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                        <flux:avatar :name="auth()->user()->name"
                                            :initials="auth()->user()->initials()" />
                                        <div class="grid flex-1 text-start text-sm leading-tight">
                                            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                        </div>
                                    </div>
                                </div>
                            </flux:menu.radio.group>

                            <flux:menu.separator />

                            <flux:menu.radio.group>
                                <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>
                                    {{ __('Profile') }}
                                </flux:menu.item>
                                <flux:menu.item :href="route('security.edit')" icon="shield-check" wire:navigate>
                                    {{ __('Security') }}
                                </flux:menu.item>
                                <flux:menu.item :href="route('sidebar-menu.index')" icon="list-bullet" wire:navigate>
                                    {{ __('Sidebar Menu') }}
                                </flux:menu.item>
                            </flux:menu.radio.group>

                            <flux:menu.separator />

                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                    class="w-full cursor-pointer" data-test="logout-button">
                                    {{ __('Log out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </header>

            <main class="w-full max-w-full flex-1 min-w-0 px-3 py-2 sm:px-4 sm:py-3 lg:px-6 lg:py-4">
                {{ $slot }}
            </main>
        </div>

        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-[60] flex sm:hidden" aria-hidden="true">
            <div class="fixed inset-0 bg-black/50" x-on:click="sidebarOpen = false"></div>

            <aside data-debug-sidebar="mobile"
                class="relative flex h-full w-48 max-w-[85vw] flex-col bg-sidebar text-sidebar-foreground">
                <div class="flex h-10 shrink-0 items-center justify-between gap-2 border-b-2 border-red-500 px-2.5">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />

                    <button type="button"
                        class="inline-flex items-center justify-center rounded-md border border-border bg-background px-2 py-1 text-xs font-medium shadow-sm hover:bg-muted"
                        x-on:click="sidebarOpen = false" aria-label="{{ __('Close sidebar') }}">
                        <flux:icon icon="x-mark" class="size-4" />
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto px-2 pb-2.5 pt-2">
                    <div class="space-y-0.5">
                        @if (!empty($menuNodes))
                            <x-sidebar-menu-nodes :nodes="$menuNodes" />
                        @else
                            <a href="{{ route('dashboard') }}" wire:navigate
                                class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1 text-xs font-medium text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                                {{ __('Dashboard') }}
                            </a>
                        @endif
                    </div>
                </nav>

            </aside>
        </div>
    </div>

    @persist('toast')
    <flux:toast.group position="top right">
        <flux:toast position="top right" />
    </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>