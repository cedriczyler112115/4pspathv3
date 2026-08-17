@props([
    'heading' => null,
    'subheading' => null,
    'contentClass' => 'mt-3 w-full max-w-lg',
])

<div class="w-full">
    <div class="w-full">
        <flux:heading>{{ $heading }}</flux:heading>
        <flux:subheading>{{ $subheading }}</flux:subheading>

        <div class="{{ $contentClass }}">
            {{ $slot }}
        </div>
    </div>
</div>
