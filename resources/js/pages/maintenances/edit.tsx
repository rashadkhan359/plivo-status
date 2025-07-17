import { Head, useForm } from '@inertiajs/react';
import { Button, Card, Input, Label, Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui';
import React from 'react';
import { Maintenance } from '@/types/maintenance';
import { Service } from '@/types/service';

const statusOptions = [
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
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
  const { data, setData, patch, processing, errors } = useForm({
    service_id: String(maintenance.data.service_id),
    title: maintenance.data.title,
    description: maintenance.data.description || '',
    scheduled_start: maintenance.data.scheduled_start.slice(0, 16),
    scheduled_end: maintenance.data.scheduled_end.slice(0, 16),
    status: maintenance.data.status,
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    patch(`/maintenances/${maintenance.data.id}`);
  }

  return (
    <>
      <Head title="Edit Maintenance" />
      <Card className="max-w-lg mx-auto p-6 mt-8">
        <h1 className="text-xl font-bold mb-4">Edit Maintenance</h1>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <Label htmlFor="service_id">Service</Label>
            <Select value={data.service_id} onValueChange={(value) => setData('service_id', value)}>
              <SelectTrigger id="service_id">
                <SelectValue placeholder="Select service" />
              </SelectTrigger>
              <SelectContent>
                                {services.data.map((service: Service) => (
                                    <SelectItem key={service.id} value={String(service.id)}>
                                        {service.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
            </Select>
            {errors.service_id && <p className="text-xs text-red-500">{errors.service_id}</p>}
          </div>
          <div>
            <Label htmlFor="title">Title</Label>
            <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} />
            {errors.title && <p className="text-xs text-red-500">{errors.title}</p>}
          </div>
          <div>
            <Label htmlFor="description">Description</Label>
            <Input id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} />
            {errors.description && <p className="text-xs text-red-500">{errors.description}</p>}
          </div>
          <div>
            <Label htmlFor="scheduled_start">Scheduled Start</Label>
            <Input id="scheduled_start" type="datetime-local" value={data.scheduled_start} onChange={(e) => setData('scheduled_start', e.target.value)} />
            {errors.scheduled_start && <p className="text-xs text-red-500">{errors.scheduled_start}</p>}
          </div>
          <div>
            <Label htmlFor="scheduled_end">Scheduled End</Label>
            <Input id="scheduled_end" type="datetime-local" value={data.scheduled_end} onChange={(e) => setData('scheduled_end', e.target.value)} />
            {errors.scheduled_end && <p className="text-xs text-red-500">{errors.scheduled_end}</p>}
          </div>
          <div>
            <Label htmlFor="status">Status</Label>
                        <Select value={data.status} onValueChange={(value) => setData('status', value as Maintenance['status'])}>
                            <SelectTrigger id="status">
                <SelectValue placeholder="Select status" />
              </SelectTrigger>
              <SelectContent>
                {statusOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {errors.status && <p className="text-xs text-red-500">{errors.status}</p>}
          </div>
          <Button type="submit" disabled={processing}>
            Update
          </Button>
        </form>
      </Card>
    </>
  );
}