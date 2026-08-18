const THEME_STORAGE_KEY = 'lgu:shadcn-theme';
const DEFAULT_THEME = 'neutral';

const THEMES = [
    {
        id: 'neutral',
        label: 'Neutral',
        description: 'Default balanced grayscale',
        swatches: ['var(--color-neutral-950)', 'var(--color-neutral-500)', 'var(--color-neutral-100)'],
        light: themeTokens('neutral', {
            primaryShade: 900,
            accentShade: 200,
            ringShade: 500,
        }),
        dark: themeTokens('neutral', {
            primaryShade: 100,
            accentShade: 800,
            ringShade: 400,
            primaryForeground: 'var(--color-neutral-950)',
            accentForeground: 'var(--color-neutral-50)',
        }),
    },
    {
        id: 'stone',
        label: 'Stone',
        description: 'Warm earthy surfaces',
        swatches: ['var(--color-stone-900)', 'var(--color-stone-500)', 'var(--color-stone-200)'],
        light: themeTokens('stone'),
        dark: themeTokens('stone', darkModeOverrides('stone')),
    },
    {
        id: 'zinc',
        label: 'Zinc',
        description: 'Cool industrial grayscale',
        swatches: ['var(--color-zinc-900)', 'var(--color-zinc-500)', 'var(--color-zinc-200)'],
        light: themeTokens('zinc'),
        dark: themeTokens('zinc', darkModeOverrides('zinc')),
    },
    {
        id: 'gray',
        label: 'Gray',
        description: 'Soft conventional neutral',
        swatches: ['var(--color-gray-900)', 'var(--color-gray-500)', 'var(--color-gray-200)'],
        light: themeTokens('gray'),
        dark: themeTokens('gray', darkModeOverrides('gray')),
    },
    {
        id: 'amber',
        label: 'Amber',
        description: 'Warm confident highlights',
        swatches: ['var(--color-amber-700)', 'var(--color-amber-500)', 'var(--color-amber-200)'],
        light: themeTokens('amber', {
            primaryForeground: 'var(--color-amber-50)',
            sidebarPrimaryForeground: 'var(--color-amber-50)',
        }),
        dark: themeTokens('amber', darkModeOverrides('amber', 'var(--color-amber-950)')),
    },
    {
        id: 'blue',
        label: 'Blue',
        description: 'Clean product dashboard feel',
        swatches: ['var(--color-blue-700)', 'var(--color-blue-500)', 'var(--color-blue-200)'],
        light: themeTokens('blue'),
        dark: themeTokens('blue', darkModeOverrides('blue', 'var(--color-blue-950)')),
    },
    {
        id: 'cyan',
        label: 'Cyan',
        description: 'Bright modern contrast',
        swatches: ['var(--color-cyan-700)', 'var(--color-cyan-500)', 'var(--color-cyan-200)'],
        light: themeTokens('cyan'),
        dark: themeTokens('cyan', darkModeOverrides('cyan', 'var(--color-cyan-950)')),
    },
    {
        id: 'emerald',
        label: 'Emerald',
        description: 'Fresh success-forward palette',
        swatches: ['var(--color-emerald-700)', 'var(--color-emerald-500)', 'var(--color-emerald-200)'],
        light: themeTokens('emerald'),
        dark: themeTokens('emerald', darkModeOverrides('emerald', 'var(--color-emerald-950)')),
    },
    {
        id: 'fuchsia',
        label: 'Fuchsia',
        description: 'Vibrant expressive accent',
        swatches: ['var(--color-fuchsia-700)', 'var(--color-fuchsia-500)', 'var(--color-fuchsia-200)'],
        light: themeTokens('fuchsia'),
        dark: themeTokens('fuchsia', darkModeOverrides('fuchsia', 'var(--color-fuchsia-950)')),
    },
    {
        id: 'green',
        label: 'Green',
        description: 'Natural and calm interface',
        swatches: ['var(--color-green-700)', 'var(--color-green-500)', 'var(--color-green-200)'],
        light: themeTokens('green'),
        dark: themeTokens('green', darkModeOverrides('green', 'var(--color-green-950)')),
    },
    {
        id: 'indigo',
        label: 'Indigo',
        description: 'Deep focused contrast',
        swatches: ['var(--color-indigo-700)', 'var(--color-indigo-500)', 'var(--color-indigo-200)'],
        light: themeTokens('indigo'),
        dark: themeTokens('indigo', darkModeOverrides('indigo', 'var(--color-indigo-950)')),
    },
    {
        id: 'lime',
        label: 'Lime',
        description: 'Energetic vivid controls',
        swatches: ['var(--color-lime-700)', 'var(--color-lime-500)', 'var(--color-lime-200)'],
        light: themeTokens('lime', {
            primaryForeground: 'var(--color-lime-50)',
            sidebarPrimaryForeground: 'var(--color-lime-50)',
        }),
        dark: themeTokens('lime', darkModeOverrides('lime', 'var(--color-lime-950)')),
    },
    {
        id: 'orange',
        label: 'Orange',
        description: 'Bold high-energy accenting',
        swatches: ['var(--color-orange-700)', 'var(--color-orange-500)', 'var(--color-orange-200)'],
        light: themeTokens('orange', {
            primaryForeground: 'var(--color-orange-50)',
            sidebarPrimaryForeground: 'var(--color-orange-50)',
        }),
        dark: themeTokens('orange', darkModeOverrides('orange', 'var(--color-orange-950)')),
    },
    {
        id: 'pink',
        label: 'Pink',
        description: 'Soft energetic UI tone',
        swatches: ['var(--color-pink-700)', 'var(--color-pink-500)', 'var(--color-pink-200)'],
        light: themeTokens('pink'),
        dark: themeTokens('pink', darkModeOverrides('pink', 'var(--color-pink-950)')),
    },
    {
        id: 'purple',
        label: 'Purple',
        description: 'Creative premium contrast',
        swatches: ['var(--color-purple-700)', 'var(--color-purple-500)', 'var(--color-purple-200)'],
        light: themeTokens('purple'),
        dark: themeTokens('purple', darkModeOverrides('purple', 'var(--color-purple-950)')),
    },
    {
        id: 'red',
        label: 'Red',
        description: 'Strong urgent emphasis',
        swatches: ['var(--color-red-700)', 'var(--color-red-500)', 'var(--color-red-200)'],
        light: themeTokens('red'),
        dark: themeTokens('red', darkModeOverrides('red', 'var(--color-red-950)')),
    },
    {
        id: 'rose',
        label: 'Rose',
        description: 'Elegant warm highlight',
        swatches: ['var(--color-rose-700)', 'var(--color-rose-500)', 'var(--color-rose-200)'],
        light: themeTokens('rose'),
        dark: themeTokens('rose', darkModeOverrides('rose', 'var(--color-rose-950)')),
    },
    {
        id: 'sky',
        label: 'Sky',
        description: 'Airy friendly palette',
        swatches: ['var(--color-sky-700)', 'var(--color-sky-500)', 'var(--color-sky-200)'],
        light: themeTokens('sky'),
        dark: themeTokens('sky', darkModeOverrides('sky', 'var(--color-sky-950)')),
    },
    {
        id: 'teal',
        label: 'Teal',
        description: 'Balanced cool accenting',
        swatches: ['var(--color-teal-700)', 'var(--color-teal-500)', 'var(--color-teal-200)'],
        light: themeTokens('teal'),
        dark: themeTokens('teal', darkModeOverrides('teal', 'var(--color-teal-950)')),
    },
    {
        id: 'violet',
        label: 'Violet',
        description: 'Deep expressive theme',
        swatches: ['var(--color-violet-700)', 'var(--color-violet-500)', 'var(--color-violet-200)'],
        light: themeTokens('violet'),
        dark: themeTokens('violet', darkModeOverrides('violet', 'var(--color-violet-950)')),
    },
    {
        id: 'yellow',
        label: 'Yellow',
        description: 'Bright optimistic interface',
        swatches: ['var(--color-yellow-700)', 'var(--color-yellow-500)', 'var(--color-yellow-200)'],
        light: themeTokens('yellow', {
            primaryForeground: 'var(--color-yellow-50)',
            sidebarPrimaryForeground: 'var(--color-yellow-50)',
        }),
        dark: themeTokens('yellow', darkModeOverrides('yellow', 'var(--color-yellow-950)')),
    },
    {
        id: 'mauve',
        label: 'Mauve',
        description: 'Muted editorial softness',
        swatches: ['var(--color-mauve-700)', 'var(--color-mauve-500)', 'var(--color-mauve-200)'],
        light: themeTokens('mauve'),
        dark: themeTokens('mauve', darkModeOverrides('mauve', 'var(--color-mauve-950)')),
    },
    {
        id: 'olive',
        label: 'Olive',
        description: 'Natural muted depth',
        swatches: ['var(--color-olive-700)', 'var(--color-olive-500)', 'var(--color-olive-200)'],
        light: themeTokens('olive'),
        dark: themeTokens('olive', darkModeOverrides('olive', 'var(--color-olive-950)')),
    },
    {
        id: 'mist',
        label: 'Mist',
        description: 'Cool atmospheric neutral',
        swatches: ['var(--color-mist-700)', 'var(--color-mist-500)', 'var(--color-mist-200)'],
        light: themeTokens('mist'),
        dark: themeTokens('mist', darkModeOverrides('mist', 'var(--color-mist-950)')),
    },
    {
        id: 'taupe',
        label: 'Taupe',
        description: 'Warm understated surfaces',
        swatches: ['var(--color-taupe-700)', 'var(--color-taupe-500)', 'var(--color-taupe-200)'],
        light: themeTokens('taupe'),
        dark: themeTokens('taupe', darkModeOverrides('taupe', 'var(--color-taupe-950)')),
    },
];

