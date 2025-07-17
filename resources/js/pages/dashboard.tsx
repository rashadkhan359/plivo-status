import { ServiceList } from '../components/service-list';
import { IncidentList } from '../components/incident-list';
import AppLayout from '@/layouts/app-layout';

export default function Dashboard({ services = [], incidents = [] }: { services: any[]; incidents: any[] }) {
  return (
    <AppLayout>
      <div className="flex flex-col gap-8 p-4 max-w-5xl mx-auto w-full">
        <h1 className="text-2xl font-bold mb-2">Dashboard Overview</h1>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <h2 className="text-lg font-semibold mb-2">Services</h2>
            <ServiceList initialServices={services} />
          </div>
          <div>
            <h2 className="text-lg font-semibold mb-2">Incidents</h2>
            <IncidentList initialIncidents={incidents} />
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
