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
                <button type="button" wire:click="previousPage" rel="prev"
                    class="inline-flex cursor-pointer items-center rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground hover:border-primary/40 hover:bg-muted/20">
                    {{ __('Previous') }}
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
                            <button type="button" wire:click="gotoPage({{ $page }})"
                                class="inline-flex min-w-10 cursor-pointer items-center justify-center rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground hover:border-primary/40 hover:bg-muted/20 hover:text-primary">
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage" rel="next"
                    class="inline-flex cursor-pointer items-center rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground hover:border-primary/40 hover:bg-muted/20">
                    {{ __('Next') }}
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