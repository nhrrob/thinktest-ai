import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle, Info, X, XCircle } from 'lucide-react';
import { useEffect } from 'react';
import toast from 'react-hot-toast';

interface FlashMessages {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
    message?: string; // Legacy support for existing controllers
}

interface ToastOptions {
    duration?: number;
    position?: 'top-left' | 'top-center' | 'top-right' | 'bottom-left' | 'bottom-center' | 'bottom-right';
}

// Custom toast component with close button
const CustomToast = ({ message, icon, onClose }: { message: string; icon: React.ReactNode; onClose: () => void }) => (
    <div className="flex w-full items-center justify-between">
        <div className="flex items-center space-x-3">
            {icon}
            <span className="text-sm font-medium">{message}</span>
        </div>
        <button
            onClick={onClose}
            className="ml-4 inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-md text-foreground/70 transition-colors duration-200 hover:bg-muted/50 hover:text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
            aria-label="Close notification"
        >
            <X className="h-4 w-4" />
        </button>
    </div>
);

export function useToast() {
    const { props } = usePage<SharedData & FlashMessages>();

    const showSuccess = (message: string, options?: ToastOptions) => {
        return toast.custom(
            (t) => (
                <div
                    className={`${
                        t.visible ? 'animate-enter' : 'animate-leave'
                    } ring-opacity-5 pointer-events-auto flex w-full max-w-md rounded-lg border border-border bg-background shadow-lg ring-1 ring-black`}
                    style={{
                        borderLeftColor: 'hsl(142 76% 36%)', // green-600
                        borderLeftWidth: '4px',
                    }}
                >
                    <div className="w-0 flex-1 p-4">
                        <CustomToast
                            message={message}
                            icon={<CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400" />}
                            onClose={() => toast.dismiss(t.id)}
                            borderColor="hsl(142 76% 36%)"
                        />
                    </div>
                </div>
            ),
            {
                duration: options?.duration || 5000,
                position: options?.position || 'top-center',
                // Generate unique ID to prevent deduplication
                id: `success-${Date.now()}-${Math.random()}`,
                // Ensure proper exit animation timing
                style: {
                    animationDuration: '500ms',
                },
            },
        );
    };

    const showError = (message: string, options?: ToastOptions) => {
        return toast.custom(
            (t) => (
                <div
                    className={`${
                        t.visible ? 'animate-enter' : 'animate-leave'
                    } ring-opacity-5 pointer-events-auto flex w-full max-w-md rounded-lg border border-border bg-background shadow-lg ring-1 ring-black`}
                    style={{
                        borderLeftColor: 'hsl(0 84% 60%)', // red-600
                        borderLeftWidth: '4px',
                    }}
                >
                    <div className="w-0 flex-1 p-4">
                        <CustomToast
                            message={message}
                            icon={<XCircle className="h-5 w-5 text-red-600 dark:text-red-400" />}
                            onClose={() => toast.dismiss(t.id)}
                            borderColor="hsl(0 84% 60%)"
                        />
                    </div>
                </div>
            ),
            {
                duration: options?.duration || 6000,
                position: options?.position || 'top-center',
                // Generate unique ID to prevent deduplication
                id: `error-${Date.now()}-${Math.random()}`,
                // Ensure proper exit animation timing
                style: {
                    animationDuration: '500ms',
                },
            },
        );
    };

    const showWarning = (message: string, options?: ToastOptions) => {
        return toast.custom(
            (t) => (
                <div
                    className={`${
                        t.visible ? 'animate-enter' : 'animate-leave'
                    } ring-opacity-5 pointer-events-auto flex w-full max-w-md rounded-lg border border-border bg-background shadow-lg ring-1 ring-black`}
                    style={{
                        borderLeftColor: 'hsl(45 93% 47%)', // yellow-600
                        borderLeftWidth: '4px',
                    }}
                >
                    <div className="w-0 flex-1 p-4">
                        <CustomToast
                            message={message}
                            icon={<AlertCircle className="h-5 w-5 text-yellow-600 dark:text-yellow-400" />}
                            onClose={() => toast.dismiss(t.id)}
                            borderColor="hsl(45 93% 47%)"
                        />
                    </div>
                </div>
            ),
            {
                duration: options?.duration || 5000,
                position: options?.position || 'top-center',
                // Generate unique ID to prevent deduplication
                id: `warning-${Date.now()}-${Math.random()}`,
                // Ensure proper exit animation timing
                style: {
                    animationDuration: '500ms',
                },
            },
        );
    };

    const showInfo = (message: string, options?: ToastOptions) => {
        return toast.custom(
            (t) => (
                <div
                    className={`${
                        t.visible ? 'animate-enter' : 'animate-leave'
                    } ring-opacity-5 pointer-events-auto flex w-full max-w-md rounded-lg border border-border bg-background shadow-lg ring-1 ring-black`}
                    style={{
                        borderLeftColor: 'hsl(221 83% 53%)', // blue-600
                        borderLeftWidth: '4px',
                    }}
                >
                    <div className="w-0 flex-1 p-4">
                        <CustomToast
                            message={message}
                            icon={<Info className="h-5 w-5 text-blue-600 dark:text-blue-400" />}
                            onClose={() => toast.dismiss(t.id)}
                            borderColor="hsl(221 83% 53%)"
                        />
                    </div>
                </div>
            ),
            {
                duration: options?.duration || 5000,
                position: options?.position || 'top-center',
                // Generate unique ID to prevent deduplication
                id: `info-${Date.now()}-${Math.random()}`,
                // Ensure proper exit animation timing
                style: {
                    animationDuration: '500ms',
                },
            },
        );
    };

    const showLoading = (message: string, options?: ToastOptions) => {
        return toast.custom(
            (t) => (
                <div
                    className={`${
                        t.visible ? 'animate-enter' : 'animate-leave'
                    } ring-opacity-5 pointer-events-auto flex w-full max-w-md rounded-lg border border-border bg-background shadow-lg ring-1 ring-black`}
                    style={{
                        borderLeftColor: 'hsl(var(--primary))',
                        borderLeftWidth: '4px',
                    }}
                >
                    <div className="w-0 flex-1 p-4">
                        <div className="flex w-full items-center justify-between">
                            <div className="flex items-center space-x-3">
                                <div className="h-5 w-5 animate-spin rounded-full border-b-2 border-primary"></div>
                                <span className="text-sm font-medium">{message}</span>
                            </div>
                            <button
                                onClick={() => toast.dismiss(t.id)}
                                className="ml-4 inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-md text-foreground/70 transition-colors duration-200 hover:bg-muted/50 hover:text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                                aria-label="Close notification"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            ),
            {
                duration: options?.duration || Infinity,
                position: options?.position || 'top-center',
                // Ensure proper exit animation timing
                style: {
                    animationDuration: '500ms',
                },
            },
        );
    };

    const dismiss = (toastId?: string) => {
        if (toastId) {
            toast.dismiss(toastId);
        } else {
            toast.dismiss();
        }
    };

    const promise = <T,>(
        promise: Promise<T>,
        messages: {
            loading: string;
            success: string | ((data: T) => string);
            error: string | ((error: Error) => string);
        },
        options?: ToastOptions,
    ) => {
        // Use our custom toast functions for promise handling
        const loadingToast = showLoading(messages.loading, options);

        return promise
            .then((result) => {
                toast.dismiss(loadingToast);
                const successMessage = typeof messages.success === 'function' ? messages.success(result) : messages.success;
                showSuccess(successMessage, options);
                return result;
            })
            .catch((error) => {
                toast.dismiss(loadingToast);
                const errorMessage = typeof messages.error === 'function' ? messages.error(error) : messages.error;
                showError(errorMessage, options);
                throw error;
            });
    };

    // Auto-display flash messages from Laravel
    useEffect(() => {
        const { success, error, warning, info, message } = props;

        if (success) {
            showSuccess(success);
        }

        if (error) {
            showError(error);
        }

        if (warning) {
            showWarning(warning);
        }

        if (info) {
            showInfo(info);
        }

        // Legacy support for 'message' flash key
        if (message && !success && !error && !warning && !info) {
            showSuccess(message);
        }
    }, [props, showSuccess, showError, showWarning, showInfo]);

    return {
        success: showSuccess,
        error: showError,
        warning: showWarning,
        info: showInfo,
        loading: showLoading,
        dismiss,
        promise,
    };
}

export default useToast;
