<div x-data="themePreferences()" x-init="init()" class="space-y-4">
    <div class="flex items-center justify-between gap-3 rounded-2xl border border-border bg-card p-4">
        <div>
            <p class="text-sm font-medium text-foreground">{{ __('Shadcn theme') }}</p>
            <p class="text-sm text-muted-foreground">{{ __('Stored in this browser and applied across the dashboard, settings, and menus.') }}</p>
        </div>

        <span class="rounded-full bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground" x-text="currentThemeLabel"></span>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <template x-for="theme in themes" :key="theme.id">
            <button
                type="button"
                class="rounded-2xl border bg-background p-4 text-left transition hover:border-primary/50 hover:shadow-sm"
                :class="isSelected(theme.id) ? 'border-primary ring-2 ring-ring/30' : 'border-border'"
                @click="setTheme(theme.id)"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-medium text-foreground" x-text="theme.label"></p>
                        <p class="mt-1 text-xs text-muted-foreground" x-text="theme.description"></p>
                    </div>

                    <span
                        x-show="isSelected(theme.id)"
                        x-cloak
                        class="rounded-full bg-primary px-2.5 py-1 text-[11px] font-medium text-primary-foreground"
                    >
                        {{ __('Active') }}
                    </span>
                </div>

                <div class="mt-4 flex items-center gap-2">
                    <template x-for="swatch in theme.swatches" :key="`${theme.id}-${swatch}`">
                        <span class="size-4 rounded-full border border-border shadow-sm" :style="`background:${swatch}`"></span>
                    </template>
                </div>
            </button>
        </template>
    </div>
</div>
