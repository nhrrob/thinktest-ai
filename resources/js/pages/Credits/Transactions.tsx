import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { 
    CreditCard, 
    Download, 
    Filter, 
    Search, 
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
    Calendar,
    DollarSign,
    TrendingUp,
    TrendingDown
} from 'lucide-react';
import { type BreadcrumbItem } from '@/types';

interface CreditTransaction {
    id: number;
    type: string;
    amount: number;
    balance_before: number;
    balance_after: number;
    description: string;
    created_at: string;
    payment_intent_id?: string;
    payment_method?: string;
    payment_status?: string;
    ai_provider?: string;
    ai_model?: string;
    tokens_used?: number;
    formatted_amount: string;
    type_display: string;
    status_color: string;
}

interface CreditStatus {
    balance: number;
    total_purchased: number;
    total_used: number;
    recent_transactions: CreditTransaction[];
    usage_stats: {
        total_uses: number;
        total_credits_used: number;
        provider_breakdown: Record<string, any>;
    };
}

interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Props {
    transactions: {
        data: CreditTransaction[];
        links: any[];
        meta: PaginationData;
    };
    creditStatus: CreditStatus;
}

export default function CreditTransactions({ transactions, creditStatus }: Props) {
    const [filterType, setFilterType] = useState<string>('all');
    const [searchTerm, setSearchTerm] = useState<string>('');
    const [sortBy, setSortBy] = useState<string>('created_at');
    const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Credits',
            href: '/credits',
        },
        {
            title: 'Transaction History',
            href: '/credits/transactions',
        },
    ];

    const getStatusBadgeVariant = (transaction: CreditTransaction) => {
        if (transaction.type === 'purchase') {
            return transaction.payment_status === 'completed' ? 'default' : 'secondary';
        }
        if (transaction.type === 'usage') {
            return 'outline';
        }
        return 'secondary';
    };

    const getAmountColor = (amount: number) => {
        return amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const handleDownloadReceipt = (transactionId: number) => {
        // This would trigger a download of the receipt
        window.open(`/credits/receipt?transaction_id=${transactionId}`, '_blank');
    };

    const handleFilterChange = (type: string) => {
        setFilterType(type);
        // In a real implementation, this would trigger a new request with filters
        const params = new URLSearchParams(window.location.search);
        if (type !== 'all') {
            params.set('type', type);
        } else {
            params.delete('type');
        }
        router.visit(`/credits/transactions?${params.toString()}`);
    };

    const handleSort = (field: string) => {
        const newOrder = sortBy === field && sortOrder === 'desc' ? 'asc' : 'desc';
        setSortBy(field);
        setSortOrder(newOrder);
        
        const params = new URLSearchParams(window.location.search);
        params.set('sort', field);
        params.set('order', newOrder);
        router.visit(`/credits/transactions?${params.toString()}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transaction History" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Summary Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Current Balance</CardTitle>
                                <CreditCard className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {creditStatus.balance.toFixed(1)}
                                </div>
                                <p className="text-xs text-muted-foreground">Available credits</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Purchased</CardTitle>
                                <TrendingUp className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {creditStatus.total_purchased.toFixed(1)}
                                </div>
                                <p className="text-xs text-muted-foreground">Credits purchased</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Used</CardTitle>
                                <TrendingDown className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                    {creditStatus.total_used.toFixed(1)}
                                </div>
                                <p className="text-xs text-muted-foreground">Credits consumed</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Transactions</CardTitle>
                                <Calendar className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {transactions.meta.total}
                                </div>
                                <p className="text-xs text-muted-foreground">All time</p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Filters and Search */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Filter className="h-5 w-5" />
                                Filters & Search
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-col sm:flex-row gap-4">
                                <div className="flex-1">
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            placeholder="Search transactions..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            className="pl-10"
                                        />
                                    </div>
                                </div>
                                
                                <Select value={filterType} onValueChange={handleFilterChange}>
                                    <SelectTrigger className="w-full sm:w-48">
                                        <SelectValue placeholder="Filter by type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Transactions</SelectItem>
                                        <SelectItem value="purchase">Purchases</SelectItem>
                                        <SelectItem value="usage">Usage</SelectItem>
                                        <SelectItem value="refund">Refunds</SelectItem>
                                        <SelectItem value="bonus">Bonus Credits</SelectItem>
                                    </SelectContent>
                                </Select>

                                <Button
                                    variant="outline"
                                    onClick={() => router.visit('/credits')}
                                    className="flex items-center gap-2"
                                >
                                    <CreditCard className="h-4 w-4" />
                                    Buy More Credits
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Transactions Table */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Transaction History</CardTitle>
                            <CardDescription>
                                Showing {transactions.meta.from} to {transactions.meta.to} of {transactions.meta.total} transactions
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left py-3 px-4">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleSort('created_at')}
                                                    className="h-auto p-0 font-semibold"
                                                >
                                                    Date
                                                    <ArrowUpDown className="ml-2 h-4 w-4" />
                                                </Button>
                                            </th>
                                            <th className="text-left py-3 px-4">Type</th>
                                            <th className="text-left py-3 px-4">Description</th>
                                            <th className="text-right py-3 px-4">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleSort('amount')}
                                                    className="h-auto p-0 font-semibold"
                                                >
                                                    Amount
                                                    <ArrowUpDown className="ml-2 h-4 w-4" />
                                                </Button>
                                            </th>
                                            <th className="text-right py-3 px-4">Balance After</th>
                                            <th className="text-center py-3 px-4">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {transactions.data.map((transaction) => (
                                            <tr key={transaction.id} className="border-b hover:bg-muted/50">
                                                <td className="py-3 px-4">
                                                    <div className="text-sm">
                                                        {formatDate(transaction.created_at)}
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <Badge variant={getStatusBadgeVariant(transaction)}>
                                                        {transaction.type_display}
                                                    </Badge>
                                                </td>
                                                <td className="py-3 px-4">
                                                    <div className="text-sm">
                                                        {transaction.description}
                                                    </div>
                                                    {transaction.ai_provider && (
                                                        <div className="text-xs text-muted-foreground">
                                                            {transaction.ai_provider} • {transaction.ai_model}
                                                            {transaction.tokens_used && ` • ${transaction.tokens_used} tokens`}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="py-3 px-4 text-right">
                                                    <span className={`font-semibold ${getAmountColor(transaction.amount)}`}>
                                                        {transaction.formatted_amount}
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4 text-right">
                                                    <span className="text-sm">
                                                        {transaction.balance_after.toFixed(1)}
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4 text-center">
                                                    {transaction.type === 'purchase' && transaction.payment_intent_id && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleDownloadReceipt(transaction.id)}
                                                            className="h-8 w-8 p-0"
                                                        >
                                                            <Download className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {transactions.meta.last_page > 1 && (
                                <div className="flex items-center justify-between mt-6">
                                    <div className="text-sm text-muted-foreground">
                                        Page {transactions.meta.current_page} of {transactions.meta.last_page}
                                    </div>
                                    
                                    <div className="flex items-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={transactions.meta.current_page === 1}
                                            onClick={() => router.visit(`/credits/transactions?page=${transactions.meta.current_page - 1}`)}
                                        >
                                            <ChevronLeft className="h-4 w-4" />
                                            Previous
                                        </Button>
                                        
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={transactions.meta.current_page === transactions.meta.last_page}
                                            onClick={() => router.visit(`/credits/transactions?page=${transactions.meta.current_page + 1}`)}
                                        >
                                            Next
                                            <ChevronRight className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
