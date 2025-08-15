import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Clock, FileText, MessageSquare, X } from 'lucide-react';
import { useState } from 'react';
import { router } from '@inertiajs/react';

interface Conversation {
    id: number;
    title: string;
    created_at: string;
    updated_at: string;
}

interface Analysis {
    id: number;
    file_name: string;
    analysis_type: string;
    created_at: string;
    updated_at: string;
}

interface RecentItemsSidebarProps {
    recentConversations: Conversation[];
    recentAnalyses: Analysis[];
    className?: string;
}

export default function RecentItemsSidebar({ 
    recentConversations, 
    recentAnalyses, 
    className = '' 
}: RecentItemsSidebarProps) {
    const [isOpen, setIsOpen] = useState(false);

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        const now = new Date();
        const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));
        
        if (diffInHours < 1) {
            return 'Just now';
        } else if (diffInHours < 24) {
            return `${diffInHours}h ago`;
        } else if (diffInHours < 48) {
            return 'Yesterday';
        } else {
            const diffInDays = Math.floor(diffInHours / 24);
            return `${diffInDays}d ago`;
        }
    };

    const hasRecentItems = recentConversations.length > 0 || recentAnalyses.length > 0;

    return (
        <div className={className}>
            <Sheet open={isOpen} onOpenChange={setIsOpen}>
                <SheetTrigger asChild>
                    <Button 
                        variant="outline" 
                        size="sm" 
                        className="gap-2"
                        disabled={!hasRecentItems}
                    >
                        <Clock className="h-4 w-4" />
                        History
                        {hasRecentItems && (
                            <span className="ml-1 rounded-full bg-primary px-2 py-0.5 text-xs text-primary-foreground">
                                {recentConversations.length + recentAnalyses.length}
                            </span>
                        )}
                    </Button>
                </SheetTrigger>
                
                <SheetContent side="right" className="w-80 sm:w-96">
                    <SheetHeader>
                        <SheetTitle className="flex items-center gap-2">
                            <Clock className="h-5 w-5" />
                            Recent Activity
                        </SheetTitle>
                    </SheetHeader>
                    
                    <div className="mt-6 space-y-6">
                        {/* Recent Conversations */}
                        {recentConversations.length > 0 && (
                            <div>
                                <h3 className="mb-3 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                    <MessageSquare className="h-4 w-4" />
                                    Recent Conversations
                                </h3>
                                <div className="space-y-2">
                                    {recentConversations.slice(0, 5).map((conversation) => (
                                        <div
                                            key={conversation.id}
                                            className="group rounded-lg border border-border bg-card p-3 transition-colors hover:bg-muted/50 cursor-pointer"
                                            onClick={() => {
                                                // Navigate to conversation
                                                router.visit(`/thinktest/conversation/${conversation.id}`);
                                                setIsOpen(false); // Close sidebar after navigation
                                            }}
                                        >
                                            <div className="flex items-start justify-between">
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-medium text-foreground">
                                                        {conversation.title}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatDate(conversation.updated_at)}
                                                    </p>
                                                </div>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-auto p-1 opacity-0 group-hover:opacity-100"
                                                    onClick={(e) => {
                                                        e.stopPropagation(); // Prevent parent click
                                                        router.visit(`/thinktest/conversation/${conversation.id}`);
                                                        setIsOpen(false);
                                                    }}
                                                >
                                                    <span className="sr-only">View conversation</span>
                                                    →
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Recent Analyses */}
                        {recentAnalyses.length > 0 && (
                            <div>
                                <h3 className="mb-3 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                    <FileText className="h-4 w-4" />
                                    Recent Analyses
                                </h3>
                                <div className="space-y-2">
                                    {recentAnalyses.slice(0, 5).map((analysis) => (
                                        <div
                                            key={analysis.id}
                                            className="group rounded-lg border border-border bg-card p-3 transition-colors hover:bg-muted/50 cursor-pointer"
                                            onClick={() => {
                                                // Navigate to analysis
                                                router.visit(`/thinktest/analysis/${analysis.id}`);
                                                setIsOpen(false); // Close sidebar after navigation
                                            }}
                                        >
                                            <div className="flex items-start justify-between">
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-medium text-foreground">
                                                        {analysis.file_name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {analysis.analysis_type} • {formatDate(analysis.updated_at)}
                                                    </p>
                                                </div>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-auto p-1 opacity-0 group-hover:opacity-100"
                                                    onClick={(e) => {
                                                        e.stopPropagation(); // Prevent parent click
                                                        router.visit(`/thinktest/analysis/${analysis.id}`);
                                                        setIsOpen(false);
                                                    }}
                                                >
                                                    <span className="sr-only">View analysis</span>
                                                    →
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Empty State */}
                        {!hasRecentItems && (
                            <div className="flex flex-col items-center justify-center py-8 text-center">
                                <Clock className="h-12 w-12 text-muted-foreground/50" />
                                <h3 className="mt-4 text-sm font-medium text-foreground">No recent activity</h3>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Your recent conversations and analyses will appear here.
                                </p>
                            </div>
                        )}
                    </div>
                </SheetContent>
            </Sheet>
        </div>
    );
}
