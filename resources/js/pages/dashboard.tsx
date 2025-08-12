import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Activity, FileText, GitBranch, Key, TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'ThinkTest AI',
        href: '/dashboard',
    },
];

type DashboardStats = {
    overview: {
        total_tests_generated: number;
        total_repositories_processed: number;
        total_files_analyzed: number;
        active_api_tokens: number;
    };
    monthly: {
        tests_this_month: number;
        repositories_this_month: number;
        files_this_month: number;
        conversations_this_month: number;
    };
    recent_activity: Array<{
        type: string;
        title: string;
        description: string;
        timestamp: string;
        provider?: string;
        framework?: string;
    }>;
    provider_usage: Record<string, number>;
    trends: {
        tests_trend: {
            previous: number;
            current: number;
            percentage: number;
            direction: 'up' | 'down' | 'stable';
        };
        repositories_trend: {
            previous: number;
            current: number;
            percentage: number;
            direction: 'up' | 'down' | 'stable';
        };
    };
};

interface DashboardProps {
    stats: DashboardStats;
}

export default function Dashboard({ stats }: DashboardProps) {
    const getTrendIcon = (direction: string) => {
        switch (direction) {
            case 'up':
                return <TrendingUp className="h-4 w-4 text-green-600" />;
            case 'down':
                return <TrendingDown className="h-4 w-4 text-red-600" />;
            default:
                return <Minus className="h-4 w-4 text-gray-600" />;
        }
    };

    const getTrendColor = (direction: string) => {
        switch (direction) {
            case 'up':
                return 'text-green-600';
            case 'down':
                return 'text-red-600';
            default:
                return 'text-gray-600';
        }
    };

    const formatTimestamp = (timestamp: string) => {
        return new Date(timestamp).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getActivityIcon = (type: string) => {
        switch (type) {
            case 'test_generated':
                return <FileText className="h-4 w-4" />;
            case 'plugin_analyzed':
                return <Activity className="h-4 w-4" />;
            case 'repository_processed':
                return <GitBranch className="h-4 w-4" />;
            default:
                return <Activity className="h-4 w-4" />;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="ThinkTest AI" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                {/* Overview Stats */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Tests Generated</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.overview.total_tests_generated}</div>
                            <div className="flex items-center space-x-2 text-xs text-muted-foreground">
                                {getTrendIcon(stats.trends.tests_trend.direction)}
                                <span className={getTrendColor(stats.trends.tests_trend.direction)}>
                                    {stats.trends.tests_trend.percentage}% from last month
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Repositories</CardTitle>
                            <GitBranch className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.overview.total_repositories_processed}</div>
                            <div className="flex items-center space-x-2 text-xs text-muted-foreground">
                                {getTrendIcon(stats.trends.repositories_trend.direction)}
                                <span className={getTrendColor(stats.trends.repositories_trend.direction)}>
                                    {stats.trends.repositories_trend.percentage}% from last month
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Files Analyzed</CardTitle>
                            <Activity className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.overview.total_files_analyzed}</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.monthly.files_this_month} this month
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">API Tokens</CardTitle>
                            <Key className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.overview.active_api_tokens}</div>
                            <p className="text-xs text-muted-foreground">
                                Active tokens configured
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Recent Activity */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Activity</CardTitle>
                            <CardDescription>Your latest test generations and analyses</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {stats.recent_activity.length > 0 ? (
                                    stats.recent_activity.slice(0, 8).map((activity, index) => (
                                        <div key={index} className="flex items-start space-x-3">
                                            <div className="flex-shrink-0 mt-1">
                                                {getActivityIcon(activity.type)}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {activity.title}
                                                </p>
                                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                                    {activity.description}
                                                </p>
                                                <div className="flex items-center space-x-2 mt-1">
                                                    <p className="text-xs text-gray-400">
                                                        {formatTimestamp(activity.timestamp)}
                                                    </p>
                                                    {activity.provider && (
                                                        <Badge variant="secondary" className="text-xs">
                                                            {activity.provider}
                                                        </Badge>
                                                    )}
                                                    {activity.framework && (
                                                        <Badge variant="outline" className="text-xs">
                                                            {activity.framework}
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        No recent activity. Start by uploading a plugin or connecting a GitHub repository!
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Provider Usage & Monthly Stats */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>This Month</CardTitle>
                                <CardDescription>Your activity for the current month</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-sm">Tests Generated</span>
                                        <span className="text-sm font-medium">{stats.monthly.tests_this_month}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-sm">Repositories Processed</span>
                                        <span className="text-sm font-medium">{stats.monthly.repositories_this_month}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-sm">Files Analyzed</span>
                                        <span className="text-sm font-medium">{stats.monthly.files_this_month}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-sm">AI Conversations</span>
                                        <span className="text-sm font-medium">{stats.monthly.conversations_this_month}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>AI Provider Usage</CardTitle>
                                <CardDescription>Distribution of your AI provider usage</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {Object.keys(stats.provider_usage).length > 0 ? (
                                        Object.entries(stats.provider_usage).map(([provider, count]) => (
                                            <div key={provider} className="flex justify-between">
                                                <span className="text-sm capitalize">{provider.replace('-', ' ')}</span>
                                                <span className="text-sm font-medium">{count}</span>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            No AI provider usage yet
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
