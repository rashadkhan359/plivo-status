export type Maintenance = {
    id: number;
    title: string;
    description: string;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
    scheduled_start: string;
    scheduled_end: string;
    created_at: string;
    updated_at: string;
    organization: Organization;
};

export type User = {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
};

export type Organization = {
    id: number;
    domain: string;
    services_count: number;
    incidents_count: number;
    maintenances_count: number;
    users: {
        data: User[];
    } | User[];
    name: string;
    slug: string;
    created_at: string;
    users_count?: number;
    incidents?: any[];
    maintenances?: any[];
    services?: any[];
};
