import { type BreadcrumbItem, type User, type Role, type Permission } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { PencilIcon, ArrowLeftIcon, MailIcon, CalendarIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';

interface UserShowProps {
    user: User & { roles: (Role & { permissions: Permission[] })[] };
}

export default function UserShow({ user }: UserShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Admin',
            href: '/admin/users',
        },
        {
            title: 'Users',
            href: '/admin/users',
        },
        {
            title: user.name,
            href: `/admin/users/${user.id}`,
        },
    ];

    // Get all unique permissions from user's roles
    const allPermissions = user.roles.reduce((acc, role) => {
        role.permissions.forEach(permission => {
            if (!acc.find(p => p.id === permission.id)) {
                acc.push(permission);
            }
        });
        return acc;
    }, [] as Permission[]);

    // Group permissions by group_name
    const groupedPermissions = allPermissions.reduce((acc, permission) => {
        const groupName = permission.group_name || 'Other';
        if (!acc[groupName]) {
            acc[groupName] = [];
        }
        acc[groupName].push(permission);
        return acc;
    }, {} as Record<string, Permission[]>);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`User: ${user.name}`} />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{user.name}</h1>
                        <p className="text-muted-foreground">
                            User details and assigned roles
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link href={route('admin.users.index')}>
                            <Button variant="outline">
                                <ArrowLeftIcon className="mr-2 h-4 w-4" />
                                Back to Users
                            </Button>
                        </Link>
                        <Link href={route('admin.users.edit', user.id)}>
                            <Button>
                                <PencilIcon className="mr-2 h-4 w-4" />
                                Edit User
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* User Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>User Information</CardTitle>
                            <CardDescription>
                                Basic information about this user
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center gap-3">
                                <MailIcon className="h-4 w-4 text-muted-foreground" />
                                <span className="text-sm">{user.email}</span>
                            </div>
                            <div className="flex items-center gap-3">
                                <CalendarIcon className="h-4 w-4 text-muted-foreground" />
                                <span className="text-sm">
                                    Created {new Date(user.created_at).toLocaleDateString()}
                                </span>
                            </div>
                            {user.email_verified_at && (
                                <div className="flex items-center gap-3">
                                    <Badge variant="secondary">Email Verified</Badge>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Assigned Roles */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Assigned Roles</CardTitle>
                            <CardDescription>
                                Roles currently assigned to this user
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {user.roles.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No roles assigned to this user.
                                </p>
                            ) : (
                                <div className="space-y-3">
                                    {user.roles.map((role) => (
                                        <div key={role.id} className="flex items-center justify-between p-3 border rounded-lg">
                                            <div>
                                                <h4 className="font-medium">{role.name}</h4>
                                                <p className="text-sm text-muted-foreground">
                                                    {role.permissions.length} permission{role.permissions.length !== 1 ? 's' : ''}
                                                </p>
                                            </div>
                                            <Link href={route('roles.show', role.id)}>
                                                <Button variant="outline" size="sm">
                                                    View Role
                                                </Button>
                                            </Link>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Effective Permissions */}
                {Object.keys(groupedPermissions).length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Effective Permissions</CardTitle>
                            <CardDescription>
                                All permissions granted to this user through their assigned roles
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-6">
                                {Object.entries(groupedPermissions).map(([groupName, permissions]) => (
                                    <div key={groupName}>
                                        <h4 className="font-medium mb-3 text-sm uppercase tracking-wide text-muted-foreground">
                                            {groupName}
                                        </h4>
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                            {permissions.map((permission) => (
                                                <Badge key={permission.id} variant="outline">
                                                    {permission.name}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
