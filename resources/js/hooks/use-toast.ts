import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';

export function useToast() {
    const { props } = usePage();
    const { flash } = props as any;

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }

        if (flash?.warning) {
            toast.warning(flash.warning);
        }

        if (flash?.info) {
            toast.info(flash.info);
        }
    }, [flash]);

    return {
        success: (message: string) => toast.success(message),
        error: (message: string) => toast.error(message),
        warning: (message: string) => toast.warning(message),
        info: (message: string) => toast.info(message),
    };
} 