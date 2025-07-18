export type Maintenance = {
    id: number;
    service_id: number | null;
    title: string;
    description: string | null;
    status: 'scheduled' | 'in_progress' | 'completed';
    scheduled_start: string;
    scheduled_end: string;
    created_at: string;
    updated_at: string;
    service?: {
        id: number;
        name: string;
        status: string;
        team?: {
            id: number;
            name: string;
            color?: string;
        };
    };
};
