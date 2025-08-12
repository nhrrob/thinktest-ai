import { AlertTriangleIcon } from 'lucide-react';
import * as React from 'react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

export interface ConfirmationDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'destructive' | 'default';
    onConfirm: () => void;
    onCancel?: () => void;
    loading?: boolean;
}

export function ConfirmationDialog({
    open,
    onOpenChange,
    title,
    description,
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    variant = 'destructive',
    onConfirm,
    onCancel,
    loading = false,
}: ConfirmationDialogProps) {
    const handleCancel = () => {
        onCancel?.();
        onOpenChange(false);
    };

    const handleConfirm = () => {
        onConfirm();
        // Don't close automatically - let the parent handle it based on success/failure
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <div className="flex items-center gap-3">
                        {variant === 'destructive' && (
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                                <AlertTriangleIcon className="h-5 w-5 text-red-600 dark:text-red-400" />
                            </div>
                        )}
                        <div className="flex-1">
                            <DialogTitle className="text-left">{title}</DialogTitle>
                        </div>
                    </div>
                </DialogHeader>

                <DialogDescription className="text-left text-sm text-muted-foreground">{description}</DialogDescription>

                <DialogFooter className="gap-2 sm:gap-2">
                    <Button variant="secondary" onClick={handleCancel} disabled={loading} className="flex-1 sm:flex-none">
                        {cancelText}
                    </Button>
                    <Button variant={variant} onClick={handleConfirm} disabled={loading} className="flex-1 sm:flex-none">
                        {loading ? 'Processing...' : confirmText}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

// Hook for easier usage
export function useConfirmationDialog() {
    const [isOpen, setIsOpen] = React.useState(false);
    const [config, setConfig] = React.useState<Omit<ConfirmationDialogProps, 'open' | 'onOpenChange'>>({
        title: '',
        description: '',
        onConfirm: () => {},
    });

    // Use useRef to store the latest onConfirm callback to avoid stale closures
    const onConfirmRef = React.useRef<() => void>(() => {});
    // Track if we're programmatically closing to prevent onOpenChange conflicts
    const isProgrammaticallyClosing = React.useRef(false);

    const openDialog = (dialogConfig: Omit<ConfirmationDialogProps, 'open' | 'onOpenChange'>) => {
        console.log('Opening confirmation dialog:', dialogConfig.title);
        // Store the onConfirm callback in a ref to ensure we always have the latest version
        onConfirmRef.current = dialogConfig.onConfirm;

        // Set the config with a wrapper function that calls the ref
        setConfig({
            ...dialogConfig,
            onConfirm: () => {
                console.log('Executing onConfirm for dialog:', dialogConfig.title);
                onConfirmRef.current();
            },
        });
        setIsOpen(true);
    };

    const closeDialog = () => {
        console.log('Closing confirmation dialog');
        // Mark that we're programmatically closing
        isProgrammaticallyClosing.current = true;
        setIsOpen(false);
        // Clear the ref when closing to prevent memory leaks
        onConfirmRef.current = () => {};
        // Clear the config to ensure fresh state on next open
        setConfig({
            title: '',
            description: '',
            onConfirm: () => {},
        });
        // Reset the flag after a brief delay
        setTimeout(() => {
            isProgrammaticallyClosing.current = false;
        }, 150);
    };

    const handleOpenChange = React.useCallback((open: boolean) => {
        // Skip onOpenChange if we're programmatically closing
        if (isProgrammaticallyClosing.current) {
            return;
        }
        console.log('Dialog onOpenChange called with:', open);
        setIsOpen(open);
        if (!open) {
            // Clear the ref when dialog is closed via onOpenChange
            onConfirmRef.current = () => {};
        }
    }, []);

    const dialogProps: ConfirmationDialogProps = {
        ...config,
        open: isOpen,
        onOpenChange: handleOpenChange,
    };

    // Backward-compatible component for existing pages
    const Instance: React.FC = () => <ConfirmationDialog {...dialogProps} />;

    return {
        openDialog,
        closeDialog,
        dialogProps,
        // For backward compatibility with existing usages
        ConfirmationDialog: Instance,
        isOpen,
    };
}