const THEMES_BY_ID = Object.fromEntries(THEMES.map((theme) => [theme.id, theme]));

function themeTokens(
    color,
    {
        primaryShade = 700,
        primaryForeground = 'var(--color-white)',
        secondaryShade = 100,
        secondaryForeground = `var(--color-${color}-900)`,
        accentShade = 100,
        accentForeground = `var(--color-${color}-900)`,
        ringShade = 400,
        sidebarPrimaryShade = primaryShade,
        sidebarPrimaryForeground = primaryForeground,
        sidebarAccentShade = accentShade,
        sidebarAccentForeground = accentForeground,
        chartShades = [500, 400, 300, 600, 700],
    } = {},
) {
    return {
        primary: `var(--color-${color}-${primaryShade})`,
        primaryForeground,
        secondary: `var(--color-${color}-${secondaryShade})`,
        secondaryForeground,
        accent: `var(--color-${color}-${accentShade})`,
        accentForeground,
        ring: `var(--color-${color}-${ringShade})`,
        sidebarPrimary: `var(--color-${color}-${sidebarPrimaryShade})`,
        sidebarPrimaryForeground,
        sidebarAccent: `var(--color-${color}-${sidebarAccentShade})`,
        sidebarAccentForeground,
        charts: chartShades.map((shade) => `var(--color-${color}-${shade})`),
    };
}

