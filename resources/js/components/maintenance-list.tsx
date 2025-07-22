import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { Calendar, CheckCircle, Loader } from 'lucide-react';
import React, { useEffect, useState } from 'react';

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
    orgId?: string;
    orgSlug?: string;
}

const statusConfig: Record<MaintenanceStatus, { icon: React.ElementType; color: string; label: string }> = {
    scheduled: { icon: Calendar, color: 'text-blue-500', label: 'Scheduled' },
    in_progress: { icon: Loader, color: 'text-yellow-500', label: 'In Progress' },
    completed: { icon: CheckCircle, color: 'text-green-500', label: 'Completed' },
};

export function MaintenanceList({ maintenances, loading, error, orgId, orgSlug }: MaintenanceListProps) {
    const [maintenanceList, setMaintenanceList] = useState<Maintenance[]>(maintenances.data);

    // Sync with prop changes
    useEffect(() => {
        setMaintenanceList(maintenances.data);
    }, [maintenances.data]);

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

    if (!maintenanceList.length) {
        return <div className="text-sm text-muted-foreground">No maintenance scheduled.</div>;
    }

    return (
        <div className="space-y-4">
            {maintenanceList.map((maintenance) => {
                const config = statusConfig[maintenance.status];
                const Icon = config.icon;

                return (
                    <Card key={maintenance.id} className="hover:shadow-md transition-shadow">
                        <CardHeader className="pb-3">
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-base">{maintenance.title}</CardTitle>
                                <div className="flex items-center gap-2">
                                    <Icon className={cn('h-4 w-4', config.color)} />
                                    <span className="text-sm font-medium">{config.label}</span>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <div className="text-sm text-muted-foreground">
                                <div>Start: {new Date(maintenance.scheduled_start).toLocaleString()}</div>
                                <div>End: {new Date(maintenance.scheduled_end).toLocaleString()}</div>
                            </div>
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}
