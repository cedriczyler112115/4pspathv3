@php
    $showAllMode = method_exists($this, 'isAllPerPage') ? $this->isAllPerPage() : ((int) $paginator->perPage() === 1 && $paginator->lastPage() === 1);
@endphp

<div class="flex flex-col items-center justify-between gap-3 sm:flex-row {{ $paginationClass }}">
    <div class="text-xs text-muted-foreground">
        @if ($paginator->total() > 0)
            @if ($showAllMode)
                {{ __('Showing all :total targets', ['total' => $paginator->total()]) }}
            @else
                {{ __('Showing targets :from-:to of :total', [
                    'from' => $paginator->firstItem(),
                    'to' => min($paginator->currentPage() * $paginator->perPage(), $paginator->total()),
                    'total' => $paginator->total(),
                ]) }}
            @endif
        @else
            {{ __('No targets found') }}
        @endif
    </div>

    @if (! $showAllMode)
        <nav class="flex flex-wrap items-center justify-center gap-1" aria-label="{{ __('Target pagination') }}">
            <flux:button variant="ghost" size="sm" type="button" wire:click="setPage(1)"
                :disabled="$paginator->onFirstPage()" aria-label="{{ __('First page') }}">
                {{ __('First') }}
            </flux:button>
            <flux:button variant="ghost" size="sm" type="button" wire:click="previousPage"
                :disabled="$paginator->onFirstPage()" aria-label="{{ __('Previous page') }}">
                {{ __('Previous') }}
            </flux:button>

            @foreach ($this->paginationElements($paginator) as $element)
                @if (is_string($element))
                    <span wire:key="{{ $keyPrefix }}-pagination-{{ $element }}"
                        class="px-1.5 text-sm text-muted-foreground" aria-hidden="true">&hellip;</span>
                @else
                    <flux:button wire:key="{{ $keyPrefix }}-pagination-page-{{ $element }}" size="sm" type="button"
                        wire:click="setPage({{ $element }})"
                        variant="{{ $element === $paginator->currentPage() ? 'primary' : 'ghost' }}"
                        :aria-current="$element === $paginator->currentPage() ? 'page' : null"
                        aria-label="{{ __('Page :page', ['page' => $element]) }}">
                        {{ $element }}
                    </flux:button>
                @endif
            @endforeach

            <flux:button variant="ghost" size="sm" type="button" wire:click="nextPage"
                :disabled="!$paginator->hasMorePages()" aria-label="{{ __('Next page') }}">
                {{ __('Next') }}
            </flux:button>
            <flux:button variant="ghost" size="sm" type="button" wire:click="setPage({{ $paginator->lastPage() }})"
                :disabled="!$paginator->hasMorePages()" aria-label="{{ __('Last page') }}">
                {{ __('Last') }}
            </flux:button>
        </nav>
    @endif
</div>
