import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
    currentOrganization?: {
        id: number;
        name: string;
        slug: string;
        domain?: string;
        settings: Record<string, any>;
        timezone: string;
        created_at: string;
        updated_at: string;
    };
    currentRole?: 'owner' | 'admin' | 'team_lead' | 'member';
    currentPermissions?: {
        manage_organization?: boolean;
        manage_users?: boolean;
        manage_teams?: boolean;
        manage_services?: boolean;
        manage_incidents?: boolean;
        manage_maintenance?: boolean;
        view_analytics?: boolean;
    };
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    auth: Auth;
    name: string;
    quote: {
        message: string;
        author: string;
    };
    ziggy: {
        location: string;
        query: Record<string, string>;
        params: Record<string, string>;
        route: string;
    };
    sidebarOpen: boolean;
    [key: string]: any;
}

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    avatar?: string;
    organization_id?: number; // Legacy field
    role?: string; // Legacy field
}