function darkModeOverrides(color, foreground = 'var(--color-black)') {
    return {
        primaryShade: 400,
        primaryForeground: foreground,
        secondaryShade: 900,
        secondaryForeground: `var(--color-${color}-50)`,
        accentShade: 900,
        accentForeground: `var(--color-${color}-50)`,
        ringShade: 400,
        sidebarPrimaryShade: 400,
        sidebarPrimaryForeground: foreground,
        sidebarAccentShade: 900,
        sidebarAccentForeground: `var(--color-${color}-50)`,
        chartShades: [400, 500, 600, 300, 700],
    };
}

function normalizeThemeId(themeId) {
    return THEMES_BY_ID[themeId] ? themeId : DEFAULT_THEME;
}

function getStoredTheme() {
    return normalizeThemeId(window.localStorage.getItem(THEME_STORAGE_KEY));
}

let appliedThemeId = null;
const legacyThemeProps = [
    '--theme-primary',
    '--theme-primary-foreground',
    '--theme-secondary',
    '--theme-secondary-foreground',
    '--theme-accent',
    '--theme-accent-foreground',
    '--theme-ring',
    '--theme-sidebar-primary',
    '--theme-sidebar-primary-foreground',
    '--theme-sidebar-accent',
    '--theme-sidebar-accent-foreground',
    '--theme-dark-primary',
    '--theme-dark-primary-foreground',
    '--theme-dark-secondary',
    '--theme-dark-secondary-foreground',
    '--theme-dark-accent',
    '--theme-dark-accent-foreground',
    '--theme-dark-ring',
    '--theme-dark-sidebar-primary',
    '--theme-dark-sidebar-primary-foreground',
    '--theme-dark-sidebar-accent',
    '--theme-dark-sidebar-accent-foreground',
];

