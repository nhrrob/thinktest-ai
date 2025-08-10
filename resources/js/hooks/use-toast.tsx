import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import toast from 'react-hot-toast';
import { CheckCircle, XCircle, AlertCircle, Info } from 'lucide-react';
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
    return toast.success(message, {
      duration: options?.duration || 5000,
      position: options?.position || 'top-center',
      icon: <CheckCircle className="h-5 w-5 text-green-600" />,
      style: {
        background: 'hsl(var(--background))',
        color: 'hsl(var(--foreground))',
        border: '1px solid hsl(var(--border))',
        borderLeftColor: 'hsl(142 76% 36%)', // green-600
        borderLeftWidth: '4px',
        borderRadius: '8px',
        padding: '16px',
        fontSize: '14px',
        fontWeight: '500',
        boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        maxWidth: '420px',
        minWidth: '300px',
      },
    });
  };

  const showError = (message: string, options?: ToastOptions) => {
    return toast.error(message, {
      duration: options?.duration || 6000,
      position: options?.position || 'top-center',
      icon: <XCircle className="h-5 w-5 text-red-600" />,
      style: {
        background: 'hsl(var(--background))',
        color: 'hsl(var(--foreground))',
        border: '1px solid hsl(var(--border))',
        borderLeftColor: 'hsl(0 84% 60%)', // red-600
        borderLeftWidth: '4px',
        borderRadius: '8px',
        padding: '16px',
        fontSize: '14px',
        fontWeight: '500',
        boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        maxWidth: '420px',
        minWidth: '300px',
      },
    });
  };

  const showWarning = (message: string, options?: ToastOptions) => {
    return toast(message, {
      duration: options?.duration || 5000,
      position: options?.position || 'top-center',
      icon: <AlertCircle className="h-5 w-5 text-yellow-600" />,
      style: {
        background: 'hsl(var(--background))',
        color: 'hsl(var(--foreground))',
        border: '1px solid hsl(var(--border))',
        borderLeftColor: 'hsl(45 93% 47%)', // yellow-600
        borderLeftWidth: '4px',
        borderRadius: '8px',
        padding: '16px',
        fontSize: '14px',
        fontWeight: '500',
        boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        maxWidth: '420px',
        minWidth: '300px',
      },
    });
  };

  const showInfo = (message: string, options?: ToastOptions) => {
    return toast(message, {
      duration: options?.duration || 5000,
      position: options?.position || 'top-center',
      icon: <Info className="h-5 w-5 text-blue-600" />,
      style: {
        background: 'hsl(var(--background))',
        color: 'hsl(var(--foreground))',
        border: '1px solid hsl(var(--border))',
        borderLeftColor: 'hsl(221 83% 53%)', // blue-600
        borderLeftWidth: '4px',
        borderRadius: '8px',
        padding: '16px',
        fontSize: '14px',
        fontWeight: '500',
        boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        maxWidth: '420px',
        minWidth: '300px',
      },
    });
  };

  const showLoading = (message: string, options?: ToastOptions) => {
    return toast.loading(message, {
      duration: options?.duration || Infinity,
      position: options?.position || 'top-center',
      style: {
        background: 'hsl(var(--background))',
        color: 'hsl(var(--foreground))',
        border: '1px solid hsl(var(--border))',
        borderLeftColor: 'hsl(var(--primary))',
        borderLeftWidth: '4px',
        borderRadius: '8px',
        padding: '16px',
        fontSize: '14px',
        fontWeight: '500',
        boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        maxWidth: '420px',
        minWidth: '300px',
      },
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
    return toast.promise(promise, messages, {
      position: options?.position || 'top-center',
      style: {
        background: 'hsl(var(--background))',
        color: 'hsl(var(--foreground))',
        border: '1px solid hsl(var(--border))',
        borderRadius: '8px',
        padding: '16px',
        fontSize: '14px',
        fontWeight: '500',
        boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
        maxWidth: '420px',
        minWidth: '300px',
      },
      success: {
        duration: 5000,
        icon: <CheckCircle className="h-5 w-5 text-green-600" />,
        style: {
          borderLeftColor: 'hsl(142 76% 36%)',
          borderLeftWidth: '4px',
        },
      },
      error: {
        duration: 6000,
        icon: <XCircle className="h-5 w-5 text-red-600" />,
        style: {
          borderLeftColor: 'hsl(0 84% 60%)',
          borderLeftWidth: '4px',
        },
      },
      loading: {
        duration: Infinity,
        style: {
          borderLeftColor: 'hsl(var(--primary))',
          borderLeftWidth: '4px',
        },
      },
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
