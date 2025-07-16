import { useEffect, useState } from 'react';
import { ServiceCard } from './service-card';
import { Skeleton } from './ui/skeleton';
import { useRealtime } from '../hooks/use-realtime';
import { RealtimeIndicator } from './realtime-indicator';

export function ServiceList({ initialServices, orgId, orgSlug }: { initialServices: any[]; orgId?: string; orgSlug?: string }) {
  const [services, setServices] = useState(initialServices);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const { state, subscribe, unsubscribe } = useRealtime();

  useEffect(() => {
    if (!orgId || !orgSlug) return;
    const handleStatusChanged = (data: any) => {
      setServices((prev) => prev.map((s) => (s.id === data.service.id ? data.service : s)));
    };
    subscribe(`organization.${orgId}`, 'ServiceStatusChanged', handleStatusChanged);
    subscribe(`status.${orgSlug}`, 'ServiceStatusChanged', handleStatusChanged);
    return () => {
      unsubscribe(`organization.${orgId}`, 'ServiceStatusChanged');
      unsubscribe(`status.${orgSlug}`, 'ServiceStatusChanged');
    };
  }, [orgId, orgSlug, subscribe, unsubscribe]);

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
      <div className="mb-2 flex justify-end">
        <RealtimeIndicator state={state} />
      </div>
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        {services.map((service) => (
          <ServiceCard key={service.id} service={service} />
        ))}
      </div>
    </div>
  );
} 