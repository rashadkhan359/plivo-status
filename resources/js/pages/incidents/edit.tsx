import { Head, useForm } from '@inertiajs/react';
import { Button, Card, Input, Label, Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui';
import AppLayout from '@/layouts/app-layout';
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
  const { data, setData, put, processing, errors } = useForm({
    service_ids: incident.data.services?.map(s => s.id.toString()) || [],
    title: incident.data.title || '',
    description: incident.data.description || '',
    status: incident.data.status || '',
    severity: incident.data.severity || '',
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    put(`/incidents/${incident.data.id}`);
  }

  const breadcrumbs = [
    { title: 'Incidents', href: '/incidents' },
    { title: incident.data.title, href: `/incidents/${incident.data.id}` },
    { title: 'Edit', href: `/incidents/${incident.data.id}/edit` },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <div className="flex flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
        <Head title={`Edit Incident: ${incident.data.title}`} />
        
        {/* Page Header */}
        <div>
          <h1 className="text-3xl font-bold">Edit Incident</h1>
          <p className="text-muted-foreground mt-2">Update incident details and status</p>
        </div>

        <Card className="p-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid gap-4">
              <div>
                <Label htmlFor="title">Title</Label>
                <Input
                  id="title"
                  value={data.title}
                  onChange={(e) => setData('title', e.target.value)}
                  placeholder="Brief description of the incident"
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
                  rows={4}
                  placeholder="Detailed description of the incident..."
                />
                {errors.description && <p className="text-red-500 text-sm mt-1">{errors.description}</p>}
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label htmlFor="status">Status</Label>
                  <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                    <SelectTrigger>
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
                  {errors.status && <p className="text-red-500 text-sm mt-1">{errors.status}</p>}
                </div>

                <div>
                  <Label htmlFor="severity">Severity</Label>
                  <Select value={data.severity} onValueChange={(value) => setData('severity', value)}>
                    <SelectTrigger>
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
                  {errors.severity && <p className="text-red-500 text-sm mt-1">{errors.severity}</p>}
                </div>
              </div>
            </div>

            <div className="flex gap-4">
              <Button type="submit" disabled={processing}>
                {processing ? 'Updating...' : 'Update Incident'}
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