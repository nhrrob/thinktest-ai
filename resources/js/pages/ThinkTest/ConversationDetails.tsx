import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Calendar, Clock, Code, FileText, Github, MessageSquare, User } from 'lucide-react';

interface Conversation {
    id: number;
    conversation_id: string;
    provider: string;
    status: string;
    context: any;
    messages: any[];
    metadata: any;
    plugin_file_path?: string;
    plugin_file_hash?: string;
    plugin_data?: any;
    generated_tests?: string;
    step: number;
    total_steps: number;
    started_at: string;
    completed_at?: string;
    github_repository_id?: number;
    source_type: string;
    created_at: string;
    updated_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    github_repository?: {
        id: number;
        name: string;
        full_name: string;
        description?: string;
        html_url: string;
    };
}

interface ConversationDetailsProps {
    conversation: Conversation;
}

export default function ConversationDetails({ conversation }: ConversationDetailsProps) {
    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'active':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            case 'failed':
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            case 'cancelled':
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
        }
    };

    const getProviderIcon = (provider: string) => {
        switch (provider) {
            case 'openai':
            case 'openai-gpt5':
            case 'chatgpt-5':
                return '🤖';
            case 'anthropic':
            case 'anthropic-claude':
                return '🧠';
            default:
                return '🤖';
        }
    };

    const progressPercentage = conversation.total_steps > 0 
        ? Math.round((conversation.step / conversation.total_steps) * 100) 
        : 0;

    return (
        <AppLayout>
            <Head title={`Conversation Details - ${conversation.context?.filename || 'Unknown'}`} />

            <div className="container mx-auto px-4 py-8 max-w-4xl">
                {/* Header */}
                <div className="mb-6">
                    <div className="flex items-center gap-4 mb-4">
                        <Link href="/thinktest">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Back to ThinkTest AI
                            </Button>
                        </Link>
                    </div>
                    
                    <div className="flex items-start justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-foreground mb-2">
                                {conversation.title || 'Conversation Details'}
                            </h1>
                            <p className="text-muted-foreground">
                                {conversation.context?.filename || 'Unknown file'}
                            </p>
                        </div>
                        <Badge className={getStatusColor(conversation.status)}>
                            {conversation.status.charAt(0).toUpperCase() + conversation.status.slice(1)}
                        </Badge>
                    </div>
                </div>

                <div className="grid gap-6">
                    {/* Overview Card */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MessageSquare className="h-5 w-5" />
                                Conversation Overview
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2 text-sm">
                                        <User className="h-4 w-4 text-muted-foreground" />
                                        <span className="font-medium">Provider:</span>
                                        <span>{getProviderIcon(conversation.provider)} {conversation.provider}</span>
                                    </div>
                                    <div className="flex items-center gap-2 text-sm">
                                        <Calendar className="h-4 w-4 text-muted-foreground" />
                                        <span className="font-medium">Started:</span>
                                        <span>{new Date(conversation.started_at).toLocaleString()}</span>
                                    </div>
                                    {conversation.completed_at && (
                                        <div className="flex items-center gap-2 text-sm">
                                            <Clock className="h-4 w-4 text-muted-foreground" />
                                            <span className="font-medium">Completed:</span>
                                            <span>{new Date(conversation.completed_at).toLocaleString()}</span>
                                        </div>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2 text-sm">
                                        <Code className="h-4 w-4 text-muted-foreground" />
                                        <span className="font-medium">Source:</span>
                                        <span className="capitalize">{conversation.source_type}</span>
                                    </div>
                                    <div className="flex items-center gap-2 text-sm">
                                        <FileText className="h-4 w-4 text-muted-foreground" />
                                        <span className="font-medium">Framework:</span>
                                        <span>{conversation.context?.framework || 'PHPUnit'}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Progress */}
                            <div className="space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="font-medium">Progress</span>
                                    <span>{conversation.step} of {conversation.total_steps} steps</span>
                                </div>
                                <Progress value={progressPercentage} className="h-2" />
                            </div>
                        </CardContent>
                    </Card>

                    {/* GitHub Repository Info */}
                    {conversation.github_repository && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Github className="h-5 w-5" />
                                    GitHub Repository
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium">Repository:</span>
                                        <a 
                                            href={conversation.github_repository.html_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
                                        >
                                            {conversation.github_repository.full_name}
                                        </a>
                                    </div>
                                    {conversation.github_repository.description && (
                                        <div className="flex items-start gap-2">
                                            <span className="font-medium">Description:</span>
                                            <span className="text-muted-foreground">
                                                {conversation.github_repository.description}
                                            </span>
                                        </div>
                                    )}
                                    {conversation.context?.branch && (
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium">Branch:</span>
                                            <Badge variant="outline">{conversation.context.branch}</Badge>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Context Information */}
                    {conversation.context && Object.keys(conversation.context).length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Context Information</CardTitle>
                                <CardDescription>
                                    Additional context and metadata for this conversation
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <pre className="bg-muted p-4 rounded-lg text-sm overflow-auto">
                                    {JSON.stringify(conversation.context, null, 2)}
                                </pre>
                            </CardContent>
                        </Card>
                    )}

                    {/* Messages */}
                    {conversation.messages && conversation.messages.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Conversation Messages</CardTitle>
                                <CardDescription>
                                    Message history for this conversation
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {conversation.messages.map((message, index) => (
                                        <div key={index} className="border-l-2 border-muted pl-4">
                                            <div className="flex items-center gap-2 mb-2">
                                                <Badge variant="outline">
                                                    {message.role || 'system'}
                                                </Badge>
                                                {message.timestamp && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {new Date(message.timestamp).toLocaleString()}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="text-sm">
                                                {typeof message.content === 'string' 
                                                    ? message.content 
                                                    : JSON.stringify(message.content, null, 2)
                                                }
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Generated Tests */}
                    {conversation.generated_tests && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Generated Tests</CardTitle>
                                <CardDescription>
                                    Test code generated for this conversation
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <pre className="bg-muted p-4 rounded-lg text-sm overflow-auto max-h-96">
                                    {conversation.generated_tests}
                                </pre>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
