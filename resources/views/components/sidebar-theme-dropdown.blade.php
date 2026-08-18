<div x-data="themePreferences()" x-init="init()" class="rounded-xl border border-sidebar-border bg-sidebar-accent/20 p-3">
    <div class="flex items-center justify-between gap-2">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sidebar-foreground/70">{{ __('Theme') }}</p>
            <p class="truncate text-sm font-medium text-sidebar-foreground" x-text="currentThemeLabel"></p>
        </div>

        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-sidebar-primary text-sidebar-primary-foreground">
            <flux:icon icon="paint-brush" class="size-4" />
        </span>
    </div>

    <div class="mt-3">
        <label for="sidebar-theme-select" class="sr-only">{{ __('Select theme') }}</label>
        <select
            id="sidebar-theme-select"
            class="w-full rounded-lg border border-sidebar-border bg-sidebar px-3 py-2 text-sm text-sidebar-foreground outline-none transition focus:border-sidebar-primary focus:ring-2 focus:ring-sidebar-primary/30"
            x-model="selectedTheme"
            x-on:change="setTheme($event.target.value)"
        >
            <template x-for="theme in themes" :key="theme.id">
                <option :value="theme.id" x-text="theme.label"></option>
            </template>
        </select>
    </div>

    <div class="mt-3 flex items-center gap-1.5">
        <template x-for="swatch in currentThemeSwatches" :key="swatch">
            <span class="size-3 rounded-full border border-sidebar-border shadow-sm" :style="`background:${swatch}`"></span>
        </template>
    </div>
</div>
