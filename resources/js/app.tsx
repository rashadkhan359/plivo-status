import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { Toaster } from './components/ui/toaster';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import React from 'react';

// Configure Pusher
// @ts-ignore
window.Pusher = Pusher;

// Configure Echo with Pusher only if credentials are available
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap2';

let echo: any = null;

if (pusherKey) {
    try {
        echo = new Echo({
            broadcaster: 'pusher',
            key: pusherKey,
            cluster: pusherCluster,
            forceTLS: true,
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            encrypted: true,
            timeout: 20000,
            enableLogging: import.meta.env.DEV,
        });
    } catch (error) {
        console.warn('Failed to initialize Pusher:', error);
    }
}

// Make Echo available globally for useRealtime hook and direct usage
// @ts-ignore
window.Echo = echo;

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Set up global error handling
router.on('error', (event) => {
    const { errors } = event.detail;
    
    // Handle 403 errors specifically
    if (errors && Object.keys(errors).length === 1 && errors['403']) {
        // Use Sonner toast directly (no Inertia context needed)
        toast.error(errors['403'] || 'You do not have permission to perform this action.');
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
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


