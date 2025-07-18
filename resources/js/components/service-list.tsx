import { useEffect, useState } from 'react';
import { ServiceCard } from '@/components/service-card';
import { Skeleton } from '@/components/ui/skeleton';
import { Service } from '@/types/service';

export function ServiceList({ initialServices, orgId, orgSlug }: { initialServices: Service[]; orgId?: string; orgSlug?: string }) {
  const [services, setServices] = useState<Service[]>(initialServices);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Sync with prop changes
  useEffect(() => {
    setServices(initialServices);
  }, [initialServices]);

  if (loading) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        {[...Array(3)].map((_, i) => (
          <Skeleton key={i} className="h-24 w-full rounded-lg" />
        ))}
      </div>
    );
  }
  if (error) {
    return <div className="text-red-500 text-sm p-4">{error}</div>;
  }
  if (!services.length) {
    return <div className="text-muted-foreground text-sm p-4">No services found.</div>;
  }
  return (
    <div>
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        {services.map((service) => (
          <ServiceCard key={service.id} service={service} />
        ))}
      </div>
    </div>
  );
} 