export type Maintenance = {
    id: number;
    service_id: number;
    title: string;
    description: string | null;
    status: 'scheduled' | 'in_progress' | 'completed';
    scheduled_start: string;
    scheduled_end: string;
    created_at: string;
    updated_at: string;
};
