import { Head } from '@inertiajs/react';
import { useToast } from '@/hooks/use-toast';
import AppLayout from '@/layouts/app-layout';

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
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <h1 className="text-2xl font-bold mb-6">Toast Notification Test</h1>
                            
                            <div className="space-y-4">
                                <div>
                                    <h2 className="text-lg font-semibold mb-2">Test Duplicate Error Messages</h2>
                                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        This should show 3 identical error toasts (testing the deduplication fix)
                                    </p>
                                    <button
                                        onClick={testDuplicateErrors}
                                        className="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Test Duplicate Errors
                                    </button>
                                </div>

                                <div>
                                    <h2 className="text-lg font-semibold mb-2">Test Different Error Messages</h2>
                                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        This should show 3 different error toasts
                                    </p>
                                    <button
                                        onClick={testDifferentMessages}
                                        className="bg-orange-500 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Test Different Messages
                                    </button>
                                </div>

                                <div>
                                    <h2 className="text-lg font-semibold mb-2">Test All Toast Types</h2>
                                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        This should show success, error, warning, and info toasts
                                    </p>
                                    <button
                                        onClick={testAllTypes}
                                        className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                                    >
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
