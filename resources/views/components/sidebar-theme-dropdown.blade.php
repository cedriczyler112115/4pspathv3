<div
    x-data="floatingThemeSelector()"
    x-init="init()"
    x-on:keydown.escape.window="close()"
    class="pointer-events-none fixed bottom-4 left-4 z-40 sm:bottom-6 sm:left-6"
>
    <div class="pointer-events-auto relative flex flex-col items-start gap-3">
        <div
            id="floating-theme-selector-panel"
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-2 scale-95 opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 scale-100 opacity-100"
            x-transition:leave-end="translate-y-2 scale-95 opacity-0"
            x-on:click.outside="close()"
            class="w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-border bg-popover/95 text-popover-foreground shadow-2xl backdrop-blur supports-[backdrop-filter]:bg-popover/90"
            role="dialog"
            aria-label="{{ __('Theme selector') }}"
        >
            <div class="border-b border-border px-4 py-3">
                <div class="flex items-start gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-border bg-muted text-foreground">
                        <flux:icon icon="paint-brush" class="size-5" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-foreground">{{ __('Shadcn themes') }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ __('Search and switch the active color palette in real time.') }}</p>
                    </div>
                </div>

                <div class="relative mt-3">
                    <label for="theme-selector-search" class="sr-only">{{ __('Search themes') }}</label>
                    <flux:icon icon="magnifying-glass" class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        id="theme-selector-search"
                        x-ref="search"
                        x-model="searchQuery"
                        x-on:keydown.arrow-down.prevent="moveActive(1)"
                        x-on:keydown.arrow-up.prevent="moveActive(-1)"
                        x-on:keydown.enter.prevent="applyActive()"
                        type="search"
                        autocomplete="off"
                        placeholder="{{ __('Search themes...') }}"
                        class="h-10 w-full rounded-xl border border-input bg-background ps-10 pe-3 text-sm text-foreground outline-none transition placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring"
                    />
                </div>
            </div>

            <div
                x-ref="results"
                class="max-h-80 overflow-y-auto p-2"
                role="listbox"
                :aria-activedescendant="activeThemeId ? `theme-option-${activeThemeId}` : null"
            >
                <template x-if="filteredThemes.length === 0">
                    <div class="rounded-xl border border-dashed border-border bg-muted/30 px-4 py-8 text-center text-sm text-muted-foreground">
                        {{ __('No themes match your search.') }}
                    </div>
                </template>

                <template x-for="(theme, index) in filteredThemes" :key="theme.id">
                    <button
                        type="button"
                        :id="`theme-option-${theme.id}`"
                        :data-theme-option="theme.id"
                        role="option"
                        :aria-selected="isSelected(theme.id)"
                        :tabindex="open ? 0 : -1"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        :class="{
                            'bg-muted ring-1 ring-ring/30': isSelected(theme.id),
                            'bg-accent/70': activeIndex === index && ! isSelected(theme.id),
                        }"
                        x-on:mouseenter="activeIndex = index"
                        x-on:focus="activeIndex = index"
                        x-on:keydown.arrow-down.prevent="moveActive(1)"
                        x-on:keydown.arrow-up.prevent="moveActive(-1)"
                        x-on:keydown.enter.prevent="selectTheme(theme.id)"
                        x-on:click="selectTheme(theme.id)"
                    >
                        <div class="flex items-center gap-1">
                            <template x-for="swatch in theme.swatches" :key="`${theme.id}-${swatch}`">
                                <span class="size-3 rounded-full border border-border" :style="`background:${swatch}`"></span>
                            </template>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-foreground" x-text="theme.label"></div>
                            <div class="truncate text-xs text-muted-foreground" x-text="theme.description"></div>
                        </div>

                        <flux:icon x-show="isSelected(theme.id)" x-cloak icon="check" class="size-4 shrink-0 text-primary" />
                    </button>
                </template>
            </div>
        </div>

        <button
            x-ref="trigger"
            type="button"
            x-on:click="toggle()"
            x-on:keydown.arrow-up.prevent="openPanel()"
            x-on:keydown.arrow-down.prevent="openPanel()"
            :aria-expanded="open.toString()"
            aria-controls="floating-theme-selector-panel"
            class="inline-flex h-14 max-w-[calc(100vw-2rem)] items-center gap-3 rounded-full border border-border bg-background/95 px-4 text-left text-sm text-foreground shadow-xl backdrop-blur transition hover:-translate-y-0.5 hover:bg-accent/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            aria-label="{{ __('Open theme selector') }}"
        >
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                <flux:icon icon="paint-brush" class="size-4" />
            </span>

            <span class="hidden min-w-0 sm:block">
                <span class="block truncate text-xs uppercase tracking-[0.18em] text-muted-foreground">{{ __('Theme') }}</span>
                <span class="block truncate font-medium" x-text="currentThemeLabel"></span>
            </span>

            <span class="hidden items-center gap-1 sm:flex">
                <template x-for="swatch in currentThemeSwatches" :key="swatch">
                    <span class="size-2.5 rounded-full border border-border" :style="`background:${swatch}`"></span>
                </template>
            </span>
        </button>
    </div>
</div>
