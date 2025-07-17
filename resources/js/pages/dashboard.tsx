import { ServiceList } from '../components/service-list';
import { IncidentList } from '../components/incident-list';
import { MaintenanceList } from '../components/maintenance-list';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Service } from '@/types/service';
import { Incident } from '@/types/incident';
import { Maintenance } from '@/types/maintenance';
import { LayoutGrid, Wrench, AlertTriangle, Calendar } from 'lucide-react';

interface DashboardProps {
  services: { data: Service[] };
  incidents: { data: Incident[] };
  maintenances: { data: Maintenance[] };
  stats: {
    servicesCount: number;
    incidentsCount: number;
    maintenancesCount: number;
  };
}

export default function Dashboard({ services, incidents, maintenances, stats }: DashboardProps) {
  return (
    <AppLayout>
      <div className="flex flex-col gap-6 p-6 max-w-6xl mx-auto w-full">
        {/* Page Header */}
        <div>
          <h1 className="text-3xl font-bold">Dashboard</h1>
          <p className="text-muted-foreground mt-2">Manage your services, incidents, and maintenance schedules</p>
        </div>
        
        {/* Stats Overview */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Services</CardTitle>
              <Wrench className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.servicesCount}</div>
              <p className="text-xs text-muted-foreground">
                Active monitoring services
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Active Incidents</CardTitle>
              <AlertTriangle className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.incidentsCount}</div>
              <p className="text-xs text-muted-foreground">
                Total incidents reported
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Maintenance</CardTitle>
              <Calendar className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.maintenancesCount}</div>
              <p className="text-xs text-muted-foreground">
                Scheduled maintenance windows
              </p>
            </CardContent>
          </Card>
        </div>

        {/* Content Sections */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div>
            <h2 className="text-xl font-semibold mb-6">Services Status</h2>
            <ServiceList initialServices={services.data} />
          </div>
          <div>
            <h2 className="text-xl font-semibold mb-6">Recent Incidents</h2>
            <IncidentList initialIncidents={incidents.data} />
          </div>
        </div>

        <div>
          <h2 className="text-xl font-semibold mb-6">Upcoming Maintenance</h2>
          <MaintenanceList maintenances={maintenances} />
        </div>
      </div>
    </AppLayout>
  );
}
