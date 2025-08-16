import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useToast } from '@/hooks/use-toast';
import { CheckCircle, CreditCard, Star, Zap, X } from 'lucide-react';
import { type BreadcrumbItem } from '@/types';
import { loadStripe } from '@stripe/stripe-js';
import { Elements, CardElement, useStripe, useElements } from '@stripe/react-stripe-js';

interface CreditPackage {
    id: number;
    name: string;
    slug: string;
    description: string;
    credits: number;
    price: number;
    bonus_credits: number;
    is_popular: boolean;
    features: string[];
    formatted_price: string;
    formatted_credits: string;
    total_credits: number;
    value_proposition: string;
}

interface CreditStatus {
    balance: number;
    total_purchased: number;
    total_used: number;
    recent_transactions: any[];
    usage_stats: {
        total_uses: number;
        total_credits_used: number;
        provider_breakdown: Record<string, any>;
    };
}

interface Props {
    packages: CreditPackage[];
    creditStatus: CreditStatus;
    recommendedPackage: CreditPackage | null;
    stripePublishableKey: string;
}

// Initialize Stripe (will be set dynamically in component)
let stripePromise: Promise<any> | null = null;

// Payment Form Component
function PaymentForm({
    clientSecret,
    packageInfo,
    onSuccess,
    onError,
    onCancel
}: {
    clientSecret: string;
    packageInfo: CreditPackage;
    onSuccess: () => void;
    onError: (error: string) => void;
    onCancel: () => void;
}) {
    const stripe = useStripe();
    const elements = useElements();
    const [isProcessing, setIsProcessing] = useState(false);
    const { toast } = useToast();

    const handleSubmit = async (event: React.FormEvent) => {
        event.preventDefault();

        if (!stripe || !elements) {
            return;
        }

        setIsProcessing(true);

        const cardElement = elements.getElement(CardElement);
        if (!cardElement) {
            onError('Card element not found');
            setIsProcessing(false);
            return;
        }

        try {
            const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
                payment_method: {
                    card: cardElement,
                }
            });

            if (error) {
                onError(error.message || 'Payment failed');
            } else if (paymentIntent?.status === 'succeeded') {
                toast({
                    title: 'Payment Successful!',
                    description: `Successfully purchased ${packageInfo.name}`,
                    variant: 'success',
                });
                onSuccess();
            }
        } catch (err) {
            onError('An unexpected error occurred');
        } finally {
            setIsProcessing(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="space-y-4">
                <div>
                    <h3 className="text-lg font-semibold">Payment Details</h3>
                    <p className="text-sm text-muted-foreground">
                        Complete your purchase of {packageInfo.name} for {packageInfo.formatted_price}
                    </p>
                </div>

                <div className="p-4 border rounded-lg">
                    <CardElement
                        options={{
                            style: {
                                base: {
                                    fontSize: '16px',
                                    color: '#424770',
                                    '::placeholder': {
                                        color: '#aab7c4',
                                    },
                                },
                                invalid: {
                                    color: '#9e2146',
                                },
                            },
                        }}
                    />
                </div>
            </div>

            <div className="flex gap-3">
                <Button
                    type="submit"
                    disabled={!stripe || isProcessing}
                    className="flex-1"
                >
                    {isProcessing ? (
                        <>
                            <Zap className="h-4 w-4 mr-2 animate-spin" />
                            Processing...
                        </>
                    ) : (
                        <>
                            <CreditCard className="h-4 w-4 mr-2" />
                            Pay {packageInfo.formatted_price}
                        </>
                    )}
                </Button>

                <Button
                    type="button"
                    variant="outline"
                    onClick={onCancel}
                    disabled={isProcessing}
                >
                    Cancel
                </Button>
            </div>
        </form>
    );
}

