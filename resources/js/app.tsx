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
                    toastOptions={{
                        duration: 5000,
                        // Custom className for targeting in CSS
                        className: 'custom-toast',
                        style: {
                            background: 'hsl(var(--background))',
                            color: 'hsl(var(--foreground))',
                            border: '1px solid hsl(var(--border))',
                            borderRadius: '8px',
                            padding: '16px',
                            fontSize: '14px',
                            fontWeight: '500',
                            boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                            maxWidth: '420px',
                            minWidth: '300px',
                            zIndex: 9999,
                            // Disable default animations to use our custom ones
                            animation: 'none',
                        },
                        success: {
                            duration: 5000,
                            iconTheme: {
                                primary: 'hsl(var(--primary))',
                                secondary: 'hsl(var(--primary-foreground))',
                            },
                        },
                        error: {
                            duration: 6000,
                            iconTheme: {
                                primary: 'hsl(var(--destructive))',
                                secondary: 'hsl(var(--destructive-foreground))',
                            },
                        },
                        loading: {
                            duration: Infinity,
                        },
                    }}
                    containerStyle={{
                        top: '20px',
                        zIndex: 9999,
                    }}
                    gutter={12}
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
