import React from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle, CreditCard, ArrowRight } from 'lucide-react';
import { type BreadcrumbItem } from '@/types';

interface CreditStatus {
    balance: number;
    total_purchased: number;
    total_used: number;
}

interface Props {
    paymentIntentId: string;
    amount: number;
    creditStatus: CreditStatus;
}

export default function CreditsSuccess({ paymentIntentId, amount, creditStatus }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Payment Successful',
            href: '/credits/success',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Payment Successful" />

            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <Card className="text-center">
                        <CardHeader>
                            <div className="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mb-4">
                                <CheckCircle className="h-8 w-8 text-green-600 dark:text-green-400" />
                            </div>
                            <CardTitle className="text-2xl text-green-800 dark:text-green-200">
                                Payment Successful!
                            </CardTitle>
                            <CardDescription className="text-lg">
                                Your credits have been added to your account
                            </CardDescription>
                        </CardHeader>
                        
                        <CardContent className="space-y-6">
                            <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                            ${amount.toFixed(2)}
                                        </div>
                                        <div className="text-sm text-muted-foreground">Amount Paid</div>
                                    </div>
                                    <div>
                                        <div className="text-2xl font-bold text-green-600 dark:text-green-400">
                                            {creditStatus.balance.toFixed(1)}
                                        </div>
                                        <div className="text-sm text-muted-foreground">Current Balance</div>
                                    </div>
                                    <div>
                                        <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                            {creditStatus.total_purchased.toFixed(1)}
                                        </div>
                                        <div className="text-sm text-muted-foreground">Total Purchased</div>
                                    </div>
                                </div>
                            </div>

                            <div className="text-sm text-muted-foreground">
                                Payment ID: {paymentIntentId}
                            </div>

                            <div className="flex flex-col sm:flex-row gap-4 justify-center">
                                <Button
                                    onClick={() => router.visit('/thinktest')}
                                    className="flex items-center"
                                >
                                    Start Generating Tests
                                    <ArrowRight className="h-4 w-4 ml-2" />
                                </Button>
                                
                                <Button
                                    variant="outline"
                                    onClick={() => router.visit('/credits/transactions')}
                                    className="flex items-center"
                                >
                                    <CreditCard className="h-4 w-4 mr-2" />
                                    View Transactions
                                </Button>
                            </div>

                            <div className="text-sm text-muted-foreground">
                                A receipt has been sent to your email address.
                            </div>
                        </CardContent>
                    </Card>

                    {/* Next Steps */}
                    <Card className="mt-8">
                        <CardHeader>
                            <CardTitle>What's Next?</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div className="flex items-start space-x-3">
                                    <div className="w-6 h-6 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span className="text-xs font-semibold text-blue-600 dark:text-blue-400">1</span>
                                    </div>
                                    <div>
                                        <h4 className="font-medium">Generate Tests</h4>
                                        <p className="text-sm text-muted-foreground">
                                            Go to the ThinkTest AI page and start generating comprehensive tests for your WordPress plugins.
                                        </p>
                                    </div>
                                </div>
                                
                                <div className="flex items-start space-x-3">
                                    <div className="w-6 h-6 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span className="text-xs font-semibold text-blue-600 dark:text-blue-400">2</span>
                                    </div>
                                    <div>
                                        <h4 className="font-medium">Choose Your AI Provider</h4>
                                        <p className="text-sm text-muted-foreground">
                                            Select from GPT-5, Claude 4, or other AI providers based on your needs and credit costs.
                                        </p>
                                    </div>
                                </div>
                                
                                <div className="flex items-start space-x-3">
                                    <div className="w-6 h-6 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span className="text-xs font-semibold text-blue-600 dark:text-blue-400">3</span>
                                    </div>
                                    <div>
                                        <h4 className="font-medium">Monitor Usage</h4>
                                        <p className="text-sm text-muted-foreground">
                                            Track your credit usage and purchase more when needed. Credits never expire!
                                        </p>
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
