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

async function initializeEcho() {
    let pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
    let pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap2';

    // If environment variables are not available, try fetching from server
    if (!pusherKey) {
        try {
            const response = await fetch('/broadcasting/config');
            const config = await response.json();
            
            if (config.pusher && config.pusher.key) {
                pusherKey = config.pusher.key;
                pusherCluster = config.pusher.cluster || 'ap2';
                console.log('✅ Successfully fetched Pusher config from server');
            }
        } catch (error) {
            console.error('❌ Failed to fetch Pusher config from server:', error);
        }
    }

    if (!pusherKey) {
        console.warn('⚠️ No Pusher credentials available - Echo not initialized');
        // @ts-ignore
        window.Echo = null;
        return;
    }

    try {
        const echo = new Echo({
            broadcaster: 'pusher',
            key: pusherKey,
            cluster: pusherCluster,
            forceTLS: true,
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            encrypted: true,
            timeout: 20000,
            enableLogging: import.meta.env.DEV,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        });

        // Make Echo available globally
        // @ts-ignore
        window.Echo = echo;
        
    } catch (error) {
        console.error('❌ Failed to initialize Echo/Pusher:', error);
        // @ts-ignore
        window.Echo = null;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Beacon';

// Set up global error handling
router.on('error', (event) => {
    const { errors } = event.detail;
    
    if (errors && Object.keys(errors).length === 1 && errors['403']) {
        toast.error(errors['403'] || 'You do not have permission to perform this action.');
    }
});

// Initialize Echo and then start the app
initializeEcho().then(() => {
    console.log('🎉 Echo initialization complete, starting Inertia app...');
    
    createInertiaApp({
        title: (title) => `${title} - ${appName}`,
        resolve: (name) =>
            resolvePageComponent(
                `./pages/${name}.tsx`,
                import.meta.glob('./pages/**/*.tsx'),
            ),
        setup({ el, App, props }) {
            const root = createRoot(el);

            console.log('🎨 App rendering with Echo state:', {
                hasWindowEcho: !!window.Echo,
                echoState: window.Echo?.connector?.pusher?.connection?.state || 'No connection'
            });

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
});

// This will set light / dark mode on load...
initializeTheme();