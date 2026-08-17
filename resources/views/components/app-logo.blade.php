@props([
    'sidebar' => false,
])

@if($sidebar)
    <a
        {{ $attributes->class('flex items-center gap-3 bg-transparent font-semibold tracking-tight !text-zinc-950 hover:bg-transparent hover:!text-zinc-950 dark:!text-white dark:hover:!text-white') }}
    >
        <span class="flex aspect-square size-8 items-center justify-center bg-transparent">
            <x-app-logo-icon class="size-5 !fill-zinc-950 dark:!fill-white" />
        </span>
        <span class="text-base !text-zinc-950 dark:!text-white">{{ config('app.name') }}</span>
    </a>
@else
    <flux:brand :name="config('app.name')" {{ $attributes->class('[&>div:last-child]:text-primary') }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center text-foreground">
            <x-app-logo-icon class="size-5 fill-current" />
        </x-slot>
    </flux:brand>
@endif
