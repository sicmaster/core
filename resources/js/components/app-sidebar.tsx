import { Link } from '@inertiajs/react';
import { LayoutGrid, Users, Shield } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { usePermissions } from '@/hooks/use-permissions';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import type { NavItem } from '@/types';

// Default nav items (non-admin pages)
const defaultNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

// Admin nav items (admin/* pages)
const adminNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: admin.dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Users',
        href: admin.users.index(),
        icon: Users,
    },
    {
        title: 'Roles',
        href: admin.roles.index(),
        icon: Shield,
    },
];

export function AppSidebar() {
    const { currentUrl } = useCurrentUrl();
    const { hasPermission, hasAnyPermission } = usePermissions();
    const isAdminSection = currentUrl.startsWith('/admin');

    // Filter admin nav items based on permissions
    const filteredAdminNavItems = adminNavItems.filter((item) => {
        if (item.title === 'Users') return hasPermission('users.read');
        if (item.title === 'Roles') return hasPermission('roles.read');
        return true; // Dashboard or other items
    });

    const navItems = isAdminSection ? filteredAdminNavItems : defaultNavItems;
    const homeHref = isAdminSection ? admin.dashboard() : dashboard();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={homeHref} prefetch>
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
            </SidebarFooter>
        </Sidebar>
    );
}
