import { DeleteDialog } from '@/components/delete-dialog';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Service } from '@/types/service';
import { Head, Link, router } from '@inertiajs/react';
import React from 'react';

interface Props {
    services: {
        data: Service[];
    };
}

export default function ServiceIndex({ services }: PageProps<Props>) {
    const [deleteDialogOpen, setDeleteDialogOpen] = React.useState(false);
    const [serviceToDelete, setServiceToDelete] = React.useState<Service | null>(null);
    const [deleting, setDeleting] = React.useState(false);

    const handleDeleteClick = (service: Service) => {
        setServiceToDelete(service);
        setDeleteDialogOpen(true);
    };

    const handleConfirmDelete = async () => {
        if (!serviceToDelete) return;
        setDeleting(true);
        router.delete(`/services/${serviceToDelete.id}`, {
            onFinish: () => {
                setDeleting(false);
                setDeleteDialogOpen(false);
                setServiceToDelete(null);
            },
        });
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
                <div className="p-5">
                    <Head title="Services" />
                    <div className="mb-6 flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Services</h1>
                        <Link href="/services/create" target="_blank">
                            <Button size="sm" variant="outline" className="flex items-center">
                                Add Service
                            </Button>
                        </Link>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {services.data.map((service) => (
                            <Card key={service.id} className="flex flex-col gap-2 p-4">
                                <div className="flex items-center justify-between">
                                    <span className="font-semibold">{service.name}</span>
                                    <StatusBadge status={service.status} />
                                </div>
                                <div className="text-sm text-gray-500">{service.description}</div>
                                <div className="mt-2 flex gap-2">
                                    <Link href={`/services/${service.id}/edit`} target="_blank">
                                        <Button size="sm" variant="outline" className="flex items-center">
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button size="sm" variant="destructive" className="flex items-center" onClick={() => handleDeleteClick(service)}>
                                        Delete
                                    </Button>
                                </div>
                            </Card>
                        ))}
                    </div>
                </div>
            </AppLayout>
            <DeleteDialog
                open={deleteDialogOpen}
                onOpenChange={(open) => {
                    setDeleteDialogOpen(open);
                    if (!open) setServiceToDelete(null);
                }}
                onConfirm={handleConfirmDelete}
                itemType="service"
                itemName={serviceToDelete?.name}
                loading={deleting}
            />
        </>
    );
}
