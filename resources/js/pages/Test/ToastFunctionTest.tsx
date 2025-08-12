import { useToast } from '@/hooks/use-toast';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export default function ToastFunctionTest() {
    // Test the correct destructuring
    const { error: showError, success: showSuccess, warning: showWarning, info: showInfo } = useToast();

    const testShowError = () => {
        showError('This is a test error message');
    };

    const testShowSuccess = () => {
        showSuccess('This is a test success message');
    };

    const testShowWarning = () => {
        showWarning('This is a test warning message');
    };

    const testShowInfo = () => {
        showInfo('This is a test info message');
    };

    return (
        <AppLayout>
            <Head title="Toast Function Test" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <h1 className="mb-6 text-2xl font-bold">Toast Function Test</h1>
                            <p className="mb-6 text-gray-600 dark:text-gray-400">
                                This page tests that the useToast hook functions are correctly destructured and working.
                            </p>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Toast Function Tests</CardTitle>
                                    <CardDescription>
                                        Click each button to test the corresponding toast function
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid grid-cols-2 gap-4">
                                        <Button 
                                            onClick={testShowError}
                                            variant="destructive"
                                        >
                                            Test Error Toast
                                        </Button>

                                        <Button 
                                            onClick={testShowSuccess}
                                            variant="default"
                                        >
                                            Test Success Toast
                                        </Button>

                                        <Button 
                                            onClick={testShowWarning}
                                            variant="outline"
                                        >
                                            Test Warning Toast
                                        </Button>

                                        <Button 
                                            onClick={testShowInfo}
                                            variant="secondary"
                                        >
                                            Test Info Toast
                                        </Button>
                                    </div>

                                    <div className="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <h3 className="font-semibold mb-2">Expected Behavior:</h3>
                                        <ul className="text-sm space-y-1">
                                            <li>• Each button should show a toast notification</li>
                                            <li>• No console errors should appear</li>
                                            <li>• Functions should be properly destructured from useToast</li>
                                        </ul>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
