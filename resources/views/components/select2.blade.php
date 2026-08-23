@props([
    'label' => null,
    'placeholder' => 'Select option',
    'options' => [],
    'target' => null,
    'searchable' => true,
    'containerClass' => 'w-full',
    'minWidth' => '140px',
])

@php
    $wireModel = $attributes->wire('model');
    $modelName = $wireModel->value();
    $targetName = $target ?? $modelName;

    $formattedOptions = [];
    foreach ($options as $opt) {
        if (is_array($opt)) {
            $val = (string)($opt['value'] ?? $opt['id'] ?? '');
            $lbl = (string)($opt['label'] ?? $opt['name'] ?? $val);
            $sub = (string)($opt['sublabel'] ?? $opt['position'] ?? '');
        } elseif (is_object($opt)) {
            $val = (string)($opt->value ?? $opt->id ?? $opt->target_year ?? '');
            $lbl = (string)($opt->label ?? $opt->name ?? $opt->target_year ?? $opt->division_name ?? $opt->section_name ?? $val);
            $sub = (string)($opt->position ?? '');
        } else {
            $val = (string)$opt;
            $lbl = (string)$opt;
            $sub = '';
        }
        $formattedOptions[] = [
            'value' => $val,
            'label' => $lbl,
            'sublabel' => $sub,
        ];
    }
    $optionsJson = json_encode($formattedOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
@endphp

<div x-data="{
        open: false,
        search: '',
        value: @entangle($attributes->wire('model')),
        options: {{ $optionsJson }},
        dropUp: false,
        get selectedOption() {
            return this.options.find(o => String(o.value) === String(this.value)) || null;
        },
        get filteredOptions() {
            if (!this.search.trim()) return this.options;
            const q = this.search.toLowerCase();
            return this.options.filter(o => o.label.toLowerCase().includes(q) || (o.sublabel && o.sublabel.toLowerCase().includes(q)));
        },
        select(val) {
            this.value = String(val ?? '');
            this.open = false;
            this.search = '';
        },
        toggle() {
            if (this.open) {
                this.open = false;
                return;
            }
            this.calculateDirection();
            this.open = true;
            this.$nextTick(() => {
                if (this.$refs.searchInput) this.$refs.searchInput.focus();
            });
        },
        calculateDirection() {
            if (!this.$refs.triggerBtn) return;
            const rect = this.$refs.triggerBtn.getBoundingClientRect();
            const dropHeight = 280;
            const spaceBelow = window.innerHeight - rect.bottom;
            this.dropUp = spaceBelow < dropHeight && rect.top > dropHeight;
        }
    }"
    x-effect="options = {{ $optionsJson }}"
    @click.outside="open = false"
    class="relative {{ $containerClass }}"
    style="min-width: {{ $minWidth }};">

    <flux:field>
        @if($label)
            <flux:label>{{ $label }}</flux:label>
        @endif

        <!-- Select2 Trigger Button -->
        <div x-ref="triggerBtn"
            @click="toggle()"
            class="flex h-[38px] min-h-[38px] max-h-[38px] w-full cursor-pointer items-center justify-between rounded-lg border border-border bg-background px-2.5 py-1.5 text-sm text-foreground shadow-xs transition-colors hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary/20 relative"
            style="height: 38px;"
            data-flux-control>
            <span x-text="selectedOption && selectedOption.value !== '' ? selectedOption.label : '{{ $placeholder }}'"
                :class="!selectedOption || selectedOption.value === '' ? 'text-muted-foreground' : 'font-medium text-foreground'"
                class="truncate pr-8"></span>

            <div class="absolute right-2.5 flex items-center justify-center pointer-events-none">
                <!-- Spinner when loading -->
                <div wire:loading wire:target="{{ $targetName }}" class="flex items-center">
                    <svg class="animate-spin size-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <!-- Chevron Down Icon -->
                <svg wire:loading.remove wire:target="{{ $targetName }}" class="size-4 text-muted-foreground transition-transform duration-200" :class="open ? 'rotate-180 text-primary' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>
    </flux:field>

    <!-- Inline Absolute Select2 Dropdown Overlay -->
    <div x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
        class="absolute left-0 w-full min-w-[180px] rounded-lg border border-border bg-card p-2 shadow-xl"
        style="z-index: 99999;"
        x-cloak>

        @if($searchable)
            <!-- Search Input Box inside Dropdown -->
            <div class="relative mb-2">
                <input x-ref="searchInput"
                    type="text"
                    x-model="search"
                    placeholder="{{ __('Search...') }}"
                    class="w-full rounded-md border border-border bg-background px-3 py-1.5 pl-8 text-xs text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                <svg class="absolute left-2.5 top-2 size-3.5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <button x-show="search.length > 0" @click="search = ''" type="button" class="absolute right-2 top-2 text-muted-foreground hover:text-foreground">
                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        <!-- Options List -->
        <div class="space-y-0.5 text-xs overscroll-contain" style="max-height: 240px; overflow-y: auto; overscroll-behavior: contain;">
            <!-- Default / Placeholder Option -->
            @if($placeholder)
                <div @click="select('')"
                    :class="!value || value === '' ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-muted/60 text-muted-foreground'"
                    class="flex cursor-pointer items-center justify-between rounded px-2.5 py-2 transition-colors">
                    <span>{{ $placeholder }}</span>
                    <template x-if="!value || value === ''">
                        <svg class="size-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                </div>
            @endif

            <!-- Options Loop -->
            <template x-for="option in filteredOptions" :key="option.value">
                <div @click="select(option.value)"
                    :class="String(value) === String(option.value) ? 'bg-primary/10 text-primary font-semibold' : 'hover:bg-muted/60 text-foreground'"
                    class="flex cursor-pointer items-center justify-between rounded px-2.5 py-2 transition-colors">
                    <div class="truncate">
                        <span x-text="option.label" class="block truncate"></span>
                        <template x-if="option.sublabel">
                            <span x-text="option.sublabel" class="block truncate text-[10px] opacity-75"></span>
                        </template>
                    </div>
                    <template x-if="String(value) === String(option.value)">
                        <svg class="size-3.5 text-primary shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                </div>
            </template>

            <!-- Empty State -->
            <div x-show="filteredOptions.length === 0" class="px-3 py-4 text-center text-xs text-muted-foreground">
                {{ __('No results found.') }}
            </div>
        </div>
    </div>
</div>
