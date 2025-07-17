import { Head, useForm } from '@inertiajs/react';
import { Button, Card, Input, Label, Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui';
import React from 'react';
import { Service } from '@/types/service';

const statusOptions = [
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'completed', label: 'Completed' },
];

interface Props {
  services: {
    data: Service[];
  };
}

export default function MaintenanceCreate({ services }: PageProps<Props>) {
  const { data, setData, post, processing, errors } = useForm({
    service_id: '',
    title: '',
    description: '',
    scheduled_start: '',
    scheduled_end: '',
    status: 'scheduled',
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    post('/maintenances');
  }

  return (
    <>
      <Head title="Add Maintenance" />
      <Card className="max-w-lg mx-auto p-6 mt-8">
        <h1 className="text-xl font-bold mb-4">Add Maintenance</h1>
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
                        <Select value={data.status} onValueChange={(value) => setData('status', value)}>
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
                        Create
                    </Button>
                </form>
      </Card>
    </>
  );
} 