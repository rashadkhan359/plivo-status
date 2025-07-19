import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { useInitials } from '@/hooks/use-initials';
import { type User } from '@/types';
import { usePage } from '@inertiajs/react';

export function UserInfo({ user, showEmail = false }: { user: User; showEmail?: boolean }) {
    const getInitials = useInitials();
    const { currentRole } = usePage().props.auth as any;

    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                <AvatarImage src={user.avatar} alt={user.name} />
                <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                    {getInitials(user.name)}
                </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium">{user.name}</span>
                {showEmail && <span className="truncate text-xs text-muted-foreground">{user.email}</span>}
                {currentRole === 'system_admin' && (
                    <Badge variant="destructive" className="mt-1 w-fit text-xs">
                        System Admin
                    </Badge>
                )}
            </div>
        </>
    );
}
