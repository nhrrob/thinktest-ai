import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { useToast } from '@/hooks/use-toast';
import { fetchWithCsrfRetry, handleApiResponse } from '@/utils/csrf';
import {
    ChevronDown,
    ChevronRight,
    File,
    Folder,
    FolderOpen,
    Loader2,
    RefreshCw,
    FileText,
    Code,
    Search,
    X
} from 'lucide-react';
import { useState, useEffect, useCallback, useMemo } from 'react';
import { useDebounce, useDebouncedValue } from '@/hooks/useDebounce';
import { useGitHubState, useTreeCache } from '@/hooks/useLocalStorage';

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
    const githubState = useGitHubState();
    const treeCache = useTreeCache();
    const [tree, setTree] = useState<TreeNode[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [expandedPaths, setExpandedPaths] = useState<Set<string>>(new Set(githubState.state.expandedPaths));
    const [isRateLimited, setIsRateLimited] = useState(false);
    const [retryAfter, setRetryAfter] = useState<number | null>(null);
    const [lastRequestTime, setLastRequestTime] = useState<number>(0);
    const [searchQuery, setSearchQuery] = useState<string>(githubState.state.searchQuery);
    const [isUsingCache, setIsUsingCache] = useState(false);
    const [filteredTree, setFilteredTree] = useState<TreeNode[]>([]);

    const { error: showError, warning: showWarning, info: showInfo } = useToast();

    // Debounced search query
    const debouncedSearchQuery = useDebouncedValue(searchQuery, 300);

    // Filter tree based on search query
    const filterTree = useCallback((nodes: TreeNode[], query: string): TreeNode[] => {
        if (!query.trim()) {
            return nodes;
        }

        const lowercaseQuery = query.toLowerCase();

        const filterNode = (node: TreeNode): TreeNode | null => {
            const nameMatches = node.name.toLowerCase().includes(lowercaseQuery);
            const pathMatches = node.path.toLowerCase().includes(lowercaseQuery);

            let filteredChildren: TreeNode[] = [];
            if (node.children) {
                filteredChildren = node.children
                    .map(child => filterNode(child))
                    .filter((child): child is TreeNode => child !== null);
            }

            // Include node if it matches or has matching children
            if (nameMatches || pathMatches || filteredChildren.length > 0) {
                return {
                    ...node,
                    children: filteredChildren.length > 0 ? filteredChildren : node.children,
                    isExpanded: filteredChildren.length > 0 ? true : node.isExpanded
                };
            }

            return null;
        };

        return nodes
            .map(node => filterNode(node))
            .filter((node): node is TreeNode => node !== null);
    }, []);

    // Update filtered tree when search query or tree changes
    useEffect(() => {
        const filtered = filterTree(tree, debouncedSearchQuery);
        setFilteredTree(filtered);

        // Auto-expand paths when searching
        if (debouncedSearchQuery.trim()) {
            const expandPaths = (nodes: TreeNode[], paths: Set<string> = new Set()) => {
                nodes.forEach(node => {
                    if (node.type === 'dir' && node.children && node.children.length > 0) {
                        paths.add(node.path);
                        expandPaths(node.children, paths);
                    }
                });
                return paths;
            };

            const pathsToExpand = expandPaths(filtered);
            setExpandedPaths(prev => {
                const newSet = new Set([...prev, ...pathsToExpand]);
                githubState.updateExpandedPaths(Array.from(newSet));
                return newSet;
            });
        }
    }, [tree, debouncedSearchQuery, filterTree]);



    const buildTreeFromFlat = useCallback((items: FileItem[]): TreeNode[] => {


        const tree: TreeNode[] = [];
        const pathMap = new Map<string, TreeNode>();

        // Filter out items with missing required properties
        const validItems = items.filter(item => {
            const isValid = item &&
                item.path &&
                item.name &&
                item.type &&
                (item.type === 'file' || item.type === 'dir');



            return isValid;
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

                tree.push(node);
            } else {
                // Find parent directory
                const parentPath = pathParts.slice(0, -1).join('/');
                const parent = pathMap.get(parentPath);
                if (parent && parent.children) {

                    parent.children.push(node);
                } else {


                    // If parent doesn't exist, add as root level item
                    // This handles cases where the API doesn't return all directory entries
                    tree.push(node);
                }
            }
        }



        return tree;
    }, [expandedPaths]);

    const fetchRepositoryTree = useCallback(async (forceRefresh = false) => {
        if (!repository.owner || !repository.repo) {
            return;
        }

        // Check cache first unless forcing refresh
        if (!forceRefresh) {
            const cachedTree = treeCache.getCachedTree(repository.owner, repository.repo, branch);
            if (cachedTree) {
                console.log(`[GitHubFileBrowser] Using cached tree for ${repository.owner}/${repository.repo}@${branch}`);
                const treeData = buildTreeFromFlat(cachedTree);
                setTree(treeData);
                setIsUsingCache(true);
                setError(null);
                return;
            }
        }

        // Debounce API calls to prevent rapid requests
        const now = Date.now();
        const timeSinceLastRequest = now - lastRequestTime;
        const minInterval = 1000; // 1 second minimum between requests

        if (timeSinceLastRequest < minInterval) {
            return;
        }

        setLastRequestTime(now);

        setIsLoading(true);
        setError(null);
        setIsRateLimited(false);
        setRetryAfter(null);
        setIsUsingCache(false);

        const requestPayload = {
            owner: repository.owner,
            repo: repository.repo,
            branch: branch,
            recursive: true,
        };

        try {
            const response = await fetchWithCsrfRetry('/thinktest/github/tree', {
                method: 'POST',
                body: JSON.stringify(requestPayload),
            });

            const result = await handleApiResponse(response);
            if (!result) return; // Handle redirect cases

            if (response.status === 429) {
                // Handle rate limiting
                setIsRateLimited(true);
                const retryAfterSeconds = result.retry_after || 60;
                setRetryAfter(retryAfterSeconds);
                const errorMessage = result.message || `Rate limit exceeded. Retrying automatically in ${retryAfterSeconds} seconds.`;



                setError(errorMessage);
                onError(errorMessage);
                // Use showError with retryAfter to trigger specialized rate limit handling
                showError(errorMessage, { retryAfter: retryAfterSeconds });
                return;
            }

            if (result.success) {
                // Validate that result.tree is an array
                if (!Array.isArray(result.tree)) {
                    const errorMessage = 'Invalid repository tree data received. Expected an array but got: ' + typeof result.tree;

                    setError(errorMessage);
                    onError(errorMessage);
                    showError(errorMessage);
                    return;
                }

                // Cache the successful result
                treeCache.setCachedTree(repository.owner, repository.repo, branch, result.tree);
                console.log(`[GitHubFileBrowser] Cached tree for ${repository.owner}/${repository.repo}@${branch}`);

                const treeData = buildTreeFromFlat(result.tree);

                setTree(treeData);

                if (treeData.length === 0) {
                    const message = 'Repository appears to be empty or contains no supported file types.';
                    showInfo(message);
                }
            } else {
                const errorMessage = result.message || 'Failed to fetch repository tree. Please check the repository URL and try again.';
                setError(errorMessage);
                onError(errorMessage);
                showError(errorMessage);
            }
        } catch (err) {
            console.error('[GitHubFileBrowser] Network error:', err);

            // Try to fall back to cached data if available
            const cachedTree = treeCache.getCachedTree(repository.owner, repository.repo, branch);
            if (cachedTree) {
                console.log(`[GitHubFileBrowser] Falling back to cached tree due to network error`);
                const treeData = buildTreeFromFlat(cachedTree);
                setTree(treeData);
                setIsUsingCache(true);
                setError('Network error occurred, showing cached data');
                showWarning('Network error occurred, showing cached data');
            } else {
                // No cached data available, this is a real error
                const errorMessage = 'Network error occurred while fetching repository tree. Please check your internet connection and try again.';
                setError(errorMessage);
                onError(errorMessage);
                showError(errorMessage);
            }
        } finally {
            setIsLoading(false);
        }
    }, [repository.owner, repository.repo, branch, buildTreeFromFlat, onError, showError, showWarning, showInfo, treeCache]);

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
            // Persist expanded paths
            githubState.updateExpandedPaths(Array.from(newSet));
            return newSet;
        });
    }, [githubState]);

    // Debounced file selection to prevent rapid API calls
    const debouncedFileSelection = useDebounce((file: FileItem) => {
        onFileSelected(file);
    }, 300); // 300ms debounce

    const handleFileClick = useCallback((file: FileItem) => {
        if (file.type === 'file') {
            debouncedFileSelection(file);
        } else {
            toggleDirectory(file.path);
        }
    }, [debouncedFileSelection, toggleDirectory]);

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

    // Highlight matching text in search results
    const highlightText = (text: string, query: string) => {
        if (!query.trim()) {
            return text;
        }

        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        const parts = text.split(regex);

        return parts.map((part, index) =>
            regex.test(part) ? (
                <span key={index} className="bg-yellow-200 dark:bg-yellow-800 text-yellow-900 dark:text-yellow-100 px-0.5 rounded">
                    {part}
                </span>
            ) : part
        );
    };

    const renderTreeNode = (node: TreeNode, depth: number = 0): React.ReactElement => {
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
                    className={`flex items-center py-1 px-2 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer rounded ${
                        isSelected ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' : 'text-gray-900 dark:text-gray-100'
                    }`}
                    style={{ paddingLeft: `${depth * 16 + 8}px` }}
                    onClick={() => handleFileClick(node)}
                >
                    {node.type === 'dir' && (
                        <span className="mr-1 text-gray-600 dark:text-gray-400">
                            {expandedPaths.has(node.path) ? (
                                <ChevronDown className="h-4 w-4" />
                            ) : (
                                <ChevronRight className="h-4 w-4" />
                            )}
                        </span>
                    )}
                    <Icon className={`h-4 w-4 mr-2 ${
                        node.type === 'dir' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400'
                    }`} />
                    <span className="text-sm truncate text-gray-900 dark:text-gray-100">
                        {highlightText(node.name, debouncedSearchQuery)}
                    </span>
                    {node.type === 'file' && node.size && (
                        <span className="ml-auto text-xs text-gray-500 dark:text-gray-400">
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
        fetchRepositoryTree(true); // Force refresh
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
        <div className="space-y-3">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <h4 className="text-sm font-medium text-gray-700 dark:text-gray-300">Repository Files</h4>
                    {isUsingCache && (
                        <span className="text-xs text-muted-foreground bg-muted px-2 py-1 rounded">
                            Cached
                        </span>
                    )}
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={handleRefresh}
                    disabled={disabled || isLoading}
                    className="h-8 w-8 p-0"
                    title={isUsingCache ? "Refresh from GitHub" : "Refresh"}
                >
                    {isLoading ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <RefreshCw className="h-4 w-4" />
                    )}
                </Button>
            </div>

            {/* Search Input */}
            <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400 dark:text-gray-500" />
                <Input
                    type="text"
                    placeholder="Search files and folders..."
                    value={searchQuery}
                    onChange={(e) => {
                        const newQuery = e.target.value;
                        setSearchQuery(newQuery);
                        githubState.updateSearchQuery(newQuery);
                    }}
                    className="pl-10 pr-10 text-sm"
                    disabled={disabled}
                />
                {searchQuery && (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            setSearchQuery('');
                            githubState.updateSearchQuery('');
                        }}
                        className="absolute right-1 top-1/2 transform -translate-y-1/2 h-6 w-6 p-0 hover:bg-gray-100"
                    >
                        <X className="h-3 w-3" />
                    </Button>
                )}
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

            <div className="border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-900 max-h-96 overflow-y-auto">
                {(debouncedSearchQuery ? filteredTree : tree).length > 0 ? (
                    <div className="p-2">
                        {(debouncedSearchQuery ? filteredTree : tree).map(node => renderTreeNode(node))}
                    </div>
                ) : (
                    <div className="p-4 text-center text-gray-500 dark:text-gray-400">
                        <File className="h-8 w-8 mx-auto mb-2 text-gray-400 dark:text-gray-500" />
                        <p className="text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                            {debouncedSearchQuery ? 'No matching files found' : 'No files found'}
                        </p>
                        <p className="text-xs text-gray-400 dark:text-gray-500">
                            {debouncedSearchQuery ? (
                                'Try adjusting your search query or clear the search to see all files.'
                            ) : !error ? (
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
