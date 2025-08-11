import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/hooks/use-toast';
import AppLayout from '@/layouts/app-layout';

export default function TestToast() {
    const toast = useToast();

    const testSuccess = () => {
        toast.success('This is a success message!');
    };

    const testError = () => {
        toast.error('This is an error message!');
    };

    const testWarning = () => {
        toast.warning('This is a warning message!');
    };

    const testInfo = () => {
        toast.info('This is an info message!');
    };

    const testLoading = () => {
        const loadingToast = toast.loading('Loading...');
        
        // Simulate async operation
        setTimeout(() => {
            toast.dismiss(loadingToast);
            toast.success('Operation completed!');
        }, 3000);
    };

    const testPromise = () => {
        const mockPromise = new Promise((resolve, reject) => {
            setTimeout(() => {
                if (Math.random() > 0.5) {
                    resolve('Success!');
                } else {
                    reject(new Error('Something went wrong!'));
                }
            }, 2000);
        });

        toast.promise(mockPromise, {
            loading: 'Processing...',
            success: 'Promise resolved successfully!',
            error: 'Promise failed!',
        });
    };

    return (
        <AppLayout>
            <Head title="Toast Notifications Test" />
            
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Toast Notifications Test</h1>
                    <p className="text-muted-foreground">
                        Test the toast notification system with different message types
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Toast Notification Types</CardTitle>
                        <CardDescription>
                            Click the buttons below to test different types of toast notifications
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <Button onClick={testSuccess} variant="default">
                                Success Toast
                            </Button>
                            
                            <Button onClick={testError} variant="destructive">
                                Error Toast
                            </Button>
                            
                            <Button onClick={testWarning} variant="outline">
                                Warning Toast
                            </Button>
                            
                            <Button onClick={testInfo} variant="secondary">
                                Info Toast
                            </Button>
                            
                            <Button onClick={testLoading} variant="outline">
                                Loading Toast
                            </Button>
                            
                            <Button onClick={testPromise} variant="outline">
                                Promise Toast
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Flash Message Simulation</CardTitle>
                        <CardDescription>
                            These buttons simulate Laravel flash messages that would be displayed automatically
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <Button 
                                onClick={() => window.location.href = '/test-toast?success=User created successfully!'}
                                variant="default"
                            >
                                Simulate Success Flash
                            </Button>
                            
                            <Button 
                                onClick={() => window.location.href = '/test-toast?error=Failed to create user!'}
                                variant="destructive"
                            >
                                Simulate Error Flash
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
