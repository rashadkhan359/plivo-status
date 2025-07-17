import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import type { Maintenance } from '@/types/models';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

interface MaintenanceIndexPageProps extends PageProps { // eslint-disable-line
    maintenances: {
        data: Maintenance[];
    };
}

const statusVariantMap: Record<Maintenance['status'], 'default' | 'secondary' | 'destructive'> = {
    scheduled: 'default',
    in_progress: 'secondary',
    completed: 'default', // 'success' is not a valid variant, using 'default'
    cancelled: 'destructive',
};

export default function MaintenanceIndexPage({ maintenances }: MaintenanceIndexPageProps) {
    const { data: maintenanceList } = maintenances; // Destructure for easier access

    return (
        <AppLayout>
            <Head title="Admin: All Maintenance" />

            <Card>
                <CardHeader>
                    <CardTitle>All Maintenance</CardTitle>
                    <CardDescription>A centralized view of all maintenance events across all organizations.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Organization</TableHead>
                                <TableHead>Title</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Scheduled Start</TableHead>
                                <TableHead>Scheduled End</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {maintenanceList.length > 0 ? (
                                maintenanceList.map((maintenance) => (
                                    <TableRow key={maintenance.id}>
                                        <TableCell>{maintenance.organization.name}</TableCell>
                                        <TableCell className="font-medium">{maintenance.title}</TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={statusVariantMap[maintenance.status]}
                                                className={cn('capitalize')}>
                                                {maintenance.status.replace('_', ' ')}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{new Date(maintenance.scheduled_start).toLocaleString()}</TableCell>
                                        <TableCell>{new Date(maintenance.scheduled_end).toLocaleString()}</TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell colSpan={5} className="h-24 text-center">
                                        No maintenance events found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
