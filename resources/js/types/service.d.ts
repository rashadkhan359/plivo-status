export type Service = {
    id: number;
    name: string;
    description: string;
    status: 'operational' | 'degraded' | 'partial_outage' | 'major_outage';
    team_id?: number;
    team?: {
        id: number;
        name: string;
        description?: string;
        color?: string;
    };
    visibility: 'public' | 'private';
    order: number;
    created_by: number;
    created_at: string;
    updated_at: string;
};

export type Team = {
    id: number;
    name: string;
    description?: string;
    color?: string;
    organization_id: number;
    created_at: string;
    updated_at: string;
    members?: Array<{
        id: number;
        name: string;
        email: string;
        pivot: {
            role: 'member' | 'lead';
        };
    }>;
    services?: Service[];
};
