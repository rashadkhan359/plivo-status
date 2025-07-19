import { DeleteDialog } from '@/components/delete-dialog';
import { StatusBadge } from '@/components/status-badge';
import { ServiceStatusUpdate } from '@/components/service-status-update';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { Service, Team } from '@/types/service';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Edit, Trash2, Zap, Plus, Wrench, Users, EyeOff } from 'lucide-react';
import React from 'react';
import { useToast } from '@/hooks/use-toast';
import { SharedData } from '@/types';

interface Props {
    services: {
        data: Service[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    teams: Team[];
    canCreate: boolean;
}

export default function ServiceIndex({ services, teams, canCreate }: PageProps<Props>) {
    const toast = useToast();
    const { props } = usePage<SharedData>();
    const { currentPermissions } = props.auth;
    
    const [deleteDialogOpen, setDeleteDialogOpen] = React.useState(false);
    const [serviceToDelete, setServiceToDelete] = React.useState<Service | null>(null);
    const [deleting, setDeleting] = React.useState(false);
    const [statusUpdateOpen, setStatusUpdateOpen] = React.useState(false);
    const [serviceToUpdate, setServiceToUpdate] = React.useState<Service | null>(null);
    const [selectedTeam, setSelectedTeam] = React.useState<string>('all');

    const handleDeleteClick = (service: Service) => {
        setServiceToDelete(service);
        setDeleteDialogOpen(true);
    };

    const handleStatusUpdateClick = (service: Service) => {
        setServiceToUpdate(service);
        setStatusUpdateOpen(true);
    };

    const handleConfirmDelete = async () => {
        if (!serviceToDelete) return;
        setDeleting(true);
        router.delete(`/services/${serviceToDelete.id}`, {
            onSuccess: () => {
                toast.success('Service deleted successfully!');
            },
            onError: () => {
                toast.error('Failed to delete service. Please try again.');
            },
            onFinish: () => {
                setDeleting(false);
                setDeleteDialogOpen(false);
                setServiceToDelete(null);
            },
        });
    };

    const filteredServices = React.useMemo(() => {
        if (selectedTeam === 'all') return services.data;
        if (selectedTeam === 'unassigned') return services.data.filter(service => !service.team_id);
        return services.data.filter(service => service.team_id?.toString() === selectedTeam);
    }, [services.data, selectedTeam]);

    const getTeamColor = (teamId?: number) => {
        const team = teams.find(t => t.id === teamId);
        return team?.color || '#64748b';
    };

    const breadcrumbs = [
        {
            title: 'Services',
            href: '/services',
        },
    ];

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex flex-col gap-6 p-6 max-w-6xl mx-auto w-full">
                    <Head title="Services" />
                    
                    {/* Page Header */}
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold">Services</h1>
                            <p className="text-muted-foreground mt-2">Monitor and manage your service status</p>
                        </div>
                        {canCreate && (
                            <Link href="/services/create">
                                <Button className="flex items-center gap-2">
                                    <Plus className="h-4 w-4" />
                                    Add Service
                                </Button>
                            </Link>
                        )}
                    </div>

                    {/* Team Filter */}
                    {teams.length > 0 && (
                        <div className="flex flex-wrap gap-2">
                            <Button
                                variant={selectedTeam === 'all' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setSelectedTeam('all')}
                            >
                                All Services ({services.data.length})
                            </Button>
                            <Button
                                variant={selectedTeam === 'unassigned' ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => setSelectedTeam('unassigned')}
                            >
                                Unassigned ({services.data.filter(s => !s.team_id).length})
                            </Button>
                            {teams.map((team) => (
                                <Button
                                    key={team.id}
                                    variant={selectedTeam === team.id.toString() ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => setSelectedTeam(team.id.toString())}
                                    className="flex items-center gap-2"
                                >
                                    <div 
                                        className="w-2 h-2 rounded-full" 
                                        style={{ backgroundColor: team.color || '#64748b' }}
                                    />
                                    {team.name} ({services.data.filter(s => s.team_id === team.id).length})
                                </Button>
                            ))}
                        </div>
                    )}

                    {/* Content */}
                    {filteredServices.length === 0 ? (
                        <div className="text-center py-12">
                            <Wrench className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                            <h3 className="text-lg font-semibold mb-2">
                                {selectedTeam === 'all' ? 'No services found' : 'No services in this filter'}
                            </h3>
                            <p className="text-muted-foreground mb-6">
                                {selectedTeam === 'all' 
                                    ? 'Get started by creating your first service to monitor'
                                    : 'Try selecting a different team or create a new service'
                                }
                            </p>
                            {canCreate && selectedTeam === 'all' && (
                                <Link href="/services/create">
                                    <Button>Create your first service</Button>
                                </Link>
                            )}
                        </div>
                    ) : (
                        <>
                            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                                {filteredServices.map((service) => (
                                    <Card key={service.id} className="flex flex-col p-6 hover:shadow-md transition-shadow duration-200">
                                        <div className="flex items-start justify-between mb-4">
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-2 mb-2">
                                                    <h3 className="font-semibold text-lg truncate">{service.name}</h3>
                                                    {service.visibility === 'private' && (
                                                        <EyeOff className="h-4 w-4 text-muted-foreground" />
                                                    )}
                                                </div>
                                                <p className="text-sm text-muted-foreground mb-3 line-clamp-2">
                                                    {service.description || 'No description provided'}
                                                </p>
                                                
                                                {/* Team Badge */}
                                                {service.team ? (
                                                    <Badge variant="secondary" className="flex items-center gap-1 w-fit">
                                                        <div 
                                                            className="w-2 h-2 rounded-full" 
                                                            style={{ backgroundColor: service.team.color || '#64748b' }}
                                                        />
                                                        <Users className="h-3 w-3" />
                                                        {service.team.name}
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="w-fit">
                                                        Unassigned
                                                    </Badge>
                                                )}
                                            </div>
                                            <StatusBadge status={service.status} />
                                        </div>
                                        
                                                                                <div className="flex gap-2 mt-auto pt-4">
                                            {currentPermissions?.manage_services && (
                                                <Button 
                                                    size="sm" 
                                                    variant="outline" 
                                                    onClick={() => handleStatusUpdateClick(service)}
                                                    className="flex items-center gap-2 flex-1"
                                                >
                                                    <Zap className="h-4 w-4" />
                                                    Update Status
                                                </Button>
                                            )}
                                            {currentPermissions?.manage_services && (
                                                <Link href={`/services/${service.id}/edit`}>
                                                    <Button size="sm" variant="outline">
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                            )}
                                            {currentPermissions?.manage_services && (
                                                <Button 
                                                    size="sm" 
                                                    variant="outline" 
                                                    onClick={() => handleDeleteClick(service)}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            )}
                                        </div>
                                    </Card>
                                ))}
                            </div>

                            {/* Pagination */}
                            {services.last_page > 1 && (
                                <div className="flex items-center justify-between mt-6">
                                    <div className="text-sm text-muted-foreground">
                                        Showing {((services.current_page - 1) * services.per_page) + 1} to{' '}
                                        {Math.min(services.current_page * services.per_page, services.total)} of{' '}
                                        {services.total} results
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        {services.links.map((link, index) => (
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

                    {/* Dialogs */}
                    <DeleteDialog
                        open={deleteDialogOpen}
                        onOpenChange={setDeleteDialogOpen}
                        onConfirm={handleConfirmDelete}
                        loading={deleting}
                        itemType="service"
                        itemName={serviceToDelete?.name}
                    />

                    {serviceToUpdate && (
                        <ServiceStatusUpdate
                            service={serviceToUpdate}
                            open={statusUpdateOpen}
                            onOpenChange={setStatusUpdateOpen}
                        />
                    )}
                </div>
            </AppLayout>
        </>
    );
}
