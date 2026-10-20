import { createInertiaApp, router } from '@inertiajs/react';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';

// Every page is bundled up front: the app is small, and moving between pages never waits for a download.
const pages = import.meta.glob<{ default: ComponentType }>('./pages/**/*.tsx', { eager: true });

createInertiaApp({
    title: (title, page) => (title ? `${title} - ${page.props.appName}` : String(page.props.appName)),
    resolve: (name) => pages[`./pages/${name}.tsx`],
    setup({ el, App, props }) {
        // There is no element only when rendering on a server, which this app does not do.
        if (!el) {
            return;
        }

        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#059669',
        delay: 250,
    },
    defaults: {
        // Moving to another page fades the content, as the old full page loads did.
        visitOptions: (_href, options) => ({ ...options, viewTransition: options.viewTransition || (options.method === 'get' && !options.preserveState) }),
    },
});

// Pages fetched ahead of time hold the data from before a change, so a change throws them away.
router.on('before', (event) => {
    if (event.detail.visit.method !== 'get' && !event.detail.visit.prefetch) {
        router.flushAll();
    }
});
