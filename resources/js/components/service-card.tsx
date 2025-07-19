import { Card } from '@/components/ui/card';
import { StatusBadge } from '@/components/status-badge';
import { Service } from '@/types/service';
import { Link } from '@inertiajs/react';

export function ServiceCard({ service }: { service: Service }) {
  return (
    <Card className="flex flex-col gap-2 p-4 shadow-sm hover:shadow-md transition-shadow duration-200 cursor-pointer">
      <div className="flex items-center justify-between">
        <Link href={route('services.show', service.id)} className="hover:underline">
          <h3 className="font-semibold text-base truncate" title={service.name}>{service.name}</h3>
        </Link>
          <StatusBadge status={service.status} />
      </div>
      {service.description && (
        <p className="text-sm text-muted-foreground line-clamp-2">{service.description}</p>
      )}
    </Card>
  );
} 