for (let index = 1; index <= 5; index += 1) {
    legacyThemeProps.push(`--theme-chart-${index}`);
    legacyThemeProps.push(`--theme-dark-chart-${index}`);
}

function getThemeStyleElement() {
    let style = document.getElementById('app-theme-tokens');

    if (style) {
        return style;
    }

    style = document.createElement('style');
    style.id = 'app-theme-tokens';
    document.head.appendChild(style);

    return style;
}

function themeTokensCss(theme) {
    const pairs = [
        ['--theme-primary', theme.light.primary],
        ['--theme-primary-foreground', theme.light.primaryForeground],
        ['--theme-secondary', theme.light.secondary],
        ['--theme-secondary-foreground', theme.light.secondaryForeground],
        ['--theme-accent', theme.light.accent],
        ['--theme-accent-foreground', theme.light.accentForeground],
        ['--theme-ring', theme.light.ring],
        ['--theme-sidebar-primary', theme.light.sidebarPrimary],
        ['--theme-sidebar-primary-foreground', theme.light.sidebarPrimaryForeground],
        ['--theme-sidebar-accent', theme.light.sidebarAccent],
        ['--theme-sidebar-accent-foreground', theme.light.sidebarAccentForeground],
        ['--theme-dark-primary', theme.dark.primary],
        ['--theme-dark-primary-foreground', theme.dark.primaryForeground],
        ['--theme-dark-secondary', theme.dark.secondary],
        ['--theme-dark-secondary-foreground', theme.dark.secondaryForeground],
        ['--theme-dark-accent', theme.dark.accent],
        ['--theme-dark-accent-foreground', theme.dark.accentForeground],
        ['--theme-dark-ring', theme.dark.ring],
        ['--theme-dark-sidebar-primary', theme.dark.sidebarPrimary],
        ['--theme-dark-sidebar-primary-foreground', theme.dark.sidebarPrimaryForeground],
        ['--theme-dark-sidebar-accent', theme.dark.sidebarAccent],
        ['--theme-dark-sidebar-accent-foreground', theme.dark.sidebarAccentForeground],
    ];

    theme.light.charts.forEach((value, index) => {
        pairs.push([`--theme-chart-${index + 1}`, value]);
    });

    theme.dark.charts.forEach((value, index) => {
        pairs.push([`--theme-dark-chart-${index + 1}`, value]);
    });

    const declarations = pairs.map(([name, value]) => `${name}: ${value};`).join('');

    return `:root{${declarations}}`;
}

function applyThemeTokens(themeId) {
    const theme = THEMES_BY_ID[normalizeThemeId(themeId)];
    const root = document.documentElement;

    if (appliedThemeId === theme.id && root.dataset.theme === theme.id) {
        return;
    }

    root.dataset.theme = theme.id;

    const style = getThemeStyleElement();
    style.textContent = themeTokensCss(theme);

    legacyThemeProps.forEach((name) => {
        if (root.style.getPropertyValue(name)) {
            root.style.removeProperty(name);
        }
    });

    appliedThemeId = theme.id;
}

function setTheme(themeId) {
    const nextTheme = normalizeThemeId(themeId);
    window.localStorage.setItem(THEME_STORAGE_KEY, nextTheme);
    document.cookie = `lgu_theme=${encodeURIComponent(nextTheme)}; path=/; max-age=31536000; samesite=lax`;
    applyThemeTokens(nextTheme);

    window.dispatchEvent(new CustomEvent('app-theme-changed', {
        detail: {
            theme: nextTheme,
            label: THEMES_BY_ID[nextTheme].label,
        },
    }));

    return nextTheme;
}

