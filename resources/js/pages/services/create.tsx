import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import React from 'react';
import AppLayout from '@/layouts/app-layout';
import { Service, Team } from '@/types/service';
import { Label } from '@/components/ui/label';

interface Props {
    teams: Team[];
}

const statusOptions = [
  { value: 'operational', label: 'Operational' },
  { value: 'degraded', label: 'Degraded' },
  { value: 'partial_outage', label: 'Partial Outage' },
  { value: 'major_outage', label: 'Major Outage' },
];

const visibilityOptions = [
  { value: 'public', label: 'Public', description: 'Visible to all organization members' },
  { value: 'private', label: 'Private', description: 'Only visible to team members' },
];

export default function ServiceCreate({ teams }: PageProps<Props>) {
  const { data, setData, post, processing, errors } = useForm<{
    name: string;
    description: string;
    status: Service['status'];
    team_id: string;
    visibility: Service['visibility'];
    order: string;
  }>({
    name: '',
    description: '',
    status: 'operational',
    team_id: '',
    visibility: 'public',
    order: '',
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    post(route('services.store'));
  }

  const breadcrumbs = [
    {
      title: 'Services',
      href: '/services',
    },
    {
      title: 'Create',
      href: '/services/create',
    },
  ];

  return (
    <>
      <AppLayout breadcrumbs={breadcrumbs}>
        <Head title="Add Service" />
        <div className="flex flex-col gap-6 p-6 max-w-2xl mx-auto w-full">
          <div>
            <h1 className="text-3xl font-bold">Add Service</h1>
            <p className="text-muted-foreground mt-2">Create a new service to monitor</p>
          </div>

          <Card className="p-6">
            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Basic Information */}
              <div className="space-y-4">
                <h2 className="text-lg font-semibold">Basic Information</h2>
                
                <div className="grid gap-2">
                  <Label htmlFor="name">Service Name *</Label>
                  <Input
                    id="name"
                    value={data.name}
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('name', e.target.value)}
                    placeholder="e.g., API Service, Frontend Application"
                  />
                  {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                </div>

                <div className="grid gap-2">
                  <Label htmlFor="description">Description</Label>
                  <Input
                    id="description"
                    value={data.description}
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('description', e.target.value)}
                    placeholder="Brief description of what this service does"
                  />
                  {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                </div>

                <div className="grid gap-2">
                  <Label htmlFor="status">Initial Status *</Label>
                  <Select value={data.status} onValueChange={(value) => setData('status', value as Service['status'])}>
                    <SelectTrigger id="status">
                      <SelectValue placeholder="Select initial status" />
                    </SelectTrigger>
                    <SelectContent>
                      {statusOptions.map(option => (
                        <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.status && <p className="text-sm text-red-500">{errors.status}</p>}
                </div>
              </div>

              {/* Team Assignment */}
              <div className="space-y-4">
                <h2 className="text-lg font-semibold">Team Assignment</h2>
                
                <div className="grid gap-2">
                  <Label htmlFor="team_id">Assign to Team</Label>
                  <Select value={data.team_id} onValueChange={(value) => setData('team_id', value)}>
                    <SelectTrigger id="team_id">
                      <SelectValue placeholder="Select a team (optional)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">No team (unassigned)</SelectItem>
                      {teams.map(team => (
                        <SelectItem key={team.id} value={team.id.toString()}>
                          <div className="flex items-center gap-2">
                            <div 
                              className="w-2 h-2 rounded-full" 
                              style={{ backgroundColor: team.color || '#64748b' }}
                            />
                            {team.name}
                          </div>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.team_id && <p className="text-sm text-red-500">{errors.team_id}</p>}
                </div>
              </div>

              {/* Visibility & Settings */}
              <div className="space-y-4">
                <h2 className="text-lg font-semibold">Visibility & Settings</h2>
                
                <div className="grid gap-2">
                  <Label htmlFor="visibility">Visibility *</Label>
                  <Select value={data.visibility} onValueChange={(value) => setData('visibility', value as Service['visibility'])}>
                    <SelectTrigger id="visibility">
                      <SelectValue placeholder="Select visibility" />
                    </SelectTrigger>
                    <SelectContent>
                      {visibilityOptions.map(option => (
                        <SelectItem key={option.value} value={option.value}>
                          <div>
                            <div className="font-medium">{option.label}</div>
                            <div className="text-sm text-muted-foreground">{option.description}</div>
                          </div>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.visibility && <p className="text-sm text-red-500">{errors.visibility}</p>}
                  {data.visibility === 'private' && !data.team_id && (
                    <p className="text-sm text-amber-600">
                      Private services should be assigned to a team for proper access control.
                    </p>
                  )}
                </div>

                <div className="grid gap-2">
                  <Label htmlFor="order">Display Order</Label>
                  <Input
                    id="order"
                    type="number"
                    min="0"
                    value={data.order}
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('order', e.target.value)}
                    placeholder="Leave empty for automatic ordering"
                  />
                  <p className="text-sm text-muted-foreground">
                    Services are displayed in ascending order. Leave empty to add at the end.
                  </p>
                  {errors.order && <p className="text-sm text-red-500">{errors.order}</p>}
                </div>
              </div>

              {/* Actions */}
              <div className="flex gap-3 pt-4">
                <Button type="submit" disabled={processing}>
                  {processing ? 'Creating...' : 'Create Service'}
                </Button>
                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                  Cancel
                </Button>
              </div>
            </form>
          </Card>
        </div>
      </AppLayout>
    </>
  );
} 