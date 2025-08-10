import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import toast from 'react-hot-toast';
import { CheckCircle, XCircle, AlertCircle, Info, X } from 'lucide-react';
import { type SharedData } from '@/types';

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
const CustomToast = ({
  message,
  icon,
  onClose,
  borderColor
}: {
  message: string;
  icon: React.ReactNode;
  onClose: () => void;
  borderColor: string;
}) => (
  <div className="flex items-center justify-between w-full">
    <div className="flex items-center space-x-3">
      {icon}
      <span className="text-sm font-medium">{message}</span>
    </div>
    <button
      onClick={onClose}
      className="ml-4 flex-shrink-0 inline-flex h-6 w-6 items-center justify-center rounded-md text-foreground/70 hover:text-foreground hover:bg-muted/50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
      aria-label="Close notification"
    >
      <X className="h-4 w-4" />
    </button>
  </div>
);

export function useToast() {
  const { props } = usePage<SharedData & FlashMessages>();

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
  }, [props.success, props.error, props.warning, props.info, props.message]);

  const showSuccess = (message: string, options?: ToastOptions) => {
    return toast.custom((t) => (
      <div
        className={`${
          t.visible ? 'animate-enter' : 'animate-leave'
        } max-w-md w-full bg-background shadow-lg rounded-lg pointer-events-auto flex ring-1 ring-black ring-opacity-5 border border-border`}
        style={{
          borderLeftColor: 'hsl(142 76% 36%)', // green-600
          borderLeftWidth: '4px',
        }}
      >
        <div className="flex-1 w-0 p-4">
          <CustomToast
            message={message}
            icon={<CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400" />}
            onClose={() => toast.dismiss(t.id)}
            borderColor="hsl(142 76% 36%)"
          />
        </div>
      </div>
    ), {
      duration: options?.duration || 5000,
      position: options?.position || 'top-center',
    });
  };

  const showError = (message: string, options?: ToastOptions) => {
    return toast.custom((t) => (
      <div
        className={`${
          t.visible ? 'animate-enter' : 'animate-leave'
        } max-w-md w-full bg-background shadow-lg rounded-lg pointer-events-auto flex ring-1 ring-black ring-opacity-5 border border-border`}
        style={{
          borderLeftColor: 'hsl(0 84% 60%)', // red-600
          borderLeftWidth: '4px',
        }}
      >
        <div className="flex-1 w-0 p-4">
          <CustomToast
            message={message}
            icon={<XCircle className="h-5 w-5 text-red-600 dark:text-red-400" />}
            onClose={() => toast.dismiss(t.id)}
            borderColor="hsl(0 84% 60%)"
          />
        </div>
      </div>
    ), {
      duration: options?.duration || 6000,
      position: options?.position || 'top-center',
    });
  };

  const showWarning = (message: string, options?: ToastOptions) => {
    return toast.custom((t) => (
      <div
        className={`${
          t.visible ? 'animate-enter' : 'animate-leave'
        } max-w-md w-full bg-background shadow-lg rounded-lg pointer-events-auto flex ring-1 ring-black ring-opacity-5 border border-border`}
        style={{
          borderLeftColor: 'hsl(45 93% 47%)', // yellow-600
          borderLeftWidth: '4px',
        }}
      >
        <div className="flex-1 w-0 p-4">
          <CustomToast
            message={message}
            icon={<AlertCircle className="h-5 w-5 text-yellow-600 dark:text-yellow-400" />}
            onClose={() => toast.dismiss(t.id)}
            borderColor="hsl(45 93% 47%)"
          />
        </div>
      </div>
    ), {
      duration: options?.duration || 5000,
      position: options?.position || 'top-center',
    });
  };

  const showInfo = (message: string, options?: ToastOptions) => {
    return toast.custom((t) => (
      <div
        className={`${
          t.visible ? 'animate-enter' : 'animate-leave'
        } max-w-md w-full bg-background shadow-lg rounded-lg pointer-events-auto flex ring-1 ring-black ring-opacity-5 border border-border`}
        style={{
          borderLeftColor: 'hsl(221 83% 53%)', // blue-600
          borderLeftWidth: '4px',
        }}
      >
        <div className="flex-1 w-0 p-4">
          <CustomToast
            message={message}
            icon={<Info className="h-5 w-5 text-blue-600 dark:text-blue-400" />}
            onClose={() => toast.dismiss(t.id)}
            borderColor="hsl(221 83% 53%)"
          />
        </div>
      </div>
    ), {
      duration: options?.duration || 5000,
      position: options?.position || 'top-center',
    });
  };

  const showLoading = (message: string, options?: ToastOptions) => {
    return toast.custom((t) => (
      <div
        className={`${
          t.visible ? 'animate-enter' : 'animate-leave'
        } max-w-md w-full bg-background shadow-lg rounded-lg pointer-events-auto flex ring-1 ring-black ring-opacity-5 border border-border`}
        style={{
          borderLeftColor: 'hsl(var(--primary))',
          borderLeftWidth: '4px',
        }}
      >
        <div className="flex-1 w-0 p-4">
          <div className="flex items-center justify-between w-full">
            <div className="flex items-center space-x-3">
              <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-primary"></div>
              <span className="text-sm font-medium">{message}</span>
            </div>
            <button
              onClick={() => toast.dismiss(t.id)}
              className="ml-4 flex-shrink-0 inline-flex h-6 w-6 items-center justify-center rounded-md text-foreground/70 hover:text-foreground hover:bg-muted/50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
              aria-label="Close notification"
            >
              <X className="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    ), {
      duration: options?.duration || Infinity,
      position: options?.position || 'top-center',
    });
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
      error: string | ((error: any) => string);
    },
    options?: ToastOptions
  ) => {
    // Use our custom toast functions for promise handling
    const loadingToast = showLoading(messages.loading, options);

    return promise
      .then((result) => {
        toast.dismiss(loadingToast);
        const successMessage = typeof messages.success === 'function'
          ? messages.success(result)
          : messages.success;
        showSuccess(successMessage, options);
        return result;
      })
      .catch((error) => {
        toast.dismiss(loadingToast);
        const errorMessage = typeof messages.error === 'function'
          ? messages.error(error)
          : messages.error;
        showError(errorMessage, options);
        throw error;
      });
  };

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
