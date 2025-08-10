import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin/users',
    },
    {
        title: 'Users',
        href: '/admin/users',
    },
];

interface UsersIndexProps {
    users: {
        data: User[];
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

export default function UsersIndex({ users }: UsersIndexProps) {
    const toast = useToast();

    const handleDelete = (user: User) => {
        if (confirm(`Are you sure you want to delete the user "${user.name}"?`)) {
            const loadingToast = toast.loading('Deleting user...');

            router.delete(route('admin.users.destroy', user.id), {
                onSuccess: () => {
                    toast.dismiss(loadingToast);
                },
                onError: () => {
                    toast.dismiss(loadingToast);
                    toast.error('Failed to delete user. Please try again.');
                },
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users Management" />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Users</h1>
                        <p className="text-muted-foreground">
                            Manage users and their role assignments
                        </p>
                    </div>
                    <Link href={route('admin.users.create')}>
                        <Button>
                            <PlusIcon className="mr-2 h-4 w-4" />
                            Create User
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>All Users</CardTitle>
                        <CardDescription>
                            A list of all users in the system with their assigned roles.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {users.data.length === 0 ? (
                                <p className="text-center text-muted-foreground py-8">
                                    No users found.
                                </p>
                            ) : (
                                users.data.map((user) => (
                                    <div
                                        key={user.id}
                                        className="flex items-center justify-between p-4 border rounded-lg"
                                    >
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3 mb-2">
                                                <h3 className="font-medium">{user.name}</h3>
                                                <div className="flex gap-1">
                                                    {user.roles?.map((role) => (
                                                        <Badge key={role.id} variant="secondary">
                                                            {role.name}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </div>
                                            <p className="text-sm text-muted-foreground mb-1">
                                                {user.email}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Created {new Date(user.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Link href={route('admin.users.show', user.id)}>
                                                <Button variant="outline" size="sm">
                                                    <EyeIcon className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Link href={route('admin.users.edit', user.id)}>
                                                <Button variant="outline" size="sm">
                                                    <PencilIcon className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            {!user.roles?.some(role => role.name === 'super-admin') && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleDelete(user)}
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
                        {users.last_page > 1 && (
                            <div className="flex items-center justify-between mt-6">
                                <div className="text-sm text-muted-foreground">
                                    Showing {((users.current_page - 1) * users.per_page) + 1} to{' '}
                                    {Math.min(users.current_page * users.per_page, users.total)} of{' '}
                                    {users.total} results
                                </div>
                                <div className="flex items-center gap-2">
                                    {users.links.map((link, index) => (
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
