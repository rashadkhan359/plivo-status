import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { StatusBadge } from '@/components/status-badge';
import { Service } from '@/types/service';
import { Link, router } from '@inertiajs/react';
import { usePermissions } from '@/hooks/use-permissions';
import { Edit, Trash2, Settings } from 'lucide-react';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuTrigger,
  DropdownMenuSeparator
} from '@/components/ui/dropdown-menu';

export function ServiceCard({ service, isPublic = false }: { service: Service; isPublic?: boolean }) {
  const permissions = usePermissions();
  
  const canManage = !isPublic && permissions.canManageService(service);
  const canAccessService = permissions.canAccessService(service.id);
  
  const handleDelete = () => {
    if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
      router.delete(route('services.destroy', service.id));
    }
  };
  
  if (!canAccessService) {
    return null; // Don't render services the user can't access
  }

  return (
    <Card className="flex flex-col gap-2 p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
      <div className="flex items-center justify-between">
        <div className="flex-1">
          <h3 className="font-semibold text-base truncate" title={service.name}>{service.name}</h3>
        </div>
        <div className="flex items-center gap-2">
          <StatusBadge status={service.status} />
          {canManage && (
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm">
                  <Settings className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem asChild>
                  <Link href={route('services.edit', service.id)} className="flex items-center">
                    <Edit className="h-4 w-4 mr-2" />
                    Edit Service
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem 
                  onClick={handleDelete}
                  className="text-red-600 focus:text-red-600"
                >
                  <Trash2 className="h-4 w-4 mr-2" />
                  Delete Service
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          )}
        </div>
      </div>
      {service.description && (
        <p className="text-sm text-muted-foreground line-clamp-2">{service.description}</p>
      )}
      {service.team && (
        <div className="flex items-center gap-2 text-xs text-muted-foreground">
          <span>Team:</span>
          <span className="font-medium">{service.team.name}</span>
        </div>
      )}
    </Card>
  );
} 