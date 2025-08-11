import { type BreadcrumbItem, type Role, type Permission } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { PencilIcon, ArrowLeftIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';

interface RoleShowProps {
    role: Role & { permissions: Permission[] };
}

export default function RoleShow({ role }: RoleShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Admin',
            href: '/admin/roles',
        },
        {
            title: 'Roles',
            href: '/admin/roles',
        },
        {
            title: role.name,
            href: `/admin/roles/${role.id}`,
        },
    ];

    // Group permissions by group_name
    const groupedPermissions = role.permissions.reduce((acc, permission) => {
        const groupName = permission.group_name || 'Other';
        if (!acc[groupName]) {
            acc[groupName] = [];
        }
        acc[groupName].push(permission);
        return acc;
    }, {} as Record<string, Permission[]>);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Role: ${role.name}`} />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{role.name}</h1>
                        <p className="text-muted-foreground">
                            Role details and assigned permissions
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link href={route('admin.roles.index')}>
                            <Button variant="outline">
                                <ArrowLeftIcon className="mr-2 h-4 w-4" />
                                Back to Roles
                            </Button>
                        </Link>
                        <Link href={route('admin.roles.edit', role.id)}>
                            <Button>
                                <PencilIcon className="mr-2 h-4 w-4" />
                                Edit Role
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Role Information</CardTitle>
                            <CardDescription>
                                Basic information about this role.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label className="text-sm font-medium">Name</Label>
                                <p className="text-sm text-muted-foreground mt-1">{role.name}</p>
                            </div>
                            <div>
                                <Label className="text-sm font-medium">Guard</Label>
                                <p className="text-sm text-muted-foreground mt-1">{role.guard_name}</p>
                            </div>
                            <div>
                                <Label className="text-sm font-medium">Created</Label>
                                <p className="text-sm text-muted-foreground mt-1">
                                    {new Date(role.created_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}
                                </p>
                            </div>
                            <div>
                                <Label className="text-sm font-medium">Last Updated</Label>
                                <p className="text-sm text-muted-foreground mt-1">
                                    {new Date(role.updated_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Permission Summary</CardTitle>
                            <CardDescription>
                                Overview of permissions assigned to this role.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">Total Permissions</span>
                                    <Badge variant="secondary">{role.permissions.length}</Badge>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">Permission Groups</span>
                                    <Badge variant="secondary">{Object.keys(groupedPermissions).length}</Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Assigned Permissions</CardTitle>
                        <CardDescription>
                            All permissions currently assigned to this role, organized by group.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {role.permissions.length === 0 ? (
                            <div className="text-center py-8">
                                <p className="text-muted-foreground">No permissions assigned to this role.</p>
                            </div>
                        ) : (
                            <div className="space-y-6">
                                {Object.entries(groupedPermissions).map(([groupName, permissions]) => (
                                    <div key={groupName}>
                                        <h3 className="font-medium mb-3 capitalize">
                                            {groupName.replace('-', ' ')} 
                                            <Badge variant="outline" className="ml-2">
                                                {permissions.length}
                                            </Badge>
                                        </h3>
                                        <div className="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                                            {permissions.map((permission) => (
                                                <div
                                                    key={permission.id}
                                                    className="flex items-center p-2 border rounded-lg"
                                                >
                                                    <span className="text-sm">{permission.name}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

// Helper component for labels
function Label({ className, children }: { className?: string; children: React.ReactNode }) {
    return <label className={className}>{children}</label>;
}
