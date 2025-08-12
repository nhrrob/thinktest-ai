import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Skeleton } from '@/components/ui/skeleton';
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

    const getCsrfToken = () => {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    };

    const buildTreeFromFlat = useCallback((items: FileItem[]): TreeNode[] => {
        const tree: TreeNode[] = [];
        const pathMap = new Map<string, TreeNode>();

        // Filter out items with missing required properties
        const validItems = items.filter(item =>
            item &&
            item.path &&
            item.name &&
            item.type &&
            (item.type === 'file' || item.type === 'dir')
        );

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
                }
            }
        }

        return tree;
    }, [expandedPaths]);

    const fetchRepositoryTree = useCallback(async () => {
        if (!repository.owner || !repository.repo) return;

        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch('/thinktest/github/tree', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    owner: repository.owner,
                    repo: repository.repo,
                    branch: branch,
                    recursive: true,
                }),
            });

            const result = await response.json();

            if (result.success) {
                // Validate that result.tree is an array
                if (!Array.isArray(result.tree)) {
                    const errorMessage = 'Invalid repository tree data received';
                    setError(errorMessage);
                    onError(errorMessage);
                    return;
                }

                const treeData = buildTreeFromFlat(result.tree);
                setTree(treeData);
            } else {
                const errorMessage = result.message || 'Failed to fetch repository tree';
                setError(errorMessage);
                onError(errorMessage);
            }
        } catch (err) {
            const errorMessage = 'Network error occurred while fetching repository tree';
            setError(errorMessage);
            onError(errorMessage);
        } finally {
            setIsLoading(false);
        }
    }, [repository.owner, repository.repo, branch, buildTreeFromFlat, onError]);

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

    const handleFileClick = useCallback((file: FileItem) => {
        if (file.type === 'file') {
            onFileSelected(file);
        } else {
            toggleDirectory(file.path);
        }
    }, [onFileSelected, toggleDirectory]);

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
                <Alert>
                    <AlertDescription>{error}</AlertDescription>
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
                        <p className="text-sm">No files found</p>
                    </div>
                )}
            </div>
        </div>
    );
}
