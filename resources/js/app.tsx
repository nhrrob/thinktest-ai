import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'react-hot-toast';
import { initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <>
                <App {...props} />
                <Toaster
                    position="top-center"
                    reverseOrder={false}
                    containerStyle={{
                        top: '20px',
                        zIndex: 10000,
                    }}
                    gutter={12}
                    toastOptions={{
                        duration: 5000,
                        style: {
                            // Minimal styling since we use custom components
                            background: 'transparent',
                            border: 'none',
                            boxShadow: 'none',
                            padding: 0,
                            margin: 0,
                        },
                    }}
                />
            </>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
