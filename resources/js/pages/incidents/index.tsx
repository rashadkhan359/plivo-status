import { DeleteDialog } from '@/components/delete-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { Incident } from '@/types/incident';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle, Info, ShieldAlert, Zap, Edit, Trash2, MessageSquare, Clock, Plus, Users, User } from 'lucide-react';
import React from 'react';
import { cn } from '@/lib/utils';
import { useToast } from '@/hooks/use-toast';

interface Props {
    incidents: {
        data: Incident[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    canCreate: boolean;
}

const statusIcons: Record<Incident['status'], { icon: React.ElementType; color: string }> = {
    investigating: { icon: Info, color: 'text-blue-500' },
    identified: { icon: ShieldAlert, color: 'text-yellow-500' },
    monitoring: { icon: Zap, color: 'text-purple-500' },
    resolved: { icon: CheckCircle, color: 'text-green-500' },
};

const severityColors = {
    low: 'text-blue-600 bg-blue-50 border-blue-200',
    medium: 'text-yellow-600 bg-yellow-50 border-yellow-200',
    high: 'text-orange-600 bg-orange-50 border-orange-200',
    critical: 'text-red-600 bg-red-50 border-red-200',
};

export default function IncidentIndex({ incidents, canCreate }: PageProps<Props>) {
    const toast = useToast();
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
            onSuccess: () => {
                toast.success('Incident deleted successfully!');
            },
            onError: () => {
                toast.error('Failed to delete incident. Please try again.');
            },
            onFinish: () => {
                setDeleting(false);
                setDeleteDialogOpen(false);
                setIncidentToDelete(null);
            },
        });
    };

    const handleResolve = (incidentId: number) => {
        router.patch(`/incidents/${incidentId}/resolve`, {}, {
            onSuccess: () => {
                toast.success('Incident resolved successfully!');
            },
            onError: () => {
                toast.error('Failed to resolve incident. Please try again.');
            },
            preserveScroll: true,
        });
    };

    const breadcrumbs = [
        { title: 'Incidents', href: '/incidents' },
    ];

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex flex-col gap-6 p-6 max-w-6xl mx-auto w-full">
                    <Head title="Incidents" />
                    
                    {/* Page Header */}
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold">Incidents</h1>
                            <p className="text-muted-foreground mt-2">Track and manage service incidents</p>
                        </div>
                        {canCreate && (
                            <Link href="/incidents/create">
                                <Button className="flex items-center gap-2">
                                    <Plus className="h-4 w-4" />
                                    Report Incident
                                </Button>
                            </Link>
                        )}
                    </div>

                    {/* Content */}
                    {incidents.data.length === 0 ? (
                        <div className="text-center py-12">
                            <AlertTriangle className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                            <h3 className="text-lg font-semibold mb-2">No incidents found</h3>
                            <p className="text-muted-foreground mb-6">All systems are running smoothly</p>
                            {canCreate && (
                                <Link href="/incidents/create">
                                    <Button>Report an incident</Button>
                                </Link>
                            )}
                        </div>
                    ) : (
                        <>
                            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                                {incidents.data.map((incident) => {
                                    const StatusIcon = statusIcons[incident.status]?.icon || AlertTriangle;
                                    const statusColor = statusIcons[incident.status]?.color || 'text-gray-500';

                                    return (
                                        <Card key={incident.id} className="flex flex-col p-6 hover:shadow-md transition-shadow duration-200">
                                            <CardHeader className="p-0 mb-4">
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1 min-w-0">
                                                        <CardTitle className="text-lg truncate">{incident.title}</CardTitle>
                                                        <p className="text-sm text-muted-foreground mt-1 line-clamp-2">
                                                            {incident.description || 'No description provided'}
                                                        </p>
                                                    </div>
                                                    <StatusIcon className={cn('h-5 w-5 flex-shrink-0 ml-2', statusColor)} />
                                                </div>
                                            </CardHeader>

                                            <CardContent className="p-0 flex-1">
                                                <div className="flex items-center justify-between mb-4">
                                                    <div className="flex items-center gap-2">
                                                        <Badge
                                                            className={cn(
                                                                'capitalize border',
                                                                severityColors[incident.severity] || 'text-gray-600 bg-gray-50 border-gray-200'
                                                            )}
                                                            variant="outline"
                                                        >
                                                            {incident.severity}
                                                        </Badge>
                                                        <Badge variant="secondary" className="capitalize">
                                                            {incident.status.replace('_', ' ')}
                                                        </Badge>
                                                    </div>
                                                </div>

                                                {/* Affected Services */}
                                                {incident.services && incident.services.length > 0 && (
                                                    <div className="mb-4">
                                                        <p className="text-xs text-muted-foreground mb-2 flex items-center gap-1">
                                                            <Zap className="h-3 w-3" />
                                                            Affected Services ({incident.services.length})
                                                        </p>
                                                        <div className="flex flex-wrap gap-1">
                                                            {incident.services.slice(0, 3).map((service) => (
                                                                <Badge key={service.id} variant="outline" className="text-xs">
                                                                    {service.team && (
                                                                        <div 
                                                                            className="w-2 h-2 rounded-full mr-1" 
                                                                            style={{ backgroundColor: service.team.color || '#64748b' }}
                                                                        />
                                                                    )}
                                                                    {service.name}
                                                                </Badge>
                                                            ))}
                                                            {incident.services.length > 3 && (
                                                                <Badge variant="outline" className="text-xs">
                                                                    +{incident.services.length - 3} more
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </div>
                                                )}

                                                {/* Creator and Time */}
                                                <div className="space-y-2">
                                                    {incident.creator && (
                                                        <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                            <User className="h-3 w-3" />
                                                            <span>Created by {incident.creator.name}</span>
                                                        </div>
                                                    )}
                                                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                        <Clock className="h-3 w-3" />
                                                        <span>{new Date(incident.created_at).toLocaleDateString()}</span>
                                                    </div>
                                                    {incident.resolver && incident.resolved_at && (
                                                        <div className="flex items-center gap-2 text-xs text-green-600">
                                                            <CheckCircle className="h-3 w-3" />
                                                            <span>Resolved by {incident.resolver.name}</span>
                                                        </div>
                                                    )}
                                                </div>
                                            </CardContent>

                                            <div className="flex gap-2 mt-4 pt-4 border-t">
                                                {incident.status !== 'resolved' && (
                                                    <Button 
                                                        size="sm" 
                                                        variant="outline" 
                                                        onClick={() => handleResolve(incident.id)}
                                                        className="flex items-center gap-2"
                                                    >
                                                        <CheckCircle className="h-4 w-4" />
                                                        Resolve
                                                    </Button>
                                                )}
                                                <Link href={`/incidents/${incident.id}/updates`}>
                                                    <Button size="sm" variant="outline" className="flex items-center gap-2">
                                                        <MessageSquare className="h-4 w-4" />
                                                        Updates
                                                    </Button>
                                                </Link>
                                                <Link href={`/incidents/${incident.id}/edit`}>
                                                    <Button size="sm" variant="outline">
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                                <Button 
                                                    size="sm" 
                                                    variant="outline" 
                                                    onClick={() => handleDeleteClick(incident)}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </Card>
                                    );
                                })}
                            </div>

                            {/* Pagination */}
                            {incidents.last_page > 1 && (
                                <div className="flex items-center justify-between mt-6">
                                    <div className="text-sm text-muted-foreground">
                                        Showing {((incidents.current_page - 1) * incidents.per_page) + 1} to{' '}
                                        {Math.min(incidents.current_page * incidents.per_page, incidents.total)} of{' '}
                                        {incidents.total} results
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        {incidents.links.map((link, index) => (
                                            <Button
                                                key={index}
                                                variant={link.active ? "default" : "outline"}
                                                size="sm"
                                                onClick={() => {
                                                    if (link.url) {
                                                        router.get(link.url);
                                                    }
                                                }}
                                                disabled={!link.url}
                                            >
                                                {link.label.replace('&laquo;', '«').replace('&raquo;', '»')}
                                            </Button>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </>
                    )}

                    {/* Delete Dialog */}
                    <DeleteDialog
                        open={deleteDialogOpen}
                        onOpenChange={setDeleteDialogOpen}
                        onConfirm={handleConfirmDelete}
                        loading={deleting}
                        itemType="incident"
                        itemName={incidentToDelete?.title}
                    />
                </div>
            </AppLayout>
        </>
    );
}

