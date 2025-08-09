import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavAdmin({ items = [] }: { items: NavItem[] }) {
    const { auth } = usePage<SharedData>().props;
    const page = usePage();
    
    // Check if user has admin permissions
    const canAccessAdmin = auth.user.roles?.some(role => 
        role.name === 'super-admin' || role.name === 'admin'
    ) || auth.user.permissions?.some(permission => 
        permission.name === 'access admin panel'
    );

    // Don't render admin navigation if user doesn't have access
    if (!canAccessAdmin) {
        return null;
    }

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Administration</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    // Check specific permissions for each admin item
                    let hasPermission = true;
                    
                    if (item.href.includes('/roles')) {
                        hasPermission = auth.user.permissions?.some(permission =>
                            ['view roles', 'create roles', 'edit roles', 'delete roles'].includes(permission.name)
                        ) || auth.user.roles?.some(role =>
                            role.name === 'super-admin' || role.name === 'admin'
                        ) || false;
                    } else if (item.href.includes('/permissions')) {
                        hasPermission = auth.user.permissions?.some(permission =>
                            ['view permissions', 'create permissions', 'edit permissions', 'delete permissions'].includes(permission.name)
                        ) || auth.user.roles?.some(role =>
                            role.name === 'super-admin' || role.name === 'admin'
                        ) || false;
                    } else if (item.href.includes('/users')) {
                        hasPermission = auth.user.permissions?.some(permission =>
                            ['view users', 'create users', 'edit users', 'delete users'].includes(permission.name)
                        ) || auth.user.roles?.some(role =>
                            role.name === 'super-admin' || role.name === 'admin'
                        ) || false;
                    }

                    if (!hasPermission) {
                        return null;
                    }

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton asChild isActive={page.url.startsWith(item.href)} tooltip={{ children: item.title }}>
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
