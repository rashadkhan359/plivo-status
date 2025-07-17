export type Incident = {
    id: number;
    service_id: number;
    title: string;
    description: string | null;
    status: 'investigating' | 'identified' | 'monitoring' | 'resolved';
    severity: 'critical' | 'high' | 'medium' | 'low';
    created_at: string;
    updated_at: string;
};
