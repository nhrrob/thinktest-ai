import { useToast } from '@/hooks/use-toast';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function ToastTest() {
    const { error, success, warning, info } = useToast();

    const testDuplicateErrors = () => {
        // Simulate the same error message multiple times
        error('Cannot delete role that is assigned to users.');
        setTimeout(() => error('Cannot delete role that is assigned to users.'), 100);
        setTimeout(() => error('Cannot delete role that is assigned to users.'), 200);
    };

    const testDifferentMessages = () => {
        error('First error message');
        setTimeout(() => error('Second error message'), 100);
        setTimeout(() => error('Third error message'), 200);
    };

    const testAllTypes = () => {
        success('Success message');
        setTimeout(() => error('Error message'), 100);
        setTimeout(() => warning('Warning message'), 200);
        setTimeout(() => info('Info message'), 300);
    };

    return (
        <AppLayout>
            <Head title="Toast Test" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <h1 className="mb-6 text-2xl font-bold">Toast Notification Test</h1>

                            <div className="space-y-4">
                                <div>
                                    <h2 className="mb-2 text-lg font-semibold">Test Duplicate Error Messages</h2>
                                    <p className="mb-3 text-sm text-gray-600 dark:text-gray-400">
                                        This should show 3 identical error toasts (testing the deduplication fix)
                                    </p>
                                    <button
                                        onClick={testDuplicateErrors}
                                        className="rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700"
                                    >
                                        Test Duplicate Errors
                                    </button>
                                </div>

                                <div>
                                    <h2 className="mb-2 text-lg font-semibold">Test Different Error Messages</h2>
                                    <p className="mb-3 text-sm text-gray-600 dark:text-gray-400">This should show 3 different error toasts</p>
                                    <button
                                        onClick={testDifferentMessages}
                                        className="rounded bg-orange-500 px-4 py-2 font-bold text-white hover:bg-orange-700"
                                    >
                                        Test Different Messages
                                    </button>
                                </div>

                                <div>
                                    <h2 className="mb-2 text-lg font-semibold">Test All Toast Types</h2>
                                    <p className="mb-3 text-sm text-gray-600 dark:text-gray-400">
                                        This should show success, error, warning, and info toasts
                                    </p>
                                    <button onClick={testAllTypes} className="rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700">
                                        Test All Types
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