export default function CreditsIndex({ packages, creditStatus, recommendedPackage, stripePublishableKey }: Props) {
    const [isLoading, setIsLoading] = useState(false);
    const [selectedPackage, setSelectedPackage] = useState<CreditPackage | null>(null);
    const [showPaymentDialog, setShowPaymentDialog] = useState(false);
    const [clientSecret, setClientSecret] = useState<string | null>(null);
    const { toast } = useToast();

    // Initialize Stripe with the key from backend
    if (!stripePromise && stripePublishableKey) {
        stripePromise = loadStripe(stripePublishableKey);
    }

    const handlePurchase = async (packageItem: CreditPackage) => {
        setIsLoading(true);
        setSelectedPackage(packageItem);

        try {
            const response = await fetch('/credits/payment-intent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    package_id: packageItem.id,
                }),
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'Failed to create payment intent');
            }

            // Set the client secret and show payment dialog
            setClientSecret(result.client_secret);
            setShowPaymentDialog(true);

        } catch (error) {
            console.error('Purchase failed:', error);
            toast({
                title: 'Purchase Failed',
                description: error instanceof Error ? error.message : 'An unexpected error occurred',
                variant: 'destructive',
            });
            setSelectedPackage(null);
        } finally {
            setIsLoading(false);
        }
    };

    const handlePaymentSuccess = () => {
        setShowPaymentDialog(false);
        setClientSecret(null);
        setSelectedPackage(null);

        // Redirect to success page
        router.visit('/credits/success');
    };

    const handlePaymentError = (error: string) => {
        toast({
            title: 'Payment Failed',
            description: error,
            variant: 'destructive',
        });
    };

    const handlePaymentCancel = () => {
        setShowPaymentDialog(false);
        setClientSecret(null);
        setSelectedPackage(null);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Purchase Credits',
            href: '/credits',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Purchase Credits" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Current Balance */}
                    <Card className="mb-8">
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2">
                                    <CreditCard className="h-5 w-5" />
                                    Your Credit Balance
                                </CardTitle>
                                <Button
                                    variant="outline"
                                    onClick={() => router.visit('/credits/transactions')}
                                    className="flex items-center gap-2"
                                >
                                    View Transactions
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div className="text-center">
                                    <div className="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                        {creditStatus.balance.toFixed(1)}
                                    </div>
                                    <div className="text-sm text-muted-foreground">Available Credits</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-semibold text-green-600 dark:text-green-400">
                                        {creditStatus.total_purchased.toFixed(1)}
                                    </div>
                                    <div className="text-sm text-muted-foreground">Total Purchased</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-semibold text-gray-600 dark:text-gray-400">
                                        {creditStatus.total_used.toFixed(1)}
                                    </div>
                                    <div className="text-sm text-muted-foreground">Total Used</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Credit Packages */}
                    <div className="mb-8">
                        <h3 className="text-lg font-semibold mb-4">Choose a Credit Package</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            {packages.map((pkg) => (
                                <Card 
                                    key={pkg.id} 
                                    className={`relative ${pkg.is_popular ? 'ring-2 ring-blue-500' : ''}`}
                                >
                                    {pkg.is_popular && (
                                        <Badge className="absolute -top-2 left-1/2 transform -translate-x-1/2 bg-blue-500">
                                            <Star className="h-3 w-3 mr-1" />
                                            Most Popular
                                        </Badge>
                                    )}
                                    
                                    <CardHeader className="text-center">
                                        <CardTitle className="text-lg">{pkg.name}</CardTitle>
                                        <CardDescription>{pkg.description}</CardDescription>
                                        <div className="mt-4">
                                            <div className="text-3xl font-bold">{pkg.formatted_price}</div>
                                            <div className="text-sm text-muted-foreground">
                                                {pkg.formatted_credits} credits
                                            </div>
                                            {pkg.bonus_credits > 0 && (
                                                <div className="text-sm text-green-600 dark:text-green-400 font-medium">
                                                    +{pkg.bonus_credits} bonus credits
                                                </div>
                                            )}
                                        </div>
                                    </CardHeader>
                                    
                                    <CardContent>
                                        <ul className="space-y-2 mb-6">
                                            {pkg.features.map((feature, index) => (
                                                <li key={index} className="flex items-center text-sm">
                                                    <CheckCircle className="h-4 w-4 text-green-500 mr-2 flex-shrink-0" />
                                                    {feature}
                                                </li>
                                            ))}
                                        </ul>
                                        
                                        <Button
                                            className="w-full"
                                            onClick={() => handlePurchase(pkg)}
                                            disabled={isLoading && selectedPackage?.id === pkg.id}
                                            variant={pkg.is_popular ? 'default' : 'outline'}
                                        >
                                            {isLoading && selectedPackage?.id === pkg.id ? (
                                                <>
                                                    <Zap className="h-4 w-4 mr-2 animate-spin" />
                                                    Processing...
                                                </>
                                            ) : (
                                                <>
                                                    <CreditCard className="h-4 w-4 mr-2" />
                                                    Purchase Now
                                                </>
                                            )}
                                        </Button>
                                        
                                        {pkg.value_proposition && (
                                            <div className="text-xs text-center text-muted-foreground mt-2">
                                                {pkg.value_proposition}
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </div>

                    {/* Recommended Package */}
                    {recommendedPackage && (
                        <Card className="bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800">
                            <CardHeader>
                                <CardTitle className="text-blue-800 dark:text-blue-200">
                                    Recommended for You
                                </CardTitle>
                                <CardDescription>
                                    Based on your usage patterns, we recommend the {recommendedPackage.name}.
                                </CardDescription>
                            </CardHeader>
                        </Card>
                    )}

                    {/* Usage Information */}
                    <Card className="mt-8">
                        <CardHeader>
                            <CardTitle>How Credits Work</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 className="font-semibold mb-2">Credit Costs by AI Provider</h4>
                                    <ul className="space-y-1 text-sm">
                                        <li>• OpenAI GPT-5: 2.0 credits per use</li>
                                        <li>• OpenAI GPT-5 Mini: 1.0 credit per use</li>
                                        <li>• Claude 4 Opus: 3.0 credits per use</li>
                                        <li>• Claude 4 Sonnet: 2.0 credits per use</li>
                                        <li>• Claude 3.5 Sonnet: 1.5 credits per use</li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 className="font-semibold mb-2">Benefits</h4>
                                    <ul className="space-y-1 text-sm">
                                        <li>• Credits never expire</li>
                                        <li>• Use any AI provider without API keys</li>
                                        <li>• Priority processing</li>
                                        <li>• Email support included</li>
                                    </ul>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Payment Dialog */}
            <Dialog open={showPaymentDialog} onOpenChange={setShowPaymentDialog}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Complete Payment</DialogTitle>
                        <DialogDescription>
                            {selectedPackage && (
                                <>
                                    Purchase {selectedPackage.name} for {selectedPackage.formatted_price}
                                </>
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    {clientSecret && selectedPackage && (
                        <Elements stripe={stripePromise}>
                            <PaymentForm
                                clientSecret={clientSecret}
                                packageInfo={selectedPackage}
                                onSuccess={handlePaymentSuccess}
                                onError={handlePaymentError}
                                onCancel={handlePaymentCancel}
                            />
                        </Elements>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
