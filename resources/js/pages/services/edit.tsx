import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import React from 'react';
import AppLayout from '@/layouts/app-layout';
import { Service } from '@/types/service';
import { Label } from '@/components/ui/label';

const statusOptions = [
  { value: 'operational', label: 'Operational' },
  { value: 'degraded', label: 'Degraded' },
  { value: 'partial_outage', label: 'Partial Outage' },
  { value: 'major_outage', label: 'Major Outage' },
];

const visibilityOptions = [
  { value: 'public', label: 'Public' },
  { value: 'private', label: 'Private' },
];

interface Props {
  service: { data: Service };
  teams: any[];
}

export default function ServiceEdit({ service, teams }: PageProps<Props>) {
  const { data, setData, patch, processing, errors } = useForm({
    name: service.data.name || '',
    description: service.data.description || '',
    status: service.data.status || 'operational',
    visibility: service.data.visibility || 'public',
    team_id: service.data.team_id?.toString() || 'none',
    order: service.data.order || 0,
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    patch(route('services.update', service.data.id), {
      onSuccess: () => {
        // Redirect to services index on success
      }
    });
  }
  const breadcrumbs = [
    { label: 'Services', href: route('services.index'), title: 'Services' },
    { label: 'Edit Service', href: route('services.edit', service.data.id), title: 'Edit Service' },
  ];

  return (
    <>
      <AppLayout breadcrumbs={breadcrumbs}>
        <Head title="Edit Service" />
        <div className="flex flex-col gap-6 p-6 max-w-2xl mx-auto w-full">
          <div>
            <h1 className="text-3xl font-bold">Edit Service</h1>
            <p className="text-muted-foreground mt-2">Update the service details</p>
          </div>
          <Card className="p-6">
            <h1 className="text-xl font-bold mb-4">Edit Service</h1>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid gap-1.5">
                <Label htmlFor="name">Name</Label>
                <Input
                  id="name"
                  value={data.name}
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('name', e.target.value)}
                />
                {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="description">Description</Label>
                <Input
                  id="description"
                  value={data.description}
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('description', e.target.value)}
                />
                {errors.description && <p className="text-xs text-red-500">{errors.description}</p>}
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="status">Status</Label>
                <Select value={data.status} onValueChange={(value) => setData('status', value as Service['status'])}>
                  <SelectTrigger id="status">
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    {statusOptions.map(option => (
                      <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.status && <p className="text-xs text-red-500">{errors.status}</p>}
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="visibility">Visibility</Label>
                <Select value={data.visibility} onValueChange={(value) => setData('visibility', value as 'public' | 'private')}>
                  <SelectTrigger id="visibility">
                    <SelectValue placeholder="Select visibility" />
                  </SelectTrigger>
                  <SelectContent>
                    {visibilityOptions.map(option => (
                      <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.visibility && <p className="text-xs text-red-500">{errors.visibility}</p>}
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="team_id">Team (Optional)</Label>
                <Select value={data.team_id} onValueChange={(value) => setData('team_id', value)}>
                  <SelectTrigger id="team_id">
                    <SelectValue placeholder="Select team (optional)" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">No Team</SelectItem>
                    {teams.map(team => (
                      <SelectItem key={team.id} value={team.id.toString()}>{team.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.team_id && <p className="text-xs text-red-500">{errors.team_id}</p>}
              </div>
              <div className="grid gap-1.5">
                <Label htmlFor="order">Display Order</Label>
                <Input
                  id="order"
                  type="number"
                  value={data.order}
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('order', parseInt(e.target.value) || 0)}
                />
                {errors.order && <p className="text-xs text-red-500">{errors.order}</p>}
              </div>
              <Button type="submit" disabled={processing}>Update</Button>
            </form>
          </Card>
        </div>
      </AppLayout>
    </>
  );
} 