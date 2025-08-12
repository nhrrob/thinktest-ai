import { ConfirmationDialog, useConfirmationDialog } from '@/components/confirmation-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/hooks/use-toast';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Role } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { EyeIcon, PencilIcon, PlusIcon, TrashIcon } from 'lucide-react';
import React from 'react';
import toast from 'react-hot-toast';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Admin', href: route('admin.users.index') },
    { title: 'Roles', href: route('admin.roles.index') },
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
    const { openDialog, closeDialog, dialogProps, isOpen } = useConfirmationDialog();
    const { error: showError } = useToast();
    const [isDeleting, setIsDeleting] = React.useState(false);

    // Debug: Log when component re-renders
    console.log('RolesIndex rendered, modal open:', isOpen, 'isDeleting:', isDeleting);

    // Clear loading state when modal is closed to avoid intermediate open:true/loading:false frame
    React.useEffect(() => {
        if (!isOpen && isDeleting) {
            console.log('Modal closed, clearing loading state');
            setIsDeleting(false);
        }
    }, [isOpen, isDeleting]);

    const handleDelete = (role: Role) => {
        let loadingToast: string | undefined;

        console.log('Opening delete dialog for role:', role.name, 'ID:', role.id);

        openDialog({
            title: 'Delete Role',
            description: `Are you sure you want to delete the role "${role.name}"? This action cannot be undone.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'destructive',
            loading: isDeleting,
            onConfirm: () => {
                console.log('Delete confirmed for role:', role.name, 'ID:', role.id);
                setIsDeleting(true);
                loadingToast = toast.loading('Deleting role...');

                router.delete(route('admin.roles.destroy', role.id), {
                    onStart: () => {
                        console.log('Request started for role:', role.name);
                    },
                    onProgress: (progress) => {
                        console.log('Request progress for role:', role.name, progress);
                    },
                    onSuccess: () => {
                        console.log('Role deletion successful for:', role.name);
                        if (loadingToast) toast.dismiss(loadingToast);

                        // Close dialog and let effect clear loading once closed
                        closeDialog();
                    },
                    onError: (errors: Record<string, string>) => {
                        console.log('Role deletion failed for:', role.name, 'Errors:', errors);
                        if (loadingToast) toast.dismiss(loadingToast);

                        // Handle validation errors from the backend
                        if (errors && typeof errors === 'object' && Object.keys(errors).length > 0) {
                            // Get the error message from the 'role' field or any other field
                            let errorMessage = 'Failed to delete role. Please try again.';

                            if (errors.role) {
                                errorMessage = errors.role;
                            } else if (errors.error) {
                                errorMessage = errors.error;
                            } else {
                                // Fallback to first available error message
                                const firstError = Object.values(errors)[0];
                                if (firstError && typeof firstError === 'string') {
                                    errorMessage = firstError;
                                }
                            }

                            // Show error toast using useToast hook for proper styling
                            showError(errorMessage);
                        }

                        // Close dialog and let effect clear loading once closed
                        closeDialog();
                    },
                    onFinish: () => {
                        console.log('Role deletion request finished for:', role.name);
                        // Ensure loading toast is always dismissed
                        if (loadingToast) toast.dismiss(loadingToast);
                    },
                });
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles Management" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Roles</h1>
                        <p className="text-muted-foreground">Manage user roles and their permissions</p>
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
                        <CardDescription>A list of all roles in the system with their assigned permissions.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {roles.data.length === 0 ? (
                                <div className="py-8 text-center">
                                    <p className="text-muted-foreground">No roles found.</p>
                                </div>
                            ) : (
                                roles.data.map((role) => (
                                    <div key={role.id} className="flex items-center justify-between rounded-lg border p-4">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3">
                                                <h3 className="font-medium">{role.name}</h3>
                                                <Badge variant="secondary">{role.permissions?.length || 0} permissions</Badge>
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">
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
                            <div className="mt-6 flex items-center justify-between">
                                <div className="text-sm text-muted-foreground">
                                    Showing {(roles.current_page - 1) * roles.per_page + 1} to{' '}
                                    {Math.min(roles.current_page * roles.per_page, roles.total)} of {roles.total} results
                                </div>
                                <div className="flex items-center gap-2">
                                    {roles.links.map((link, index) => (
                                        <Button
                                            key={index}
                                            variant={link.active ? 'default' : 'outline'}
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

            <ConfirmationDialog {...dialogProps} />
        </AppLayout>
    );
}
