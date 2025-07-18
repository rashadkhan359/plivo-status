import { Head, useForm } from '@inertiajs/react';
import { Button, Card, Input, Label, Select, SelectContent, SelectItem, SelectTrigger, SelectValue, Badge } from '@/components/ui';
import AppLayout from '@/layouts/app-layout';
import React, { useState } from 'react';
import { Incident } from '@/types/incident';
import { Service } from '@/types/service';
import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';
import { X, Check } from 'lucide-react';

type IncidentStatus = 'investigating' | 'identified' | 'monitoring' | 'resolved';
type IncidentSeverity = 'low' | 'medium' | 'high' | 'critical';

const statusOptions = [
  { value: 'investigating' as IncidentStatus, label: 'Investigating' },
  { value: 'identified' as IncidentStatus, label: 'Identified' },
  { value: 'monitoring' as IncidentStatus, label: 'Monitoring' },
  { value: 'resolved' as IncidentStatus, label: 'Resolved' },
];

const severityOptions = [
  { value: 'low' as IncidentSeverity, label: 'Low' },
  { value: 'medium' as IncidentSeverity, label: 'Medium' },
  { value: 'high' as IncidentSeverity, label: 'High' },
  { value: 'critical' as IncidentSeverity, label: 'Critical' },
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
  const { props } = usePage<SharedData>();
  const user = props.auth?.user;
  const organization = props.auth?.currentOrganization;
  
  // Check if user can change services (admin/owner only)
  const canChangeServices = user && ['owner', 'admin'].includes(user.role || '');
  
  const [selectedServices, setSelectedServices] = useState<Set<string>>(
    new Set(incident.data.services?.map(s => s.id.toString()) || [])
  );
  
  const { data, setData, put, processing, errors } = useForm({
    service_ids: incident.data.services?.map(s => s.id.toString()) || [],
    title: incident.data.title || '',
    description: incident.data.description || '',
    status: (incident.data.status as IncidentStatus) || 'investigating',
    severity: (incident.data.severity as IncidentSeverity) || 'medium',
  });

  // Update form data when selected services change
  React.useEffect(() => {
    setData('service_ids', Array.from(selectedServices));
  }, [selectedServices, setData]);

  const toggleService = (serviceId: string) => {
    const newSelected = new Set(selectedServices);
    if (newSelected.has(serviceId)) {
      newSelected.delete(serviceId);
    } else {
      newSelected.add(serviceId);
    }
    setSelectedServices(newSelected);
  };

  const removeService = (serviceId: string) => {
    const newSelected = new Set(selectedServices);
    newSelected.delete(serviceId);
    setSelectedServices(newSelected);
  };

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
                <Label htmlFor="service_ids">Affected Services</Label>
                <div className="space-y-3">
                  {/* Show currently selected services */}
                  {incident.data.services && incident.data.services.length > 0 ? (
                    <div className="text-sm text-muted-foreground">
                      Currently affecting: <span className="font-medium">{incident.data.services.length} service(s)</span>
                    </div>
                  ) : (
                    <div className="text-sm text-muted-foreground">
                      Currently affecting: <span className="font-medium text-red-500">No services selected</span>
                    </div>
                  )}
                  
                  {canChangeServices ? (
                    <>
                      {/* Selected services badges */}
                      {selectedServices.size > 0 && (
                        <div className="flex flex-wrap gap-2 mb-3">
                          {Array.from(selectedServices).map((serviceId) => {
                            const service = services.data.find(s => s.id.toString() === serviceId);
                            return service ? (
                              <Badge key={serviceId} variant="secondary" className="flex items-center gap-1">
                                {service.name}
                                <button
                                  type="button"
                                  onClick={() => removeService(serviceId)}
                                  className="ml-1 hover:bg-muted-foreground/20 rounded-full p-0.5"
                                >
                                  <X className="h-3 w-3" />
                                </button>
                              </Badge>
                            ) : null;
                          })}
                        </div>
                      )}
                      
                      {/* Service selection list */}
                      <div className="border rounded-md p-3 max-h-48 overflow-y-auto">
                        <div className="flex items-center justify-between mb-2">
                          <div className="text-sm font-medium">Available Services</div>
                          <div className="text-xs text-muted-foreground">
                            {selectedServices.size} of {services.data.length} selected
                          </div>
                        </div>
                        <div className="space-y-2">
                          {services.data.map((service) => (
                            <label
                              key={service.id}
                              className="flex items-center space-x-2 cursor-pointer hover:bg-muted/50 p-2 rounded"
                            >
                              <input
                                type="checkbox"
                                checked={selectedServices.has(service.id.toString())}
                                onChange={() => toggleService(service.id.toString())}
                                className="rounded border-gray-300 text-primary focus:ring-primary"
                              />
                              <span className="text-sm">{service.name}</span>
                              {selectedServices.has(service.id.toString()) && (
                                <Check className="h-4 w-4 text-primary" />
                              )}
                            </label>
                          ))}
                        </div>
                      </div>
                      
                      <p className="text-xs text-muted-foreground">
                        Select multiple services that this incident affects. You can remove services by clicking the X on the badges above.
                      </p>
                    </>
                  ) : (
                    <div className="text-sm text-muted-foreground p-3 bg-muted rounded">
                      Service changes require admin permissions. Currently affecting: {incident.data.services?.length || 0} service(s)
                    </div>
                  )}
                </div>
                {errors.service_ids && <p className="text-red-500 text-sm mt-1">{errors.service_ids}</p>}
              </div>

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
                  <Select value={data.status} onValueChange={(value) => setData('status', value as IncidentStatus)}>
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
                  <Select value={data.severity} onValueChange={(value) => setData('severity', value as IncidentSeverity)}>
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