import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    // Load environment variables
    const env = loadEnv(mode, process.cwd(), '');
    
    // Debug environment variables during build
    console.log('🔧 Vite Build Environment Debug:');
    console.log('Mode:', mode);
    console.log('VITE_PUSHER_APP_KEY:', env.VITE_PUSHER_APP_KEY ? 'SET' : 'NOT SET');
    console.log('VITE_PUSHER_APP_CLUSTER:', env.VITE_PUSHER_APP_CLUSTER || 'NOT SET');
    console.log('NODE_ENV:', process.env.NODE_ENV);
    
    // Log all VITE_ prefixed variables
    const viteVars = Object.keys(env).filter(key => key.startsWith('VITE_'));
    console.log('All VITE_ variables:', viteVars);
    
    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.tsx'],
                ssr: 'resources/js/ssr.tsx',
                refresh: true,
            }),
            react(),
            tailwindcss(),
        ],
        esbuild: {
            jsx: 'automatic',
        },
        resolve: {
            alias: {
                'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
            },
        },
        build: {
            rollupOptions: {
                output: {
                    assetFileNames: (assetInfo) => {
                        const info = assetInfo?.name?.split('.') || [];
                        const ext = info[info.length - 1];
                        if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(ext)) {
                            return `assets/images/[name]-[hash][extname]`;
                        }
                        return `assets/[name]-[hash][extname]`;
                    },
                },
            },
        },
        // Explicitly define environment variables to ensure they're available
        define: {
            'import.meta.env.VITE_PUSHER_APP_KEY': JSON.stringify(env.VITE_PUSHER_APP_KEY),
            'import.meta.env.VITE_PUSHER_APP_CLUSTER': JSON.stringify(env.VITE_PUSHER_APP_CLUSTER),
            'import.meta.env.VITE_APP_NAME': JSON.stringify(env.VITE_APP_NAME),
        },
    };
});