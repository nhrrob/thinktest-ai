import { type BreadcrumbItem, type Role } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/roles',
    },
    {
        title: 'Roles',
        href: '/admin/roles',
    },
];

interface RolesIndexProps {
    roles: {
        data: Role[];
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

export default function RolesIndex({ roles }: RolesIndexProps) {
    const handleDelete = (role: Role) => {
        if (confirm(`Are you sure you want to delete the role "${role.name}"?`)) {
            router.delete(route('admin.roles.destroy', role.id));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles Management" />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Roles</h1>
                        <p className="text-muted-foreground">
                            Manage user roles and their permissions
                        </p>
                    </div>
                    <Link href={route('admin.roles.create')}>
                        <Button>
                            <PlusIcon className="mr-2 h-4 w-4" />
                            Create Role
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All Roles</CardTitle>
                        <CardDescription>
                            A list of all roles in the system with their assigned permissions.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {roles.data.length === 0 ? (
                                <div className="text-center py-8">
                                    <p className="text-muted-foreground">No roles found.</p>
                                </div>
                            ) : (
                                roles.data.map((role) => (
                                    <div
                                        key={role.id}
                                        className="flex items-center justify-between p-4 border rounded-lg"
                                    >
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3">
                                                <h3 className="font-medium">{role.name}</h3>
                                                <Badge variant="secondary">
                                                    {role.permissions?.length || 0} permissions
                                                </Badge>
                                            </div>
                                            <p className="text-sm text-muted-foreground mt-1">
                                                Created {new Date(role.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Link href={route('admin.roles.show', role.id)}>
                                                <Button variant="outline" size="sm">
                                                    <EyeIcon className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Link href={route('admin.roles.edit', role.id)}>
                                                <Button variant="outline" size="sm">
                                                    <PencilIcon className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            {role.name !== 'super-admin' && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleDelete(role)}
                                                    className="text-destructive hover:text-destructive"
                                                >
                                                    <TrashIcon className="h-4 w-4" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>

                        {/* Pagination */}
                        {roles.last_page > 1 && (
                            <div className="flex items-center justify-between mt-6">
                                <div className="text-sm text-muted-foreground">
                                    Showing {((roles.current_page - 1) * roles.per_page) + 1} to{' '}
                                    {Math.min(roles.current_page * roles.per_page, roles.total)} of{' '}
                                    {roles.total} results
                                </div>
                                <div className="flex items-center gap-2">
                                    {roles.links.map((link, index) => (
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