function syncThemeFromStorage() {
    const stored = getStoredTheme();

    if (appliedThemeId === stored && document.documentElement.dataset.theme === stored) {
        return stored;
    }

    return setTheme(stored);
}

window.AppTheme = {
    defaultTheme: DEFAULT_THEME,
    storageKey: THEME_STORAGE_KEY,
    themes: THEMES,
    getTheme: () => normalizeThemeId(document.documentElement.dataset.theme || getStoredTheme()),
    getThemeLabel: (themeId = document.documentElement.dataset.theme) => THEMES_BY_ID[normalizeThemeId(themeId)].label,
    setTheme,
    sync: syncThemeFromStorage,
};

window.themePreferences = () => ({
    themes: THEMES,
    selectedTheme: window.AppTheme.getTheme(),
    init() {
        this.selectedTheme = window.AppTheme.getTheme();

        if (! this._onThemeChange) {
            this._onThemeChange = (event) => {
                this.selectedTheme = event.detail.theme;
            };

            window.addEventListener('app-theme-changed', this._onThemeChange);
        }
    },
    setTheme(themeId) {
        this.selectedTheme = window.AppTheme.setTheme(themeId);
    },
    isSelected(themeId) {
        return this.selectedTheme === themeId;
    },
    get currentTheme() {
        return THEMES_BY_ID[normalizeThemeId(this.selectedTheme)];
    },
    get currentThemeLabel() {
        return THEMES_BY_ID[normalizeThemeId(this.selectedTheme)].label;
    },
    get currentThemeSwatches() {
        return this.currentTheme.swatches;
    },
});

window.floatingThemeSelector = () => {
    const base = window.themePreferences();

    return {
        ...base,
        open: false,
        searchQuery: '',
        activeIndex: 0,
        init() {
            base.init.call(this);
            this.syncActiveIndex();

            this.$watch('searchQuery', () => {
                this.syncActiveIndex();
            });

            this.$watch('open', (isOpen) => {
                if (isOpen) {
                    this.syncActiveIndex();

                    this.$nextTick(() => {
                        this.$refs.search?.focus();
                        this.scrollActiveIntoView();
                    });

                    return;
                }

                this.searchQuery = '';
                this.syncActiveIndex();
            });
        },
        get filteredThemes() {
            const query = this.searchQuery.trim().toLowerCase();

            if (! query) {
                return this.themes;
            }

            return this.themes.filter((theme) => {
                return [
                    theme.id,
                    theme.label,
                    theme.description,
                ].some((value) => value.toLowerCase().includes(query));
            });
        },
        get activeThemeId() {
            return this.filteredThemes[this.activeIndex]?.id ?? null;
        },
        toggle() {
            this.open ? this.close() : this.openPanel();
        },
        openPanel() {
            this.open = true;
        },
        close({ restoreFocus = true } = {}) {
            this.open = false;

            if (restoreFocus) {
                this.$nextTick(() => this.$refs.trigger?.focus());
            }
        },
        syncActiveIndex() {
            const filtered = this.filteredThemes;

            if (! filtered.length) {
                this.activeIndex = 0;
                return;
            }

            const selectedIndex = filtered.findIndex((theme) => theme.id === this.selectedTheme);

            if (selectedIndex >= 0) {
                this.activeIndex = selectedIndex;
                return;
            }

            this.activeIndex = Math.min(this.activeIndex, filtered.length - 1);
        },
        moveActive(step) {
            if (! this.filteredThemes.length) {
                return;
            }

            if (! this.open) {
                this.openPanel();
                return;
            }

            const lastIndex = this.filteredThemes.length - 1;
            this.activeIndex = this.activeIndex + step;

            if (this.activeIndex < 0) {
                this.activeIndex = lastIndex;
            }

            if (this.activeIndex > lastIndex) {
                this.activeIndex = 0;
            }

            this.$nextTick(() => this.scrollActiveIntoView());
        },
        scrollActiveIntoView() {
            const activeId = this.activeThemeId;

            if (! activeId) {
                return;
            }

            const activeElement = this.$refs.results?.querySelector(`[data-theme-option="${activeId}"]`);
            activeElement?.scrollIntoView({ block: 'nearest' });
        },
        selectTheme(themeId) {
            this.setTheme(themeId);
            this.syncActiveIndex();
            this.close({ restoreFocus: true });
        },
        applyActive() {
            const activeTheme = this.filteredThemes[this.activeIndex];

            if (activeTheme) {
                this.selectTheme(activeTheme.id);
            }
        },
    };
};

