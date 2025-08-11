import { type BreadcrumbItem, type User, type Role } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon, SaveIcon } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';

interface UserEditProps {
    user: User & { roles: Role[] };
    roles: Role[];
}

type UserForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    roles: number[];
};

export default function UserEdit({ user, roles }: UserEditProps) {
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
            href: `/admin/users/${user.id}/edit`,
        },
    ];

    const { data, setData, put, errors, processing } = useForm<UserForm>({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        roles: user.roles.map(r => r.id),
    });

    const toast = useToast();

    const handleRoleChange = (roleId: number, checked: boolean) => {
        if (checked) {
            setData('roles', [...data.roles, roleId]);
        } else {
            setData('roles', data.roles.filter(id => id !== roleId));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Show loading toast
        const loadingToast = toast.loading('Updating user...');

        put(route('admin.users.update', user.id), {
            onSuccess: () => {
                toast.dismiss(loadingToast);
            },
            onError: () => {
                toast.dismiss(loadingToast);
                toast.error('Please check the form for errors and try again.');
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit User: ${user.name}`} />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Edit User</h1>
                        <p className="text-muted-foreground">
                            Update user information and role assignments
                        </p>
                    </div>
                    <Link href={route('admin.users.index')}>
                        <Button variant="outline">
                            <ArrowLeftIcon className="mr-2 h-4 w-4" />
                            Back to Users
                        </Button>
                    </Link>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>User Information</CardTitle>
                            <CardDescription>
                                Update the user's basic information.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Enter user name"
                                        className={errors.name ? 'border-destructive' : ''}
                                    />
                                    {errors.name && (
                                        <p className="text-sm text-destructive">{errors.name}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="Enter email address"
                                        className={errors.email ? 'border-destructive' : ''}
                                    />
                                    {errors.email && (
                                        <p className="text-sm text-destructive">{errors.email}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="password">Password</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder="Leave blank to keep current password"
                                        className={errors.password ? 'border-destructive' : ''}
                                    />
                                    {errors.password && (
                                        <p className="text-sm text-destructive">{errors.password}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="password_confirmation">Confirm Password</Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        placeholder="Confirm new password"
                                        className={errors.password_confirmation ? 'border-destructive' : ''}
                                    />
                                    {errors.password_confirmation && (
                                        <p className="text-sm text-destructive">{errors.password_confirmation}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Role Assignment</CardTitle>
                            <CardDescription>
                                Update the roles assigned to this user.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {roles.map((role) => (
                                    <div key={role.id} className="flex items-center space-x-2">
                                        <Checkbox
                                            id={`role-${role.id}`}
                                            checked={data.roles.includes(role.id)}
                                            onCheckedChange={(checked) => 
                                                handleRoleChange(role.id, checked as boolean)
                                            }
                                        />
                                        <Label htmlFor={`role-${role.id}`} className="font-medium">
                                            {role.name}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                            {errors.roles && (
                                <p className="text-sm text-destructive mt-2">{errors.roles}</p>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex items-center gap-2">
                        <Button type="submit" disabled={processing}>
                            <SaveIcon className="mr-2 h-4 w-4" />
                            {processing ? 'Updating...' : 'Update User'}
                        </Button>
                        <Link href={route('admin.users.index')}>
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </Link>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
