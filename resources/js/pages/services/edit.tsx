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

interface Props {
    service: { data: Service };
}

export default function ServiceEdit({ service }: PageProps<Props>) {
  const { data, setData, patch, processing, errors } = useForm({
    name: service.data.name || '',
    description: service.data.description || '',
    status: service.data.status || 'operational',
  });

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    patch(route('services.update', service.data.id));
  }

  return (
    <>
      <AppLayout>
        <Head title="Edit Service" />
        <Card className="max-w-lg mx-auto p-6 mt-8">
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
            <Button type="submit" disabled={processing}>Update</Button>
          </form>
        </Card>
      </AppLayout>
    </>
  );
} 