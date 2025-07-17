export type Service = {
    id: number;
    name: string;
    description: string;
    status: 'operational' | 'degraded' | 'partial_outage' | 'major_outage';
    created_at: string;
    updated_at: string;
};
