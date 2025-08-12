import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Skeleton } from '@/components/ui/skeleton';
import { useToast } from '@/hooks/use-toast';
import { 
    ChevronDown, 
    ChevronRight, 
    File, 
    Folder, 
    FolderOpen, 
    Loader2, 
    RefreshCw,
    FileText,
    Code
} from 'lucide-react';
import { useState, useEffect, useCallback } from 'react';
import { useDebounce } from '@/hooks/useDebounce';

interface Repository {
    owner: string;
    repo: string;
    full_name: string;
    default_branch: string;
}

interface FileItem {
    name: string;
    path: string;
    type: 'file' | 'dir';
    size: number;
    sha: string;
    url: string;
    html_url: string;
    download_url?: string;
}

interface TreeNode extends FileItem {
    children?: TreeNode[];
    isExpanded?: boolean;
    isLoading?: boolean;
}

interface GitHubFileBrowserProps {
    repository: Repository;
    branch: string;
    onFileSelected: (file: FileItem) => void;
    onError: (error: string) => void;
    disabled?: boolean;
    selectedFilePath?: string;
}

export default function GitHubFileBrowser({
    repository,
    branch,
    onFileSelected,
    onError,
    disabled = false,
    selectedFilePath
}: GitHubFileBrowserProps) {
    const [tree, setTree] = useState<TreeNode[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [expandedPaths, setExpandedPaths] = useState<Set<string>>(new Set());
    const [isRateLimited, setIsRateLimited] = useState(false);
    const [retryAfter, setRetryAfter] = useState<number | null>(null);

    const { error: showError, warning: showWarning, info: showInfo } = useToast();

    // Enhanced logging function with timestamps and context
    const logDebug = useCallback((message: string, data?: any) => {
        const timestamp = new Date().toISOString();
        const context = {
            timestamp,
            component: 'GitHubFileBrowser',
            repository: `${repository.owner}/${repository.repo}`,
            branch,
            ...data
        };
        console.log(`[${timestamp}] GitHubFileBrowser: ${message}`, context);
    }, [repository.owner, repository.repo, branch]);

    const getCsrfToken = () => {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    };

    const buildTreeFromFlat = useCallback((items: FileItem[]): TreeNode[] => {
        logDebug('Building tree from flat items', {
            totalItems: items.length,
            sampleItems: items.slice(0, 3)
        });

        const tree: TreeNode[] = [];
        const pathMap = new Map<string, TreeNode>();

        // Filter out items with missing required properties
        const validItems = items.filter(item => {
            const isValid = item &&
                item.path &&
                item.name &&
                item.type &&
                (item.type === 'file' || item.type === 'dir');

            if (!isValid) {
                logDebug('Filtering out invalid item', { item });
            }

            return isValid;
        });

        logDebug('Filtered valid items', {
            originalCount: items.length,
            validCount: validItems.length,
            filteredOut: items.length - validItems.length
        });

        // Sort items to ensure directories come before their contents
        const sortedItems = [...validItems].sort((a, b) => {
            if (a.type !== b.type) {
                return a.type === 'dir' ? -1 : 1;
            }
            return a.path.localeCompare(b.path);
        });

        for (const item of sortedItems) {
            const pathParts = item.path.split('/');
            const node: TreeNode = {
                ...item,
                children: item.type === 'dir' ? [] : undefined,
                isExpanded: expandedPaths.has(item.path),
                isLoading: false,
            };

            pathMap.set(item.path, node);

            if (pathParts.length === 1) {
                // Root level item
                logDebug('Adding root level item', {
                    path: item.path,
                    type: item.type,
                    name: item.name
                });
                tree.push(node);
            } else {
                // Find parent directory
                const parentPath = pathParts.slice(0, -1).join('/');
                const parent = pathMap.get(parentPath);
                if (parent && parent.children) {
                    logDebug('Adding child item to parent', {
                        childPath: item.path,
                        parentPath,
                        childType: item.type,
                        parentType: parent.type
                    });
                    parent.children.push(node);
                } else {
                    logDebug('Parent not found for item', {
                        childPath: item.path,
                        parentPath,
                        parentExists: !!parent,
                        parentHasChildren: parent?.children !== undefined,
                        availableParents: Array.from(pathMap.keys())
                    });

                    // If parent doesn't exist, add as root level item
                    // This handles cases where the API doesn't return all directory entries
                    tree.push(node);
                }
            }
        }

        logDebug('Tree building completed', {
            finalTreeLength: tree.length,
            totalNodesCreated: pathMap.size,
            rootLevelItems: tree.map(node => ({ path: node.path, type: node.type }))
        });

        return tree;
    }, [expandedPaths]);

    const fetchRepositoryTree = useCallback(async () => {
        if (!repository.owner || !repository.repo) {
            logDebug('Missing repository owner or repo', { owner: repository.owner, repo: repository.repo });
            return;
        }

        logDebug('Starting repository tree fetch', {
            owner: repository.owner,
            repo: repository.repo,
            branch,
            recursive: true
        });

        setIsLoading(true);
        setError(null);
        setIsRateLimited(false);
        setRetryAfter(null);

        const requestPayload = {
            owner: repository.owner,
            repo: repository.repo,
            branch: branch,
            recursive: true,
        };

        try {
            logDebug('Making API request to /thinktest/github/tree', { payload: requestPayload });

            const response = await fetch('/thinktest/github/tree', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(requestPayload),
            });

            logDebug('API response received', {
                status: response.status,
                statusText: response.statusText,
                headers: Object.fromEntries(response.headers.entries())
            });

            const result = await response.json();
            logDebug('API response parsed', { result });

            if (response.status === 429) {
                // Handle rate limiting
                setIsRateLimited(true);
                const retryAfterSeconds = result.retry_after || 60;
                setRetryAfter(retryAfterSeconds);
                const errorMessage = result.message || `Rate limit exceeded. Retrying automatically in ${retryAfterSeconds} seconds.`;

                logDebug('Rate limit exceeded', {
                    retryAfterSeconds,
                    message: result.message,
                    rateLimitInfo: result
                });

                setError(errorMessage);
                onError(errorMessage);
                showWarning(errorMessage, { duration: retryAfterSeconds * 1000 });
                return;
            }

            if (result.success) {
                // Validate that result.tree is an array
                if (!Array.isArray(result.tree)) {
                    const errorMessage = 'Invalid repository tree data received. Expected an array but got: ' + typeof result.tree;
                    logDebug('Invalid tree data structure', {
                        expectedType: 'array',
                        actualType: typeof result.tree,
                        treeData: result.tree
                    });
                    setError(errorMessage);
                    onError(errorMessage);
                    showError(errorMessage);
                    return;
                }

                logDebug('Repository tree data received', {
                    treeLength: result.tree.length,
                    sampleItems: result.tree.slice(0, 3)
                });

                const treeData = buildTreeFromFlat(result.tree);
                logDebug('Tree built from flat data', {
                    originalLength: result.tree.length,
                    treeLength: treeData.length,
                    treeStructure: treeData.map(node => ({ path: node.path, type: node.type, childrenCount: node.children?.length || 0 }))
                });

                setTree(treeData);

                if (treeData.length === 0) {
                    const message = 'Repository appears to be empty or contains no supported file types.';
                    logDebug('Empty tree after building', { originalTree: result.tree });
                    showInfo(message);
                }
            } else {
                const errorMessage = result.message || 'Failed to fetch repository tree. Please check the repository URL and try again.';
                logDebug('API request failed', {
                    success: result.success,
                    message: result.message,
                    fullResult: result
                });
                setError(errorMessage);
                onError(errorMessage);
                showError(errorMessage);
            }
        } catch (err) {
            const errorMessage = 'Network error occurred while fetching repository tree. Please check your internet connection and try again.';
            logDebug('Network error during API request', {
                error: err instanceof Error ? err.message : String(err),
                stack: err instanceof Error ? err.stack : undefined,
                requestPayload
            });
            setError(errorMessage);
            onError(errorMessage);
            showError(errorMessage);
        } finally {
            setIsLoading(false);
            logDebug('Repository tree fetch completed', { isLoading: false });
        }
    }, [repository.owner, repository.repo, branch, buildTreeFromFlat, onError, logDebug, showError, showWarning, showInfo]);

    // Auto-retry after rate limit expires
    useEffect(() => {
        if (isRateLimited && retryAfter) {
            const timer = setTimeout(() => {
                fetchRepositoryTree();
            }, retryAfter * 1000);

            return () => clearTimeout(timer);
        }
    }, [isRateLimited, retryAfter, fetchRepositoryTree]);

    useEffect(() => {
        if (repository.owner && repository.repo && branch) {
            fetchRepositoryTree();
        }
    }, [repository.owner, repository.repo, branch, fetchRepositoryTree]);

    const toggleDirectory = useCallback((path: string) => {
        setExpandedPaths(prev => {
            const newSet = new Set(prev);
            if (newSet.has(path)) {
                newSet.delete(path);
            } else {
                newSet.add(path);
            }
            return newSet;
        });
    }, []);

    // Debounced file selection to prevent rapid API calls
    const debouncedFileSelection = useDebounce((file: FileItem) => {
        logDebug('File selected (debounced)', {
            fileName: file.name,
            filePath: file.path,
            fileType: file.type,
            fileSize: file.size
        });
        onFileSelected(file);
    }, 300); // 300ms debounce

    const handleFileClick = useCallback((file: FileItem) => {
        logDebug('File clicked', {
            fileName: file.name,
            filePath: file.path,
            fileType: file.type,
            action: file.type === 'file' ? 'select' : 'toggle_directory'
        });

        if (file.type === 'file') {
            debouncedFileSelection(file);
        } else {
            toggleDirectory(file.path);
        }
    }, [debouncedFileSelection, toggleDirectory, logDebug]);

    const getFileIcon = (file: FileItem) => {
        if (file.type === 'dir') {
            return expandedPaths.has(file.path) ? FolderOpen : Folder;
        }

        // Handle case where file.name might be undefined
        if (!file.name) {
            return File;
        }

        const extension = file.name.split('.').pop()?.toLowerCase();
        switch (extension) {
            case 'php':
            case 'js':
            case 'ts':
            case 'jsx':
            case 'tsx':
                return Code;
            case 'md':
            case 'txt':
                return FileText;
            default:
                return File;
        }
    };

    const renderTreeNode = (node: TreeNode, depth: number = 0): JSX.Element => {
        // Skip rendering if node is invalid
        if (!node || !node.path || !node.name) {
            return <div key={`invalid-${depth}-${Math.random()}`}></div>;
        }

        const Icon = getFileIcon(node);
        const isSelected = selectedFilePath === node.path;
        const hasChildren = node.children && node.children.length > 0;

        return (
            <div key={node.path} className="select-none">
                <div
                    className={`flex items-center py-1 px-2 hover:bg-gray-100 cursor-pointer rounded ${
                        isSelected ? 'bg-blue-100 text-blue-800' : ''
                    }`}
                    style={{ paddingLeft: `${depth * 16 + 8}px` }}
                    onClick={() => handleFileClick(node)}
                >
                    {node.type === 'dir' && (
                        <span className="mr-1">
                            {expandedPaths.has(node.path) ? (
                                <ChevronDown className="h-4 w-4" />
                            ) : (
                                <ChevronRight className="h-4 w-4" />
                            )}
                        </span>
                    )}
                    <Icon className={`h-4 w-4 mr-2 ${
                        node.type === 'dir' ? 'text-blue-600' : 'text-gray-600'
                    }`} />
                    <span className="text-sm truncate">{node.name}</span>
                    {node.type === 'file' && node.size && (
                        <span className="ml-auto text-xs text-gray-500">
                            {(node.size / 1024).toFixed(1)}KB
                        </span>
                    )}
                </div>

                {node.type === 'dir' && expandedPaths.has(node.path) && hasChildren && (
                    <div>
                        {node.children!.map(child => renderTreeNode(child, depth + 1))}
                    </div>
                )}
            </div>
        );
    };

    const handleRefresh = () => {
        fetchRepositoryTree();
    };

    if (isLoading && tree.length === 0) {
        return (
            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <h4 className="text-sm font-medium text-gray-700">Repository Files</h4>
                    <Skeleton className="h-8 w-8" />
                </div>
                <div className="space-y-1">
                    {[...Array(8)].map((_, i) => (
                        <Skeleton key={i} className="h-6 w-full" />
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between">
                <h4 className="text-sm font-medium text-gray-700">Repository Files</h4>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={handleRefresh}
                    disabled={disabled || isLoading}
                    className="h-8 w-8 p-0"
                >
                    {isLoading ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <RefreshCw className="h-4 w-4" />
                    )}
                </Button>
            </div>

            {error && (
                <Alert className={isRateLimited ? "border-orange-200 bg-orange-50" : ""}>
                    <AlertDescription>
                        {error}
                        {isRateLimited && retryAfter && (
                            <div className="mt-2">
                                <p className="text-sm text-orange-600">
                                    Auto-retrying in {retryAfter} seconds...
                                </p>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={fetchRepositoryTree}
                                    className="mt-2"
                                    disabled={isLoading}
                                >
                                    Retry Now
                                </Button>
                            </div>
                        )}
                    </AlertDescription>
                </Alert>
            )}

            <div className="border rounded-md bg-white max-h-96 overflow-y-auto">
                {tree.length > 0 ? (
                    <div className="p-2">
                        {tree.map(node => renderTreeNode(node))}
                    </div>
                ) : (
                    <div className="p-4 text-center text-gray-500">
                        <File className="h-8 w-8 mx-auto mb-2 text-gray-400" />
                        <p className="text-sm font-medium mb-1">No files found</p>
                        <p className="text-xs text-gray-400">
                            {!error ? (
                                <>
                                    Repository may be empty or contain only unsupported file types.<br />
                                    Try refreshing or check the repository URL and branch.
                                </>
                            ) : (
                                'Please resolve the error above and try again.'
                            )}
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
}
