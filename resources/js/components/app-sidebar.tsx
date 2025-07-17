import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, Settings, Wrench, AlertTriangle, ListChecks, Shield } from 'lucide-react';
import AppLogo from './app-logo';

const adminNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'Organizations', href: route('admin.organizations.index'), icon: Shield },
    { title: 'Maintenance', href: route('admin.maintenance.index'), icon: ListChecks },
    { title: 'Services', href: '/services', icon: Wrench },
    { title: 'Incidents', href: '/incidents', icon: AlertTriangle },
    { title: 'Settings', href: '/settings', icon: Settings },
];

const memberNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'Services', href: '/services', icon: Wrench },
    { title: 'Incidents', href: '/incidents', icon: AlertTriangle },
    { title: 'Maintenance', href: '/maintenances', icon: ListChecks },
    // No settings for members
];

const footerNavItems: NavItem[] = [
    { title: 'Repository', href: 'https://github.com/laravel/react-starter-kit', icon: Folder },
    { title: 'Documentation', href: 'https://laravel.com/docs/starter-kits#react', icon: BookOpen },
];

export function AppSidebar() {
    const { props } = usePage<PageProps>();
    const role = props.auth.user?.role || 'member';
    console.log(role);
    const navItems = role === 'admin' ? adminNavItems : memberNavItems;
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
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
