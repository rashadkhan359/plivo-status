import { useEffect, useMemo, useState } from 'react';
import { ServiceList } from '@/components/service-list';
import { IncidentList } from '@/components/incident-list';
import { MaintenanceList } from '@/components/maintenance-list';
import { IncidentTimeline } from '@/components/incident-timeline';
import { StatusBadge } from '@/components/status-badge';
import { RealtimeIndicator } from '@/components/realtime-indicator';
import { PublicUptimeChart } from '@/components/public-uptime-chart';
import { useRealtime } from '@/hooks/use-realtime';
import { Service } from '@/types/service';
import { Incident } from '@/types/incident';
import { Maintenance } from '@/types/maintenance';
import { IncidentUpdate } from '@/types/incident-update';
import { Organization } from '@/types/organization';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import Header from '@/layouts/home/header';

function getOverallStatus(services: Service[]) {
  if (services.some((s) => s.status === 'major_outage')) return { status: 'major_outage', label: 'Major Outage' };
  if (services.some((s) => s.status === 'partial_outage')) return { status: 'partial_outage', label: 'Partial Outage' };
  if (services.some((s) => s.status === 'degraded')) return { status: 'degraded', label: 'Degraded' };
  return { status: 'operational', label: 'All Systems Operational' };
}

export default function PublicStatusPage({
  organization,
  services,
  incidents,
  maintenances,
  updates = { data: [] },
  uptimeMetrics = [],
  chartData = {},
  auth,
}: {
  organization: Organization;
  services: { data: Service[] };
  incidents: { data: Incident[] };
  maintenances: { data: Maintenance[] };
  updates: { data: IncidentUpdate[] };
  uptimeMetrics?: Array<{
    service_id: number;
    service_name: string;
    uptime_percentage: number;
    period: string;
  }>;
  chartData?: Record<number, Array<{
    date: string;
    uptime: number;
    timestamp: string;
  }>>;
  auth?: any;
}) {
  const [serviceList, setServiceList] = useState<Service[]>(services.data);
  const [incidentList, setIncidentList] = useState<Incident[]>(incidents.data);
  const [maintenanceList, setMaintenanceList] = useState<Maintenance[]>(maintenances.data);
  const [timeline, setTimeline] = useState<IncidentUpdate[]>(updates.data);
  const { state, subscribe, unsubscribe } = useRealtime();


  // Real-time subscriptions
  useEffect(() => {
    if (!organization?.slug) {
      return;
    }

    // Only subscribe if we're connected
    if (state !== 'connected') {
      return;
    }

    const channelName = `status.${organization.slug}`;

    // Use direct Echo approach for better reliability
    if (!window.Echo) {
      console.error('Echo not available');
      return;
    }

    const channel = window.Echo.channel(channelName);

    // Subscribe to events with dot prefix
    channel.listen('.ServiceStatusChanged', (data: { service: Service }) => {
      setServiceList((prev) => {
        const updated = prev.map((s) => (s.id === data.service.id ? data.service : s));
        return updated;
      });
    });

    channel.listen('.ServiceCreated', (data: { service: Service }) => {
      setServiceList((prev) => {
        const updated = [data.service, ...prev];
        return updated;
      });
    });

    channel.listen('.ServiceUpdated', (data: { service: Service }) => {
      setServiceList((prev) => {
        const updated = prev.map((s) => (s.id === data.service.id ? data.service : s));
        return updated;
      });
    });

    channel.listen('.IncidentCreated', (data: { incident: Incident }) => {
      setIncidentList((prev) => {
        const updated = [data.incident, ...prev];
        return updated;
      });
    });

    channel.listen('.IncidentUpdated', (data: { incident: Incident }) => {
      setIncidentList((prev) => {
        const updated = prev.map((i) => (i.id === data.incident.id ? data.incident : i));
        return updated;
      });
    });

    channel.listen('.IncidentResolved', (data: { incident: Incident }) => {
      setIncidentList((prev) => {
        const updated = prev.map((i) => (i.id === data.incident.id ? data.incident : i));
        return updated;
      });
    });

    channel.listen('.MaintenanceScheduled', (data: { maintenance: Maintenance }) => {
      setMaintenanceList((prev) => {
        const updated = [data.maintenance, ...prev];
        return updated;
      });
    });

    channel.listen('.MaintenanceUpdated', (data: { maintenance: Maintenance }) => {
      setMaintenanceList((prev) => {
        const updated = prev.map((m) => (m.id === data.maintenance.id ? data.maintenance : m));
        return updated;
      });
    });

    channel.listen('.MaintenanceStarted', (data: { maintenance: Maintenance }) => {
      setMaintenanceList((prev) => {
        const updated = prev.map((m) => (m.id === data.maintenance.id ? data.maintenance : m));
        return updated;
      });
    });

    channel.listen('.MaintenanceCompleted', (data: { maintenance: Maintenance }) => {
      setMaintenanceList((prev) => {
        const updated = prev.map((m) => (m.id === data.maintenance.id ? data.maintenance : m));
        return updated;
      });
    });

    channel.listen('.IncidentUpdateCreated', (data: { incident_update: any }) => {
      // Transform and add the new update to the timeline
      const newUpdate: IncidentUpdate = {
        id: data.incident_update.id,
        message: data.incident_update.description || data.incident_update.message,
        status: data.incident_update.status,
        created_at: data.incident_update.created_at,
      };
      setTimeline((prev) => {
        const updated = [newUpdate, ...prev];
        return updated;
      });
    });

    return () => {
      // Clean up subscriptions
      channel.stopListening('.ServiceStatusChanged');
      channel.stopListening('.ServiceCreated');
      channel.stopListening('.ServiceUpdated');
      channel.stopListening('.IncidentCreated');
      channel.stopListening('.IncidentUpdated');
      channel.stopListening('.IncidentResolved');
      channel.stopListening('.MaintenanceScheduled');
      channel.stopListening('.MaintenanceUpdated');
      channel.stopListening('.MaintenanceStarted');
      channel.stopListening('.MaintenanceCompleted');
      channel.stopListening('.IncidentUpdateCreated');
    };
  }, [organization?.slug, state]);

  const overall = useMemo(() => getOverallStatus(serviceList), [serviceList]);

  const title = `${organization.name} Status`;
  const description = `Live status for ${organization.name}. View current service status, incidents, and maintenance.`;

  return (
    <div className="flex flex-col gap-8 p-4 max-w-4xl mx-auto w-full">
      <Head>
        <title>{title}</title>
        <meta name="description" content={description} />
        <meta property="og:title" content={title} />
        <meta property="og:description" content={description} />
        <meta property="og:type" content="website" />
        <meta property="og:url" content={typeof window !== 'undefined' ? window.location.href : ''} />
      </Head>
      
      {/* Custom header for authenticated users */}
      {auth?.user ? (
        <div className="flex items-center justify-between py-4 border-b">
          <div className="flex items-center gap-4">
            <Link href={route('home')} className="text-lg font-semibold text-primary">
              StatusPage
            </Link>
            <span className="text-muted-foreground">•</span>
            <span className="text-sm text-muted-foreground">
              Viewing: {organization.name}
            </span>
          </div>
          <div className="flex items-center gap-3">
            <Link href={route('dashboard')}>
              <Button variant="outline" size="sm">
                Dashboard
              </Button>
            </Link>
            <RealtimeIndicator state={state} />
          </div>
        </div>
      ) : (
        <Header />
      )}
      
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div className="flex items-center gap-3">
          <StatusBadge status={overall.status} />
          <span className="text-lg font-semibold">{overall.label}</span>
        </div>
        {!auth?.user && (
          <div className="flex items-center gap-2">
            <RealtimeIndicator state={state} />
          </div>
        )}
      </div>
      <div>
        <h2 className="text-lg font-semibold mb-2">Services</h2>
        <ServiceList initialServices={serviceList} orgSlug={organization.slug} isPublic={true} />
      </div>

      {uptimeMetrics && uptimeMetrics.length > 0 && (
        <div>
          <h2 className="text-lg font-semibold mb-2">Service Uptime Analytics</h2>
          <PublicUptimeChart
            services={uptimeMetrics}
            chartData={chartData}
          />
        </div>
      )}
      <div>
        <h2 className="text-lg font-semibold mb-2">Active Incidents</h2>
        <IncidentList initialIncidents={incidentList} />
      </div>
      <div>
        <h2 className="text-lg font-semibold mb-2">Scheduled Maintenance</h2>
        <MaintenanceList maintenances={{ data: maintenanceList }} />
      </div>
      <div>
        <h2 className="text-lg font-semibold mb-2">Incident History</h2>
        <IncidentTimeline
          updates={timeline}
          enableRealtime={true}
          orgSlug={organization.slug}
          compact={true}
          showIcons={true}
        />
      </div>
      <div className="mt-8 p-6 rounded-xl bg-muted flex flex-col md:flex-row md:items-center gap-4">
        <div className="flex-1">
          <h3 className="font-semibold text-base mb-1">Subscribe to Updates</h3>
          <p className="text-sm text-muted-foreground">Get notified about incidents and maintenance. (Email signup coming soon)</p>
        </div>
        <form className="flex gap-2 w-full md:w-auto" onSubmit={e => e.preventDefault()}>
          <input type="email" className="border rounded-md px-3 py-2 text-sm w-full md:w-64" placeholder="Your email address" disabled />
          <button type="submit" className="bg-primary text-white px-4 py-2 rounded-md opacity-60 cursor-not-allowed" disabled>Subscribe</button>
        </form>
      </div>
    </div>
  );
} 