import React from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { XCircle, ArrowLeft, CreditCard } from 'lucide-react';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Payment Canceled',
        href: '/credits/canceled',
    },
];

export default function CreditsCanceled() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Payment Canceled" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <Card className="text-center">
                        <CardHeader>
                            <div className="mx-auto w-16 h-16 bg-red-100 dark:bg-red-900/20 rounded-full flex items-center justify-center mb-4">
                                <XCircle className="h-8 w-8 text-red-600 dark:text-red-400" />
                            </div>
                            <CardTitle className="text-2xl text-red-800 dark:text-red-200">
                                Payment Canceled
                            </CardTitle>
                            <CardDescription className="text-lg">
                                Your payment was canceled and no charges were made
                            </CardDescription>
                        </CardHeader>
                        
                        <CardContent className="space-y-6">
                            <div className="text-muted-foreground">
                                Don't worry! You can try again anytime. Your account remains unchanged.
                            </div>

                            <div className="flex flex-col sm:flex-row gap-4 justify-center">
                                <Button
                                    onClick={() => router.visit('/credits')}
                                    className="flex items-center"
                                >
                                    <CreditCard className="h-4 w-4 mr-2" />
                                    Try Again
                                </Button>
                                
                                <Button
                                    variant="outline"
                                    onClick={() => router.visit('/thinktest')}
                                    className="flex items-center"
                                >
                                    <ArrowLeft className="h-4 w-4 mr-2" />
                                    Back to ThinkTest AI
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Alternative Options */}
                    <Card className="mt-8">
                        <CardHeader>
                            <CardTitle>Alternative Options</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div className="flex items-start space-x-3">
                                    <div className="w-6 h-6 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span className="text-xs font-semibold text-blue-600 dark:text-blue-400">1</span>
                                    </div>
                                    <div>
                                        <h4 className="font-medium">Use Your Own API Keys</h4>
                                        <p className="text-sm text-muted-foreground">
                                            Add your OpenAI or Anthropic API keys in settings to use your own credits directly.
                                        </p>
                                        <Button
                                            variant="link"
                                            className="p-0 h-auto text-sm"
                                            onClick={() => router.visit('/settings')}
                                        >
                                            Go to Settings →
                                        </Button>
                                    </div>
                                </div>
                                
                                <div className="flex items-start space-x-3">
                                    <div className="w-6 h-6 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span className="text-xs font-semibold text-blue-600 dark:text-blue-400">2</span>
                                    </div>
                                    <div>
                                        <h4 className="font-medium">Try Demo Credits</h4>
                                        <p className="text-sm text-muted-foreground">
                                            New users get 5 free demo credits to try ThinkTest AI before purchasing.
                                        </p>
                                    </div>
                                </div>
                                
                                <div className="flex items-start space-x-3">
                                    <div className="w-6 h-6 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span className="text-xs font-semibold text-blue-600 dark:text-blue-400">3</span>
                                    </div>
                                    <div>
                                        <h4 className="font-medium">Contact Support</h4>
                                        <p className="text-sm text-muted-foreground">
                                            Having payment issues? Our support team is here to help.
                                        </p>
                                        <Button
                                            variant="link"
                                            className="p-0 h-auto text-sm"
                                            onClick={() => window.location.href = 'mailto:support@thinktest.ai'}
                                        >
                                            Email Support →
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
