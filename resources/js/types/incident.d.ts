export type Incident = {
    id: number;
    title: string;
    description: string | null;
    status: 'investigating' | 'identified' | 'monitoring' | 'resolved';
    severity: 'critical' | 'high' | 'medium' | 'low';
    created_by: number;
    resolved_by?: number;
    resolved_at?: string;
    created_at: string;
    updated_at: string;
    services?: Array<{
        id: number;
        name: string;
        status: string;
        team?: {
            id: number;
            name: string;
            color?: string;
        };
    }>;
    creator?: {
        id: number;
        name: string;
        email: string;
    };
    resolver?: {
        id: number;
        name: string;
        email: string;
    };
};
