import { type BreadcrumbItem, type Permission } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';

interface PermissionEditProps {
    permission: Permission;
    groups: string[];
}

type PermissionForm = {
    name: string;
    group_name: string;
};

export default function PermissionEdit({ permission, groups }: PermissionEditProps) {
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
            href: `/admin/permissions/${permission.id}/edit`,
        },
    ];

    const { data, setData, put, errors, processing } = useForm<PermissionForm>({
        name: permission.name,
        group_name: permission.group_name || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.permissions.update', permission.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Permission: ${permission.name}`} />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Edit Permission</h1>
                    <p className="text-muted-foreground">
                        Update permission information and group assignment
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Permission Information</CardTitle>
                            <CardDescription>
                                Update the details for this permission.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Permission Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="e.g., manage users, create posts"
                                    required
                                />
                                <p className="text-sm text-muted-foreground">
                                    Use descriptive names like "manage users" or "create posts"
                                </p>
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="group_name">Permission Group</Label>
                                <div className="flex gap-2">
                                    <Select
                                        value={data.group_name}
                                        onValueChange={(value) => setData('group_name', value)}
                                    >
                                        <SelectTrigger className="flex-1">
                                            <SelectValue placeholder="Select existing group or type new one" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {groups.map((group) => (
                                                <SelectItem key={group} value={group}>
                                                    {group.replace('-', ' ')}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="text-sm text-muted-foreground">
                                    Or enter a new group name:
                                </div>
                                <Input
                                    value={data.group_name}
                                    onChange={(e) => setData('group_name', e.target.value)}
                                    placeholder="e.g., user-management, content-management"
                                />
                                <p className="text-sm text-muted-foreground">
                                    Groups help organize related permissions together
                                </p>
                                <InputError message={errors.group_name} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Current Information</CardTitle>
                            <CardDescription>
                                Current details about this permission.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label className="text-sm font-medium">Guard Name</Label>
                                    <p className="text-sm text-muted-foreground mt-1">{permission.guard_name}</p>
                                </div>
                                <div>
                                    <Label className="text-sm font-medium">Created</Label>
                                    <p className="text-sm text-muted-foreground mt-1">
                                        {new Date(permission.created_at).toLocaleDateString('en-US', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric'
                                        })}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Updating...' : 'Update Permission'}
                        </Button>
                        <Button type="button" variant="outline" onClick={() => window.history.back()}>
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

// Helper component for labels
function Label({ className, children }: { className?: string; children: React.ReactNode }) {
    return <label className={className}>{children}</label>;
}
