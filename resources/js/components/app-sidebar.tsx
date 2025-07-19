import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, Settings, Wrench, AlertTriangle, ListChecks, Shield, Users, UserPlus } from 'lucide-react';
import AppLogo from './app-logo';

const footerNavItems: NavItem[] = [
    { title: 'Repository', href: 'https://github.com/laravel/react-starter-kit', icon: Folder },
    { title: 'Documentation', href: 'https://laravel.com/docs/starter-kits#react', icon: BookOpen },
];

export function AppSidebar() {
    const { props } = usePage<SharedData>();
    const { currentRole, currentPermissions } = props.auth;
    
    // Build navigation items based on permissions
    const navItems: NavItem[] = [
        { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
        { title: 'Services', href: '/services', icon: Wrench },
        { title: 'Incidents', href: '/incidents', icon: AlertTriangle },
        { title: 'Maintenances', href: '/maintenances', icon: ListChecks },
    ];

    // Add team management for users with manage_teams permission
    if (currentPermissions?.manage_teams) {
        navItems.push({ title: 'Teams', href: '/teams', icon: Users });
    }

    // Add admin sections for system admins only
    if (currentRole === 'system_admin') {
        navItems.push({ title: 'Organizations', href: route('admin.organizations.index'), icon: Shield });
    }

    // Settings for users with organization management permissions or higher roles
    if (currentPermissions?.manage_organization || currentRole === 'owner' || currentRole === 'admin' || currentRole === 'system_admin') {
        navItems.push({ title: 'Settings', href: '/settings', icon: Settings });
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
                <NavFooter items={footerNavItems} />
            </SidebarFooter>
        </Sidebar>
    );
}
