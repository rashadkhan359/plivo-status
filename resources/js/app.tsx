import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { Toaster } from './components/ui/toaster';

// Configure Pusher
// @ts-ignore
window.Pusher = Pusher;

// Configure Echo with Pusher
const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap2',
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    encrypted: true,
    timeout: 20000,
    enableLogging: import.meta.env.DEV,
});

// Add connection debugging
if (import.meta.env.DEV) {
    console.log('Echo configuration:', {
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        isDev: import.meta.env.DEV,
        protocol: window.location.protocol,
    });
    
    console.log('Echo object created:', {
        hasConnector: !!echo.connector,
        connectorType: echo.connector?.constructor?.name,
        hasPusher: !!(echo.connector as any)?.pusher,
    });
}

// Make Echo available globally for useRealtime hook
// @ts-ignore
window.Echo = echo;

// Add a small delay to ensure Echo is fully initialized
setTimeout(() => {
    if (import.meta.env.DEV) {
        console.log('Echo after initialization:', {
            hasConnector: !!window.Echo?.connector,
            hasPusher: !!(window.Echo?.connector as any)?.pusher,
            pusherState: (window.Echo?.connector as any)?.pusher?.connection?.state,
        });
    }
}, 100);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : title,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <>
                <App {...props} />
                <Toaster />
            </>
        );
    },
    progress: {
        color: '#4B55ff',
    },
});

// This will set light / dark mode on load...
initializeTheme();
