import { NavAdmin } from '@/components/nav-admin';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, Code, Folder, Home, Key, LayoutGrid, Shield, Users } from 'lucide-react';
import AppLogo from './app-logo';
import AppearanceToggleDropdown from './appearance-dropdown';

const mainNavItems: NavItem[] = [
    {
        title: 'Home',
        href: '/',
        icon: Home,
    },
    {
        title: 'ThinkTest AI',
        href: '/thinktest',
        icon: Code,
    },
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Roles',
        href: '/admin/roles',
        icon: Shield,
    },
    {
        title: 'Permissions',
        href: '/admin/permissions',
        icon: Key,
    },
    {
        title: 'Users',
        href: '/admin/users',
        icon: Users,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/nhrrob/thinktest-ai',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://github.com/nhrrob/thinktest-ai/blob/main/README.md',
        icon: BookOpen,
    },
];

export function AppSidebar() {
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
                <NavMain items={mainNavItems} />
                <NavAdmin items={adminNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <div className="flex items-center justify-between px-2 py-1">
                    <AppearanceToggleDropdown />
                </div>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
