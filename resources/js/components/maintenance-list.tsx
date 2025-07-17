import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { Calendar, CheckCircle, Loader } from 'lucide-react';
import React from 'react';

type MaintenanceStatus = 'scheduled' | 'in_progress' | 'completed';

interface Maintenance {
    id: number;
    title: string;
    status: MaintenanceStatus;
    scheduled_start: string;
    scheduled_end: string;
}

interface MaintenanceListProps {
    maintenances: {
        data: Maintenance[];
    };
    loading?: boolean;
    error?: string | null;
}

const statusConfig: Record<MaintenanceStatus, { icon: React.ElementType; color: string; label: string }> = {
    scheduled: { icon: Calendar, color: 'text-blue-500', label: 'Scheduled' },
    in_progress: { icon: Loader, color: 'text-yellow-500', label: 'In Progress' },
    completed: { icon: CheckCircle, color: 'text-green-500', label: 'Completed' },
};

export function MaintenanceList({ maintenances, loading, error }: MaintenanceListProps) {
    console.log(`maintenances`, maintenances);
    if (loading) {
        return (
            <div className="space-y-4">
                {[...Array(3)].map((_, i) => (
                    <div key={i} className="h-24 w-full animate-pulse rounded-lg bg-muted" />
                ))}
            </div>
        );
    }

    if (error) {
        return <div className="text-sm text-red-500">{error}</div>;
    }

    if (!maintenances.data.length) {
        return <div className="text-sm text-muted-foreground">No scheduled maintenance.</div>;
    }

    return (
        <div className="space-y-4">
            {maintenances.data.map((m) => {
                const config = statusConfig[m.status];
                const Icon = config.icon;
                return (
                    <Card key={m.id}>
                        <CardHeader>
                            <div className="flex items-start justify-between">
                                <CardTitle className="text-lg">{m.title}</CardTitle>
                                <div className={cn('flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold', config.color)}>
                                    <Icon className={cn('h-4 w-4', m.status === 'in_progress' && 'animate-spin')} />
                                    <span>{config.label}</span>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-sm text-muted-foreground">
                                Scheduled from {new Date(m.scheduled_start).toLocaleString()} to {new Date(m.scheduled_end).toLocaleString()}
                            </div>
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}