syncThemeFromStorage();

function resolveFluxDarkPreference() {
    const stored = window.localStorage.getItem('flux.appearance') || 'system';

    if (stored === 'dark') {
        return true;
    }

    if (stored === 'light') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function ensureThemeAndAppearance() {
    const root = document.documentElement;
    const themeId = getStoredTheme();

    if (root.dataset.theme !== themeId) {
        root.dataset.theme = themeId;
    }

    applyThemeTokens(themeId);

    const shouldBeDark = resolveFluxDarkPreference();

    if (root.classList.contains('dark') !== shouldBeDark) {
        root.classList.toggle('dark', shouldBeDark);
    }
}

function updateSidebarActiveLinks() {
    const normalizePath = (path) => {
        if (path === '/') {
            return '/';
        }

        return path.replace(/\/+$/, '');
    };

    const currentPath = normalizePath(window.location.pathname);
    const activeClasses = [
        'bg-sidebar-primary',
        'text-sidebar-primary-foreground',
        'hover:bg-sidebar-primary',
        'hover:text-sidebar-primary-foreground',
    ];
    const inactiveClasses = [
        'text-sidebar-foreground',
        'hover:bg-sidebar-accent',
        'hover:text-sidebar-accent-foreground',
    ];

    document.querySelectorAll('[data-debug-sidebar="desktop"], [data-debug-sidebar="mobile"]').forEach((sidebar) => {
        sidebar.querySelectorAll('a[wire\\:navigate]').forEach((link) => {
            let targetPath = null;

            try {
                targetPath = normalizePath(new URL(link.getAttribute('href'), window.location.origin).pathname);
            } catch {
                targetPath = null;
            }

            const isActive = targetPath !== null && targetPath === currentPath;

            link.setAttribute('aria-current', isActive ? 'page' : 'false');
            activeClasses.forEach((cls) => link.classList.toggle(cls, isActive));
            inactiveClasses.forEach((cls) => link.classList.toggle(cls, !isActive));
        });
    });
}

function resizeTextarea(textarea) {
    if (! textarea || textarea.tagName !== 'TEXTAREA') {
        return;
    }

    textarea.style.overflow = 'hidden';
    textarea.style.resize = 'none';
    textarea.style.height = 'auto';
    textarea.style.height = `${textarea.scrollHeight}px`;
}

function initAutosizeTextareas(root = document) {
    root.querySelectorAll('textarea[data-autosize="true"]').forEach((textarea) => {
        if (textarea.dataset.autosizeBound === 'true') {
            resizeTextarea(textarea);
            return;
        }

        const handleInput = () => {
            requestAnimationFrame(() => resizeTextarea(textarea));
        };

        textarea.addEventListener('input', handleInput);
        textarea.addEventListener('focus', handleInput);
        textarea.addEventListener('keyup', handleInput);
        textarea.addEventListener('change', handleInput);
        textarea.dataset.autosizeBound = 'true';
        textarea.dataset.autosizeReady = 'true';
        resizeTextarea(textarea);
        setTimeout(() => resizeTextarea(textarea), 0);
    });
}

ensureThemeAndAppearance();
updateSidebarActiveLinks();
initAutosizeTextareas();

(() => {
    const setAppearanceCookie = () => {
        const stored = window.localStorage.getItem('flux.appearance') || 'system';
        document.cookie = `lgu_appearance=${encodeURIComponent(stored)}; path=/; max-age=31536000; samesite=lax`;
    };

    setAppearanceCookie();

    if (window.Flux?.applyAppearance && ! window.Flux.__lguCookiePatched) {
        const original = window.Flux.applyAppearance.bind(window.Flux);

        window.Flux.applyAppearance = (appearance) => {
            const result = original(appearance);
            setAppearanceCookie();
            ensureThemeAndAppearance();
            return result;
        };

        window.Flux.__lguCookiePatched = true;
    }
})();

new MutationObserver(() => {
    ensureThemeAndAppearance();
}).observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-theme'] });

document.addEventListener('livewire:navigated', () => {
    ensureThemeAndAppearance();
    updateSidebarActiveLinks();
    initAutosizeTextareas();
});

document.addEventListener('livewire:rendered', () => {
    initAutosizeTextareas();
});

const autosizeObserver = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
        mutation.addedNodes.forEach((node) => {
            if (! (node instanceof HTMLElement)) {
                return;
            }

            if (node.matches?.('textarea[data-autosize="true"]')) {
                initAutosizeTextareas(node.parentElement ?? document);
                return;
            }

            if (node.querySelector?.('textarea[data-autosize="true"]')) {
                initAutosizeTextareas(node);
            }
        });
    }
});

