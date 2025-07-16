import { Card } from './ui/card';
import { StatusBadge } from './status-badge';

export function ServiceCard({ service }: { service: { name: string; description?: string; status: string } }) {
  return (
    <Card className="flex flex-col gap-2 p-4 shadow-sm hover:shadow-md transition-shadow duration-200 cursor-pointer">
      <div className="flex items-center justify-between">
        <h3 className="font-semibold text-base truncate" title={service.name}>{service.name}</h3>
        <StatusBadge status={service.status} />
      </div>
      {service.description && (
        <p className="text-sm text-muted-foreground line-clamp-2">{service.description}</p>
      )}
    </Card>
  );
} 