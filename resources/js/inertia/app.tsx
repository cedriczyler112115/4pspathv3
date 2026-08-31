import '../lib/utils';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';

const appName = document.querySelector('meta[name="app-name"]')?.getAttribute('content') ?? 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.tsx`,
        import.meta.glob<ComponentType>(['./Pages/**/*.tsx', './Layouts/**/*.tsx'])
    ),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
