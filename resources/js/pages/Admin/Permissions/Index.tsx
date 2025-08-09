import { type BreadcrumbItem, type Permission } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/permissions',
    },
    {
        title: 'Permissions',
        href: '/admin/permissions',
    },
];

interface PermissionsIndexProps {
    permissions: {
        data: Permission[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
}

export default function PermissionsIndex({ permissions }: PermissionsIndexProps) {
    const handleDelete = (permission: Permission) => {
        if (confirm(`Are you sure you want to delete the permission "${permission.name}"?`)) {
            router.delete(route('admin.permissions.destroy', permission.id));
        }
    };

    // Group permissions by group_name for better display
    const groupedPermissions = permissions.data.reduce((acc, permission) => {
        const groupName = permission.group_name || 'Other';
        if (!acc[groupName]) {
            acc[groupName] = [];
        }
        acc[groupName].push(permission);
        return acc;
    }, {} as Record<string, Permission[]>);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Permissions Management" />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Permissions</h1>
                        <p className="text-muted-foreground">
                            Manage system permissions and their groups
                        </p>
                    </div>
                    <Link href={route('admin.permissions.create')}>
                        <Button>
                            <PlusIcon className="mr-2 h-4 w-4" />
                            Create Permission
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All Permissions</CardTitle>
                        <CardDescription>
                            A list of all permissions in the system, organized by group.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {permissions.data.length === 0 ? (
                            <div className="text-center py-8">
                                <p className="text-muted-foreground">No permissions found.</p>
                            </div>
                        ) : (
                            <div className="space-y-6">
                                {Object.entries(groupedPermissions).map(([groupName, groupPermissions]) => (
                                    <div key={groupName}>
                                        <div className="flex items-center gap-2 mb-3">
                                            <h3 className="font-medium capitalize">
                                                {groupName.replace('-', ' ')}
                                            </h3>
                                            <Badge variant="outline">
                                                {groupPermissions.length} permissions
                                            </Badge>
                                        </div>
                                        <div className="space-y-2">
                                            {groupPermissions.map((permission) => (
                                                <div
                                                    key={permission.id}
                                                    className="flex items-center justify-between p-3 border rounded-lg"
                                                >
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-3">
                                                            <h4 className="font-medium">{permission.name}</h4>
                                                            <Badge variant="secondary" className="text-xs">
                                                                {permission.guard_name}
                                                            </Badge>
                                                        </div>
                                                        <p className="text-sm text-muted-foreground mt-1">
                                                            Created {new Date(permission.created_at).toLocaleDateString()}
                                                        </p>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Link href={route('admin.permissions.show', permission.id)}>
                                                            <Button variant="outline" size="sm">
                                                                <EyeIcon className="h-4 w-4" />
                                                            </Button>
                                                        </Link>
                                                        <Link href={route('admin.permissions.edit', permission.id)}>
                                                            <Button variant="outline" size="sm">
                                                                <PencilIcon className="h-4 w-4" />
                                                            </Button>
                                                        </Link>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleDelete(permission)}
                                                            className="text-destructive hover:text-destructive"
                                                        >
                                                            <TrashIcon className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Pagination */}
                        {permissions.last_page > 1 && (
                            <div className="flex items-center justify-between mt-6">
                                <div className="text-sm text-muted-foreground">
                                    Showing {((permissions.current_page - 1) * permissions.per_page) + 1} to{' '}
                                    {Math.min(permissions.current_page * permissions.per_page, permissions.total)} of{' '}
                                    {permissions.total} results
                                </div>
                                <div className="flex items-center gap-2">
                                    {permissions.links.map((link, index) => (
                                        <Button
                                            key={index}
                                            variant={link.active ? "default" : "outline"}
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() => link.url && router.get(link.url)}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
