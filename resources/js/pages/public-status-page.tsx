import { useEffect, useMemo, useState } from 'react';
import { ServiceList } from '@/components/service-list';
import { IncidentList } from '@/components/incident-list';
import { MaintenanceList } from '@/components/maintenance-list';
import { IncidentTimeline } from '@/components/incident-timeline';
import { StatusBadge } from '@/components/status-badge';
import { RealtimeIndicator } from '@/components/realtime-indicator';
import { useRealtime } from '@/hooks/use-realtime';
import { Service } from '@/types/service';
import { Incident } from '@/types/incident';
import { Maintenance } from '@/types/maintenance';
import { IncidentUpdate } from '@/types/incident-update';
import { Organization } from '@/types/organization';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { configureEcho } from "@laravel/echo-react";

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
    updates = [],
}: {
    organization: Organization;
    services: { data: Service[] };
    incidents: { data: Incident[] };
    maintenances: { data: Maintenance[] };
    updates: IncidentUpdate[];
}) {
    const [serviceList, setServiceList] = useState<Service[]>(services.data);
    const [incidentList, setIncidentList] = useState<Incident[]>(incidents.data);
    const [maintenanceList, setMaintenanceList] = useState<Maintenance[]>(maintenances.data);
  const [timeline, setTimeline] = useState<IncidentUpdate[]>(updates);
  const { state, subscribe, unsubscribe } = useRealtime();

  // Real-time subscriptions
  useEffect(() => {
    if (!organization?.slug) return;
    subscribe(`status.${organization.slug}`, 'ServiceStatusChanged', (data: { service: Service }) => {
      setServiceList((prev) => prev.map((s) => (s.id === data.service.id ? data.service : s)));
    });
    subscribe(`status.${organization.slug}`, 'IncidentCreated', (data: { incident: Incident }) => {
      setIncidentList((prev) => [data.incident, ...prev]);
    });
    subscribe(`status.${organization.slug}`, 'IncidentUpdated', (data: { incident: Incident }) => {
      setIncidentList((prev) => prev.map((i) => (i.id === data.incident.id ? data.incident : i)));
    });
    subscribe(`status.${organization.slug}`, 'IncidentResolved', (data: { incident: Incident }) => {
      setIncidentList((prev) => prev.map((i) => (i.id === data.incident.id ? data.incident : i)));
    });
    subscribe(`status.${organization.slug}`, 'MaintenanceScheduled', (data: { maintenance: Maintenance }) => {
      setMaintenanceList((prev) => [data.maintenance, ...prev]);
    });
    // Optionally subscribe to incident updates for timeline
    // subscribe(`status.${organization.slug}`, 'IncidentUpdateCreated', ...)
    return () => {
      unsubscribe(`status.${organization.slug}`, 'ServiceStatusChanged');
      unsubscribe(`status.${organization.slug}`, 'IncidentCreated');
      unsubscribe(`status.${organization.slug}`, 'IncidentUpdated');
      unsubscribe(`status.${organization.slug}`, 'IncidentResolved');
      unsubscribe(`status.${organization.slug}`, 'MaintenanceScheduled');
    };
  }, [organization?.slug, subscribe, unsubscribe]);

  const overall = useMemo(() => getOverallStatus(serviceList), [serviceList]);

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
      <header className="w-full py-6 px-4 flex items-center justify-between max-w-6xl mx-auto">
        <div className="flex items-center gap-2 text-xl font-bold">
          <span className="text-primary">StatusPage</span>
        </div>
        <div className="flex gap-2">
          <Link href={route('login')}><Button variant="outline">Login</Button></Link>
          <Link href={route('register')}><Button>Get Started</Button></Link>
        </div>
      </header>
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div className="flex items-center gap-3">
          <StatusBadge status={overall.status} />
          <span className="text-lg font-semibold">{overall.label}</span>
        </div>
        <RealtimeIndicator state={state} />
      </div>
      <div>
        <h2 className="text-lg font-semibold mb-2">Services</h2>
        <ServiceList initialServices={serviceList} orgSlug={organization.slug} />
      </div>
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
        <IncidentTimeline updates={timeline} />
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