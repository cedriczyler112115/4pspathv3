@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-muted-foreground">
            {{ __('Showing') }} {{ $paginator->firstItem() ?? 0 }} {{ __('to') }} {{ $paginator->lastItem() ?? 0 }}
            {{ __('of') }} {{ $paginator->total() }} {{ __('records') }}
        </div>

        <div class="flex flex-wrap items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span
                    class="inline-flex cursor-not-allowed items-center rounded-lg border border-border bg-muted px-3 py-2 text-sm text-muted-foreground">
                    {{ __('Previous') }}
                </span>
            @else
                <button type="button" wire:click="previousPage" rel="prev" wire:loading.attr="disabled" wire:target="previousPage"
                    class="inline-flex cursor-pointer items-center rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground hover:border-primary/40 hover:bg-muted/20">
                    <span wire:loading.remove wire:target="previousPage">{{ __('Previous') }}</span>
                    <span wire:loading wire:target="previousPage" class="inline-flex items-center gap-1.5">
                        <svg class="animate-spin size-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('Previous') }}
                    </span>
                </button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-sm text-muted-foreground">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                class="inline-flex min-w-10 items-center justify-center rounded-lg border border-primary bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground">
                                {{ $page }}
                            </span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }})" wire:loading.attr="disabled" wire:target="gotoPage({{ $page }})"
                                class="inline-flex min-w-10 cursor-pointer items-center justify-center rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground hover:border-primary/40 hover:bg-muted/20 hover:text-primary">
                                <span wire:loading.remove wire:target="gotoPage({{ $page }})">{{ $page }}</span>
                                <span wire:loading wire:target="gotoPage({{ $page }})" class="flex items-center justify-center">
                                    <svg class="animate-spin size-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage" rel="next" wire:loading.attr="disabled" wire:target="nextPage"
                    class="inline-flex cursor-pointer items-center rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground hover:border-primary/40 hover:bg-muted/20">
                    <span wire:loading.remove wire:target="nextPage">{{ __('Next') }}</span>
                    <span wire:loading wire:target="nextPage" class="inline-flex items-center gap-1.5">
                        <svg class="animate-spin size-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('Next') }}
                    </span>
                </button>
            @else
                <span
                    class="inline-flex cursor-not-allowed items-center rounded-lg border border-border bg-muted px-3 py-2 text-sm text-muted-foreground">
                    {{ __('Next') }}
                </span>
            @endif
        </div>
    </nav>
@endif