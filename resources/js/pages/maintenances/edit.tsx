import { Head, useForm } from '@inertiajs/react';
import { Button, Card, Input, Label, Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui';
import AppLayout from '@/layouts/app-layout';
import React from 'react';
import { Maintenance } from '@/types/maintenance';
import { Service } from '@/types/service';
import { useToast } from '@/hooks/use-toast';

type MaintenanceStatus = 'scheduled' | 'in_progress' | 'completed';

const statusOptions = [
  { value: 'scheduled' as MaintenanceStatus, label: 'Scheduled' },
  { value: 'in_progress' as MaintenanceStatus, label: 'In Progress' },
  { value: 'completed' as MaintenanceStatus, label: 'Completed' },
];

interface Props {
  maintenance: {
    data: Maintenance;
  };
  services: {
    data: Service[];
  };
}

export default function MaintenanceEdit({ maintenance, services }: PageProps<Props>) {
  const toast = useToast();
  
  const { data, setData, put, processing, errors } = useForm({
    service_id: maintenance.data.service_id ? String(maintenance.data.service_id) : 'none',
    title: maintenance.data.title || '',
    description: maintenance.data.description || '',
    scheduled_start: maintenance.data.scheduled_start ? new Date(maintenance.data.scheduled_start).toISOString().slice(0, 16) : '',
    scheduled_end: maintenance.data.scheduled_end ? new Date(maintenance.data.scheduled_end).toISOString().slice(0, 16) : '',
    status: (maintenance.data.status as MaintenanceStatus) || 'scheduled',
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    put(`/maintenances/${maintenance.data.id}`, {
      onSuccess: () => {
        toast.success('Maintenance updated successfully!');
      },
      onError: () => {
        toast.error('Failed to update maintenance. Please try again.');
      },
    });
  }

  const breadcrumbs = [
    { title: 'Maintenance', href: '/maintenances' },
    { title: maintenance.data.title, href: `/maintenances/${maintenance.data.id}` },
    { title: 'Edit', href: `/maintenances/${maintenance.data.id}/edit` },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <div className="flex flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
        <Head title={`Edit Maintenance: ${maintenance.data.title}`} />
        
        {/* Page Header */}
        <div>
          <h1 className="text-3xl font-bold">Edit Maintenance</h1>
          <p className="text-muted-foreground mt-2">Update maintenance window details</p>
        </div>

        <Card className="p-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid gap-4">
              <div>
                <Label htmlFor="service_id">Affected Service (Optional)</Label>
                <div className="space-y-2">
                  {/* Show currently selected service */}
                  {maintenance.data.service ? (
                    <div className="text-sm text-muted-foreground mb-2">
                      Currently affecting: <span className="font-medium">{maintenance.data.service.name}</span>
                    </div>
                  ) : (
                    <div className="text-sm text-muted-foreground mb-2">
                      Currently affecting: <span className="font-medium">No specific service</span>
                    </div>
                  )}
                  
                  <Select value={data.service_id} onValueChange={(value) => setData('service_id', value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select a service (optional)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">No specific service</SelectItem>
                      {services.data.map((service) => (
                        <SelectItem key={service.id} value={service.id.toString()}>
                          {service.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <p className="text-xs text-muted-foreground">
                    {maintenance.data.service 
                      ? "You can change which service this maintenance affects, or remove it entirely"
                      : "You can optionally select a specific service this maintenance affects"
                    }
                  </p>
                </div>
                {errors.service_id && <p className="text-red-500 text-sm mt-1">{errors.service_id}</p>}
              </div>

              <div>
                <Label htmlFor="title">Title</Label>
                <Input
                  id="title"
                  value={data.title}
                  onChange={(e) => setData('title', e.target.value)}
                  placeholder="Scheduled database maintenance"
                  required
                />
                {errors.title && <p className="text-red-500 text-sm mt-1">{errors.title}</p>}
              </div>

              <div>
                <Label htmlFor="description">Description</Label>
                <textarea
                  id="description"
                  value={data.description}
                  onChange={(e) => setData('description', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-blue-200"
                  rows={3}
                  placeholder="Describe what will happen during this maintenance..."
                />
                {errors.description && <p className="text-red-500 text-sm mt-1">{errors.description}</p>}
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label htmlFor="scheduled_start">Start Time</Label>
                  <Input
                    id="scheduled_start"
                    type="datetime-local"
                    value={data.scheduled_start}
                    onChange={(e) => setData('scheduled_start', e.target.value)}
                    required
                  />
                  {errors.scheduled_start && <p className="text-red-500 text-sm mt-1">{errors.scheduled_start}</p>}
                </div>

                <div>
                  <Label htmlFor="scheduled_end">End Time</Label>
                  <Input
                    id="scheduled_end"
                    type="datetime-local"
                    value={data.scheduled_end}
                    onChange={(e) => setData('scheduled_end', e.target.value)}
                    required
                  />
                  {errors.scheduled_end && <p className="text-red-500 text-sm mt-1">{errors.scheduled_end}</p>}
                </div>
              </div>

              <div>
                <Label htmlFor="status">Status</Label>
                <Select value={data.status} onValueChange={(value) => setData('status', value as MaintenanceStatus)}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {statusOptions.map((option) => (
                      <SelectItem key={option.value} value={option.value}>
                        {option.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.status && <p className="text-red-500 text-sm mt-1">{errors.status}</p>}
              </div>
            </div>

            <div className="flex gap-4">
              <Button type="submit" disabled={processing}>
                {processing ? 'Updating...' : 'Update Maintenance'}
              </Button>
              <Button type="button" variant="outline" onClick={() => window.history.back()}>
                Cancel
              </Button>
            </div>
          </form>
        </Card>
      </div>
    </AppLayout>
  );
}