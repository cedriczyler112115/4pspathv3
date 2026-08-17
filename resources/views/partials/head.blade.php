<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<meta name="color-scheme" content="light dark" />
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

<script>
    (() => {
        const themeStorageKey = 'lgu:shadcn-theme';
        const themeOptions = new Set([
            'neutral', 'stone', 'zinc', 'gray', 'amber', 'blue', 'cyan', 'emerald',
            'fuchsia', 'green', 'indigo', 'lime', 'orange', 'pink', 'purple', 'red',
            'rose', 'sky', 'teal', 'violet', 'yellow', 'mauve', 'olive', 'mist', 'taupe',
        ]);

        const storedTheme = window.localStorage.getItem(themeStorageKey);

        document.documentElement.dataset.theme = themeOptions.has(storedTheme)
            ? storedTheme
            : 'neutral';
    })();
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
<style id="app-theme-tokens"></style>
