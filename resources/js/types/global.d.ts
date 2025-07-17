import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import type { route as routeFn } from 'ziggy-js';
import type { SharedData } from './index';

declare global {
    const route: typeof routeFn;

    type PageProps<T = {}> = T & SharedData;

    interface Window {
        Echo: any;
    }
}
