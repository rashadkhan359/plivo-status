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
}) {
    const [serviceList, setServiceList] = useState<Service[]>(services.data);
    const [incidentList, setIncidentList] = useState<Incident[]>(incidents.data);
    const [maintenanceList, setMaintenanceList] = useState<Maintenance[]>(maintenances.data);
    const [timeline, setTimeline] = useState<IncidentUpdate[]>(updates.data);
    const { state, subscribe, unsubscribe } = useRealtime();

  // Debug realtime connection
  useEffect(() => {
    console.log('Realtime state:', state);
    console.log('Organization slug:', organization?.slug);
  }, [state, organization?.slug]);

  // Test function to verify realtime connection
  const testRealtimeConnection = () => {
    console.log('Testing realtime connection...');
    console.log('Current state:', state);
    console.log('Echo available:', !!window.Echo);
    if (window.Echo) {
      console.log('Echo connector:', window.Echo.connector);
      console.log('Pusher connection state:', window.Echo.connector?.pusher?.connection?.state);
    }
  };

  // Real-time subscriptions
  useEffect(() => {
    if (!organization?.slug) {
      console.log('No organization slug, skipping subscriptions');
      return;
    }
    
    console.log('Setting up realtime subscriptions for:', organization.slug);
    console.log('Current realtime state:', state);
    console.log('Organization details:', {
      id: organization.id,
      name: organization.name,
      slug: organization.slug,
    });
    
    // Only subscribe if we're connected
    if (state !== 'connected') {
      console.log('Not connected yet, will retry subscriptions when connected');
      return;
    }
    
    const channelName = `status.${organization.slug}`;
    console.log('Using channel name:', channelName);
    
    // Use direct Echo approach for better reliability
    if (!window.Echo) {
      console.error('Echo not available');
      return;
    }
    
    const channel = window.Echo.channel(channelName);
    
    // Subscribe to events with dot prefix
    channel.listen('.ServiceStatusChanged', (data: { service: Service }) => {
      console.log('ServiceStatusChanged received:', data);
      setServiceList((prev) => {
        const updated = prev.map((s) => (s.id === data.service.id ? data.service : s));
        console.log('Updated service list:', updated);
        return updated;
      });
    });
    
    channel.listen('.ServiceCreated', (data: { service: Service }) => {
      console.log('ServiceCreated received:', data);
      setServiceList((prev) => {
        const updated = [data.service, ...prev];
        console.log('Updated service list:', updated);
        return updated;
      });
    });
    
    channel.listen('.ServiceUpdated', (data: { service: Service }) => {
      console.log('ServiceUpdated received:', data);
      setServiceList((prev) => {
        const updated = prev.map((s) => (s.id === data.service.id ? data.service : s));
        console.log('Updated service list:', updated);
        return updated;
      });
    });
    
    channel.listen('.IncidentCreated', (data: { incident: Incident }) => {
      console.log('IncidentCreated received:', data);
      setIncidentList((prev) => {
        const updated = [data.incident, ...prev];
        console.log('Updated incident list:', updated);
        return updated;
      });
    });
    
    channel.listen('.IncidentUpdated', (data: { incident: Incident }) => {
      console.log('IncidentUpdated received:', data);
      console.log('Current incident list:', incidentList);
      setIncidentList((prev) => {
        const updated = prev.map((i) => (i.id === data.incident.id ? data.incident : i));
        console.log('Updated incident list:', updated);
        return updated;
      });
    });
    
    channel.listen('.IncidentResolved', (data: { incident: Incident }) => {
      console.log('IncidentResolved received:', data);
      setIncidentList((prev) => {
        const updated = prev.map((i) => (i.id === data.incident.id ? data.incident : i));
        console.log('Updated incident list:', updated);
        return updated;
      });
    });
    
    channel.listen('.MaintenanceScheduled', (data: { maintenance: Maintenance }) => {
      console.log('MaintenanceScheduled received:', data);
      setMaintenanceList((prev) => {
        const updated = [data.maintenance, ...prev];
        console.log('Updated maintenance list:', updated);
        return updated;
      });
    });
    
    channel.listen('.MaintenanceUpdated', (data: { maintenance: Maintenance }) => {
      console.log('MaintenanceUpdated received:', data);
      setMaintenanceList((prev) => {
        const updated = prev.map((m) => (m.id === data.maintenance.id ? data.maintenance : m));
        console.log('Updated maintenance list:', updated);
        return updated;
      });
    });
    
    channel.listen('.MaintenanceStarted', (data: { maintenance: Maintenance }) => {
      console.log('MaintenanceStarted received:', data);
      setMaintenanceList((prev) => {
        const updated = prev.map((m) => (m.id === data.maintenance.id ? data.maintenance : m));
        console.log('Updated maintenance list:', updated);
        return updated;
      });
    });
    
    channel.listen('.MaintenanceCompleted', (data: { maintenance: Maintenance }) => {
      console.log('MaintenanceCompleted received:', data);
      setMaintenanceList((prev) => {
        const updated = prev.map((m) => (m.id === data.maintenance.id ? data.maintenance : m));
        console.log('Updated maintenance list:', updated);
        return updated;
      });
    });
    
    channel.listen('.IncidentUpdateCreated', (data: { incident_update: any }) => {
      console.log('IncidentUpdateCreated received on public page:', data);
      // Transform and add the new update to the timeline
      const newUpdate: IncidentUpdate = {
        id: data.incident_update.id,
        message: data.incident_update.description || data.incident_update.message,
        status: data.incident_update.status,
        created_at: data.incident_update.created_at,
      };
      setTimeline((prev) => {
        const updated = [newUpdate, ...prev];
        console.log('Updated timeline:', updated);
        return updated;
      });
    });
    
    console.log('All subscriptions set up for channel:', channelName);
    
    return () => {
      console.log('Cleaning up realtime subscriptions for:', organization.slug);
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

  // Debug state changes
  useEffect(() => {
    console.log('ServiceList state updated:', serviceList.length, 'services');
  }, [serviceList]);

  useEffect(() => {
    console.log('IncidentList state updated:', incidentList.length, 'incidents');
  }, [incidentList]);

  useEffect(() => {
    console.log('MaintenanceList state updated:', maintenanceList.length, 'maintenances');
  }, [maintenanceList]);

  useEffect(() => {
    console.log('Timeline state updated:', timeline.length, 'updates');
  }, [timeline]);

  // SEO meta tags
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
      <Header />
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div className="flex items-center gap-3">
          <StatusBadge status={overall.status} />
          <span className="text-lg font-semibold">{overall.label}</span>
        </div>
        <div className="flex items-center gap-2">
          <RealtimeIndicator state={state} />
          {import.meta.env.DEV && (
            <Button variant="outline" size="sm" onClick={testRealtimeConnection}>
              Test Connection
            </Button>
          )}
        </div>
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