<!DOCTYPE html>
@props([
    'title' => null,
    'maxWidth' => 'max-w-sm',
    'showHeader' => true,
])
@php
    $allowedThemes = [
        'neutral', 'stone', 'zinc', 'gray', 'amber', 'blue', 'cyan', 'emerald',
        'fuchsia', 'green', 'indigo', 'lime', 'orange', 'pink', 'purple', 'red',
        'rose', 'sky', 'teal', 'violet', 'yellow', 'mauve', 'olive', 'mist', 'taupe',
    ];

    $cookieTheme = request()->cookie('lgu_theme');
    $htmlTheme = in_array($cookieTheme, $allowedThemes, true) ? $cookieTheme : null;
    $htmlAppearance = request()->cookie('lgu_appearance');
    $htmlClass = $htmlAppearance === 'dark' ? 'dark' : '';
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $htmlClass }}" @if ($htmlTheme) data-theme="{{ $htmlTheme }}" @endif>
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-background flex min-h-svh flex-col items-center justify-center p-4 sm:p-6 md:p-10">
            <div class="flex w-full {{ $maxWidth }} flex-col gap-6">
                @if ($showHeader)
                    <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                        <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md">
                            <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                        </span>
                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                @endif

                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
