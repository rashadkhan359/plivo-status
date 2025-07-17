import { DeleteDialog } from '@/components/delete-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle, Info, ShieldAlert, Zap } from 'lucide-react';
import React from 'react';
import { cn } from '@/lib/utils';

type IncidentStatus = 'investigating' | 'identified' | 'monitoring' | 'resolved';

interface Incident {
    id: number;
    title: string;
    status: IncidentStatus;
    severity: string;
}

interface Props {
    incidents: {
        data: Incident[];
    };
}

const statusIcons: Record<IncidentStatus, { icon: React.ElementType; color: string }> = {
    investigating: { icon: Info, color: 'text-blue-500' },
    identified: { icon: ShieldAlert, color: 'text-yellow-500' },
    monitoring: { icon: Zap, color: 'text-purple-500' },
    resolved: { icon: CheckCircle, color: 'text-green-500' },
};

export default function IncidentIndex({ incidents }: PageProps<Props>) {
    const [deleteDialogOpen, setDeleteDialogOpen] = React.useState(false);
    const [incidentToDelete, setIncidentToDelete] = React.useState<Incident | null>(null);
    const [deleting, setDeleting] = React.useState(false);

    const handleDeleteClick = (incident: Incident) => {
        setIncidentToDelete(incident);
        setDeleteDialogOpen(true);
    };

    const handleConfirmDelete = async () => {
        if (!incidentToDelete) return;
        setDeleting(true);
        router.delete(`/incidents/${incidentToDelete.id}`, {
            onFinish: () => {
                setDeleting(false);
                setDeleteDialogOpen(false);
                setIncidentToDelete(null);
            },
        });
    };

    const breadcrumbs = [
      { title: 'Incidents', href: '/incidents' },
    ]

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="p-4">
                    <Head title="Incidents" />
                    <div className="mb-6 flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Incidents</h1>
                        <Link href="/incidents/create" target="_blank">
                            <Button size="sm" variant="outline" className="flex items-center">
                                Add Incident
                            </Button>
                        </Link>
                    </div>
                                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {incidents.data.map((incident) => {
                            const StatusIcon = statusIcons[incident.status]?.icon || AlertTriangle;
                            const statusColor = statusIcons[incident.status]?.color || 'text-gray-500';
                            return (
                                <Card key={incident.id} className="flex flex-col">
                                    <CardHeader>
                                        <div className="flex items-start justify-between">
                                            <CardTitle className="text-lg">{incident.title}</CardTitle>
                                            <div
                                                className={cn(
                                                    'flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold',
                                                    statusColor,
                                                )}>
                                                <StatusIcon className="h-4 w-4" />
                                                <span>{incident.status.replace('_', ' ')}</span>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent className="flex-grow">
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <ShieldAlert className="h-4 w-4 text-destructive" />
                                            <span className="font-semibold capitalize">{incident.severity}</span>
                                            Severity
                                        </div>
                                    </CardContent>
                                    <div className="flex items-center justify-end gap-2 border-t p-4">
                                        <Button asChild size="sm" variant="outline">
                                            <Link href={`/incidents/${incident.id}/edit`}>Edit</Link>
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            onClick={() => handleDeleteClick(incident)}>
                                            Delete
                                        </Button>
                                    </div>
                                </Card>
                            );
                        })}
                    </div>
                    <DeleteDialog
                        open={deleteDialogOpen}
                        onOpenChange={(open) => {
                            setDeleteDialogOpen(open);
                            if (!open) setIncidentToDelete(null);
                        }}
                        onConfirm={handleConfirmDelete}
                        itemType="incident"
                        itemName={incidentToDelete?.title}
                        loading={deleting}
                    />
                </div>
            </AppLayout>
        </>
    );
}
