import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ChevronDown, ChevronRight, Bug, RefreshCw } from 'lucide-react';
import { fetchWithCsrfRetry, handleApiResponse } from '@/utils/csrf';

interface Repository {
    owner: string;
    repo: string;
    full_name: string;
    default_branch: string;
}

interface DebugInfo {
    timestamp: string;
    apiResponse?: any;
    treeData?: any[];
    validationErrors?: string[];
    networkError?: string;
}

interface GitHubDebugPanelProps {
    repository: Repository;
    branch: string;
    onDebugComplete?: (debugInfo: DebugInfo) => void;
}

export default function GitHubDebugPanel({ 
    repository, 
    branch, 
    onDebugComplete 
}: GitHubDebugPanelProps) {
    const [debugInfo, setDebugInfo] = useState<DebugInfo | null>(null);
    const [isDebugging, setIsDebugging] = useState(false);
    const [isExpanded, setIsExpanded] = useState(false);



    const runDebugTest = async () => {
        setIsDebugging(true);
        const timestamp = new Date().toISOString();
        
        console.log(`[${timestamp}] GitHubDebugPanel: Starting debug test`, {
            repository: `${repository.owner}/${repository.repo}`,
            branch
        });

        const debugData: DebugInfo = {
            timestamp,
            validationErrors: []
        };

        try {
            // Test repository tree API
            console.log(`[${timestamp}] GitHubDebugPanel: Testing /thinktest/github/tree API`);

            const response = await fetchWithCsrfRetry('/thinktest/github/tree', {
                method: 'POST',
                body: JSON.stringify({
                    owner: repository.owner,
                    repo: repository.repo,
                    branch: branch,
                    recursive: true,
                }),
            });

            console.log(`[${timestamp}] GitHubDebugPanel: API response status:`, response.status);
            console.log(`[${timestamp}] GitHubDebugPanel: API response headers:`, Object.fromEntries(response.headers.entries()));

            const result = await handleApiResponse(response);

            // If handleApiResponse returned undefined, it means there was an error that caused a page reload
            if (result === undefined) {
                return;
            }
            debugData.apiResponse = result;

            console.log(`[${timestamp}] GitHubDebugPanel: API response body:`, result);

            // Validate response structure
            if (!result.success) {
                debugData.validationErrors?.push(`API returned success: false. Message: ${result.message || 'No message provided'}`);
            }

            if (!Array.isArray(result.tree)) {
                debugData.validationErrors?.push(`API response tree is not an array. Type: ${typeof result.tree}, Value: ${JSON.stringify(result.tree)}`);
            } else {
                debugData.treeData = result.tree;
                console.log(`[${timestamp}] GitHubDebugPanel: Tree data analysis:`, {
                    totalItems: result.tree.length,
                    fileCount: result.tree.filter((item: any) => item.type === 'file').length,
                    dirCount: result.tree.filter((item: any) => item.type === 'dir').length,
                    sampleItems: result.tree.slice(0, 5),
                    uniquePaths: [...new Set(result.tree.map((item: any) => item.path))].length,
                    itemsWithMissingProps: result.tree.filter((item: any) => 
                        !item.path || !item.name || !item.type
                    ).length
                });

                if (result.tree.length === 0) {
                    debugData.validationErrors?.push('API returned empty tree array');
                }

                // Check for items with missing required properties
                const invalidItems = result.tree.filter((item: any) => 
                    !item.path || !item.name || !item.type || 
                    (item.type !== 'file' && item.type !== 'dir')
                );

                if (invalidItems.length > 0) {
                    debugData.validationErrors?.push(`Found ${invalidItems.length} items with missing or invalid properties`);
                    console.log(`[${timestamp}] GitHubDebugPanel: Invalid items:`, invalidItems);
                }
            }

        } catch (error) {
            const errorMessage = error instanceof Error ? error.message : String(error);
            debugData.networkError = errorMessage;
            debugData.validationErrors?.push(`Network error: ${errorMessage}`);
            
            console.error(`[${timestamp}] GitHubDebugPanel: Network error:`, error);
        } finally {
            setIsDebugging(false);
            setDebugInfo(debugData);
            setIsExpanded(true);
            
            if (onDebugComplete) {
                onDebugComplete(debugData);
            }
            
            console.log(`[${timestamp}] GitHubDebugPanel: Debug test completed`, debugData);
        }
    };

    return (
        <Card className="border-orange-200 bg-orange-50">
            <CardHeader>
                <CardTitle className="text-lg flex items-center gap-2">
                    <Bug className="h-5 w-5 text-orange-600" />
                    GitHub Debug Panel
                </CardTitle>
                <CardDescription>
                    Debug tool to investigate "no file found" issues
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="flex items-center gap-2">
                    <Button 
                        onClick={runDebugTest}
                        disabled={isDebugging}
                        variant="outline"
                        size="sm"
                    >
                        {isDebugging ? (
                            <>
                                <RefreshCw className="mr-2 h-4 w-4 animate-spin" />
                                Running Debug Test...
                            </>
                        ) : (
                            <>
                                <Bug className="mr-2 h-4 w-4" />
                                Run Debug Test
                            </>
                        )}
                    </Button>
                    
                    {debugInfo && (
                        <Badge variant={debugInfo.validationErrors?.length ? "destructive" : "default"}>
                            {debugInfo.validationErrors?.length ? 
                                `${debugInfo.validationErrors.length} Issues Found` : 
                                'No Issues Found'
                            }
                        </Badge>
                    )}
                </div>

                {debugInfo && (
                    <Collapsible open={isExpanded} onOpenChange={setIsExpanded}>
                        <CollapsibleTrigger className="flex items-center gap-2 text-sm font-medium">
                            {isExpanded ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                            Debug Results ({debugInfo.timestamp})
                        </CollapsibleTrigger>
                        <CollapsibleContent className="mt-2 space-y-3">
                            {debugInfo.validationErrors && debugInfo.validationErrors.length > 0 && (
                                <div className="bg-red-50 border border-red-200 rounded-md p-3">
                                    <h4 className="font-medium text-red-800 mb-2">Validation Errors:</h4>
                                    <ul className="text-sm text-red-700 space-y-1">
                                        {debugInfo.validationErrors.map((error, index) => (
                                            <li key={index}>• {error}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            {debugInfo.networkError && (
                                <div className="bg-red-50 border border-red-200 rounded-md p-3">
                                    <h4 className="font-medium text-red-800 mb-2">Network Error:</h4>
                                    <p className="text-sm text-red-700">{debugInfo.networkError}</p>
                                </div>
                            )}

                            {debugInfo.apiResponse && (
                                <div className="bg-gray-50 border border-gray-200 rounded-md p-3">
                                    <h4 className="font-medium text-gray-800 mb-2">API Response:</h4>
                                    <pre className="text-xs text-gray-700 overflow-auto max-h-40">
                                        {JSON.stringify(debugInfo.apiResponse, null, 2)}
                                    </pre>
                                </div>
                            )}

                            {debugInfo.treeData && (
                                <div className="bg-blue-50 border border-blue-200 rounded-md p-3">
                                    <h4 className="font-medium text-blue-800 mb-2">
                                        Tree Data Analysis ({debugInfo.treeData.length} items):
                                    </h4>
                                    <div className="text-sm text-blue-700 space-y-1">
                                        <p>Files: {debugInfo.treeData.filter(item => item.type === 'file').length}</p>
                                        <p>Directories: {debugInfo.treeData.filter(item => item.type === 'dir').length}</p>
                                        <p>Sample paths: {debugInfo.treeData.slice(0, 3).map(item => item.path).join(', ')}</p>
                                    </div>
                                </div>
                            )}
                        </CollapsibleContent>
                    </Collapsible>
                )}
            </CardContent>
        </Card>
    );
}
