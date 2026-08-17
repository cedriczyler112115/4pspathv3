@props([
    'title' => null,
    'maxWidth' => 'max-w-sm',
    'showHeader' => true,
])

<x-layouts::auth.simple :title="$title" :max-width="$maxWidth" :show-header="$showHeader">
    {{ $slot }}
</x-layouts::auth.simple>

