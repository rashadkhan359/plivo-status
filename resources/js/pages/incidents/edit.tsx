import { Head, useForm } from '@inertiajs/react';
import { Button, Card, Input, Label, Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui';
import React from 'react';
import { Incident } from '@/types/incident';
import { Service } from '@/types/service';

const statusOptions = [
  { value: 'investigating', label: 'Investigating' },
  { value: 'identified', label: 'Identified' },
  { value: 'monitoring', label: 'Monitoring' },
  { value: 'resolved', label: 'Resolved' },
];
const severityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'critical', label: 'Critical' },
];

interface Props {
  incident: {
    data: Incident;
  };
  services: {
    data: Service[];
  };
}

export default function IncidentEdit({ incident, services }: PageProps<Props>) {
  const { data, setData, patch, processing, errors } = useForm({
    service_id: String(incident.data.service_id),
    title: incident.data.title,
    description: incident.data.description || '',
    status: incident.data.status,
    severity: incident.data.severity,
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    patch(`/incidents/${incident.data.id}`);
  }

  return (
    <>
      <Head title="Edit Incident" />
      <Card className="max-w-lg mx-auto p-6 mt-8">
        <h1 className="text-xl font-bold mb-4">Edit Incident</h1>
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
            <Label htmlFor="status">Status</Label>
            <Select value={data.status} onValueChange={(value) => setData('status', value as Incident['status'])}>
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
          <div>
            <Label htmlFor="severity">Severity</Label>
            <Select value={data.severity} onValueChange={(value) => setData('severity', value as Incident['severity'])}>
              <SelectTrigger id="severity">
                <SelectValue placeholder="Select severity" />
              </SelectTrigger>
              <SelectContent>
                {severityOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {errors.severity && <p className="text-xs text-red-500">{errors.severity}</p>}
          </div>
          <Button type="submit" disabled={processing}>
            Update
          </Button>
        </form>
      </Card>
    </>
  );
}