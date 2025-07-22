import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { StatusBadge } from '@/components/status-badge';
import { Service } from '@/types/service';
import { Link, router } from '@inertiajs/react';
import { usePermissions } from '@/hooks/use-permissions';
import { Edit, Trash2, Settings, Users } from 'lucide-react';
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
    <Card className="group relative overflow-hidden bg-card border-border/50 hover:border-border transition-all duration-300 hover:shadow-lg">
      <div className="p-4">
        {/* Header Row: Name and Status */}
        <div className="flex items-start justify-between gap-3 mb-3">
          <h3 className="font-bold text-lg text-foreground truncate group-hover:text-primary transition-colors duration-200 flex-1" title={service.name}>
            {service.name}
          </h3>
          <div className="flex items-center gap-2 flex-shrink-0">
            <StatusBadge status={service.status} />
            {canManage && (
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button 
                    variant="ghost" 
                    size="sm"
                    className="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity duration-200 hover:bg-accent/50"
                  >
                    <Settings className="h-4 w-4" />
                    <span className="sr-only">Service options</span>
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-48">
                  <DropdownMenuItem asChild>
                    <Link href={route('services.edit', service.id)} className="flex items-center">
                      <Edit className="h-4 w-4 mr-2" />
                      Edit Service
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem 
                    onClick={handleDelete}
                    className="text-destructive focus:text-destructive focus:bg-destructive/10"
                  >
                    <Trash2 className="h-4 w-4 mr-2" />
                    Delete Service
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            )}
          </div>
        </div>

        {/* Content Area */}
        <div className="space-y-2">
          {/* Description */}
          {service.description && (
            <p className="text-sm text-muted-foreground line-clamp-2 leading-relaxed">
              {service.description}
            </p>
          )}

          {/* Team Info */}
          {service.team && (
            <div className="flex items-center gap-2 text-xs text-muted-foreground">
              <Users className="h-3 w-3 flex-shrink-0" />
              <span className="truncate">
                <span className="font-medium text-foreground">{service.team.name}</span>
              </span>
            </div>
          )}
        </div>
      </div>
    </Card>
  );
} 