autosizeObserver.observe(document.body, { childList: true, subtree: true });

window.addEventListener('storage', (event) => {
    if (event.key === THEME_STORAGE_KEY) {
        applyThemeTokens(normalizeThemeId(event.newValue));
        window.dispatchEvent(new CustomEvent('app-theme-changed', {
            detail: {
                theme: normalizeThemeId(event.newValue),
                label: window.AppTheme.getThemeLabel(event.newValue),
            },
        }));
    }
});

// #region debug-point dark-sidebar-blink
(() => {
    const debug = window.__TRAE_DEBUG__;

    if (! debug?.url) {
        return;
    }

    const send = (hypothesisId, name, payload = {}) => {
        try {
            fetch(debug.url, {
                method: 'POST',
                headers: { 'content-type': 'application/json' },
                body: JSON.stringify({
                    sessionId: debug.sessionId,
                    hypothesisId,
                    name,
                    ts: Date.now(),
                    href: window.location.href,
                    payload,
                }),
                keepalive: true,
            });
        } catch {
            //
        }
    };

    const snapshotSidebar = () => {
        const el = document.querySelector('[data-debug-sidebar="desktop"]') || document.querySelector('[data-debug-sidebar="mobile"]');

        if (! el) {
            return { exists: false };
        }

        const computed = window.getComputedStyle(el);
        const openGroups = el.querySelectorAll('details[open]').length;
        const linkCount = el.querySelectorAll('a[href]').length;

        return {
            exists: true,
            bg: computed.backgroundColor,
            color: computed.color,
            openGroups,
            linkCount,
        };
    };

    const root = document.documentElement;
    let lastRoot = {
        className: root.className,
        theme: root.dataset.theme ?? null,
        style: root.getAttribute('style') ?? '',
    };

    send('H0', 'debug:init', {
        root: lastRoot,
        sidebar: snapshotSidebar(),
        storedAppearance: window.localStorage.getItem('flux.appearance'),
        storedTheme: window.localStorage.getItem('lgu:shadcn-theme'),
    });

    new MutationObserver(() => {
        const nextRoot = {
            className: root.className,
            theme: root.dataset.theme ?? null,
            style: root.getAttribute('style') ?? '',
        };

        if (
            nextRoot.className !== lastRoot.className
            || nextRoot.theme !== lastRoot.theme
            || nextRoot.style !== lastRoot.style
        ) {
            send('H1', 'root:mutated', {
                prev: lastRoot,
                next: nextRoot,
                sidebar: snapshotSidebar(),
            });

            lastRoot = nextRoot;
        }
    }).observe(root, { attributes: true, attributeFilter: ['class', 'data-theme', 'style'] });

    let navId = 0;

    document.addEventListener('livewire:navigating', () => {
        navId += 1;
        send('H2', 'livewire:navigating', { navId, sidebar: snapshotSidebar(), root: lastRoot });
    });

    document.addEventListener('livewire:navigated', () => {
        send('H2', 'livewire:navigated', { navId, sidebar: snapshotSidebar(), root: lastRoot });
    });
})();
// #endregion debug-point dark-sidebar-blink
