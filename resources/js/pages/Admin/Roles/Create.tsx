import { type BreadcrumbItem, type Permission } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import { ChevronDownIcon, ChevronRightIcon } from 'lucide-react';

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
        title: 'Create',
        href: '/admin/roles/create',
    },
];

interface RoleCreateProps {
    permissions: Record<string, Permission[]>;
}

type RoleForm = {
    name: string;
    permissions: number[];
};

export default function RoleCreate({ permissions }: RoleCreateProps) {
    const { data, setData, post, errors, processing } = useForm<RoleForm>({
        name: '',
        permissions: [],
    });

    const [openGroups, setOpenGroups] = useState<Record<string, boolean>>({});

    const toggleGroup = (groupName: string) => {
        setOpenGroups(prev => ({
            ...prev,
            [groupName]: !prev[groupName]
        }));
    };

    const handlePermissionChange = (permissionId: number, checked: boolean) => {
        if (checked) {
            setData('permissions', [...data.permissions, permissionId]);
        } else {
            setData('permissions', data.permissions.filter(id => id !== permissionId));
        }
    };

    const handleGroupToggle = (groupName: string, checked: boolean) => {
        const groupPermissions = permissions[groupName];
        const groupPermissionIds = groupPermissions.map(p => p.id);
        
        if (checked) {
            // Add all permissions from this group
            const newPermissions = [...data.permissions];
            groupPermissionIds.forEach(id => {
                if (!newPermissions.includes(id)) {
                    newPermissions.push(id);
                }
            });
            setData('permissions', newPermissions);
        } else {
            // Remove all permissions from this group
            setData('permissions', data.permissions.filter(id => !groupPermissionIds.includes(id)));
        }
    };

    const isGroupChecked = (groupName: string) => {
        const groupPermissions = permissions[groupName];
        return groupPermissions.every(p => data.permissions.includes(p.id));
    };

    const isGroupIndeterminate = (groupName: string) => {
        const groupPermissions = permissions[groupName];
        const checkedCount = groupPermissions.filter(p => data.permissions.includes(p.id)).length;
        return checkedCount > 0 && checkedCount < groupPermissions.length;
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.roles.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Role" />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Create Role</h1>
                    <p className="text-muted-foreground">
                        Create a new role and assign permissions
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Role Information</CardTitle>
                            <CardDescription>
                                Enter the basic information for the new role.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Role Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Enter role name"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Permissions</CardTitle>
                            <CardDescription>
                                Select the permissions to assign to this role.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {Object.entries(permissions).map(([groupName, groupPermissions]) => (
                                    <Collapsible
                                        key={groupName}
                                        open={openGroups[groupName]}
                                        onOpenChange={() => toggleGroup(groupName)}
                                    >
                                        <div className="flex items-center space-x-2 p-2 border rounded-lg">
                                            <Checkbox
                                                checked={isGroupChecked(groupName)}
                                                onCheckedChange={(checked) => 
                                                    handleGroupToggle(groupName, checked as boolean)
                                                }
                                                className={isGroupIndeterminate(groupName) ? 'data-[state=checked]:bg-primary' : ''}
                                            />
                                            <CollapsibleTrigger className="flex items-center space-x-2 flex-1 text-left">
                                                <span className="font-medium capitalize">
                                                    {groupName.replace('-', ' ')}
                                                </span>
                                                <span className="text-sm text-muted-foreground">
                                                    ({groupPermissions.length} permissions)
                                                </span>
                                                {openGroups[groupName] ? (
                                                    <ChevronDownIcon className="h-4 w-4" />
                                                ) : (
                                                    <ChevronRightIcon className="h-4 w-4" />
                                                )}
                                            </CollapsibleTrigger>
                                        </div>
                                        <CollapsibleContent className="ml-6 mt-2 space-y-2">
                                            {groupPermissions.map((permission) => (
                                                <div key={permission.id} className="flex items-center space-x-2">
                                                    <Checkbox
                                                        checked={data.permissions.includes(permission.id)}
                                                        onCheckedChange={(checked) => 
                                                            handlePermissionChange(permission.id, checked as boolean)
                                                        }
                                                    />
                                                    <Label className="text-sm">
                                                        {permission.name}
                                                    </Label>
                                                </div>
                                            ))}
                                        </CollapsibleContent>
                                    </Collapsible>
                                ))}
                            </div>
                            <InputError message={errors.permissions} />
                        </CardContent>
                    </Card>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Creating...' : 'Create Role'}
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
