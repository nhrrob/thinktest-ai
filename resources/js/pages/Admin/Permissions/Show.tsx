import { type BreadcrumbItem, type Permission, type Role } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { PencilIcon, ArrowLeftIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';

interface PermissionShowProps {
    permission: Permission & { roles: Role[] };
}

export default function PermissionShow({ permission }: PermissionShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Admin',
            href: '/admin/permissions',
        },
        {
            title: 'Permissions',
            href: '/admin/permissions',
        },
        {
            title: permission.name,
            href: `/admin/permissions/${permission.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Permission: ${permission.name}`} />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{permission.name}</h1>
                        <p className="text-muted-foreground">
                            Permission details and assigned roles
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link href={route('admin.permissions.index')}>
                            <Button variant="outline">
                                <ArrowLeftIcon className="mr-2 h-4 w-4" />
                                Back to Permissions
                            </Button>
                        </Link>
                        <Link href={route('admin.permissions.edit', permission.id)}>
                            <Button>
                                <PencilIcon className="mr-2 h-4 w-4" />
                                Edit Permission
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Permission Information</CardTitle>
                            <CardDescription>
                                Basic information about this permission.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label className="text-sm font-medium">Name</Label>
                                <p className="text-sm text-muted-foreground mt-1">{permission.name}</p>
                            </div>
                            <div>
                                <Label className="text-sm font-medium">Group</Label>
                                <p className="text-sm text-muted-foreground mt-1">
                                    {permission.group_name ? (
                                        <Badge variant="outline">
                                            {permission.group_name.replace('-', ' ')}
                                        </Badge>
                                    ) : (
                                        <span className="text-muted-foreground">No group assigned</span>
                                    )}
                                </p>
                            </div>
                            <div>
                                <Label className="text-sm font-medium">Guard</Label>
                                <p className="text-sm text-muted-foreground mt-1">{permission.guard_name}</p>
                            </div>
                            <div>
                                <Label className="text-sm font-medium">Created</Label>
                                <p className="text-sm text-muted-foreground mt-1">
                                    {new Date(permission.created_at).toLocaleDateString('en-US', {
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
                                    {new Date(permission.updated_at).toLocaleDateString('en-US', {
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
                            <CardTitle>Usage Summary</CardTitle>
                            <CardDescription>
                                Overview of how this permission is being used.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">Assigned to Roles</span>
                                    <Badge variant="secondary">{permission.roles.length}</Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Assigned Roles</CardTitle>
                        <CardDescription>
                            All roles that currently have this permission assigned.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {permission.roles.length === 0 ? (
                            <div className="text-center py-8">
                                <p className="text-muted-foreground">This permission is not assigned to any roles.</p>
                            </div>
                        ) : (
                            <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                                {permission.roles.map((role) => (
                                    <Link
                                        key={role.id}
                                        href={route('admin.roles.show', role.id)}
                                        className="block"
                                    >
                                        <div className="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 transition-colors">
                                            <div>
                                                <h4 className="font-medium">{role.name}</h4>
                                                <p className="text-sm text-muted-foreground">
                                                    Created {new Date(role.created_at).toLocaleDateString()}
                                                </p>
                                            </div>
                                            <Badge variant="outline">Role</Badge>
                                        </div>
                                    </Link>
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
