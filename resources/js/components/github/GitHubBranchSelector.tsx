import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useBranchCache } from '@/hooks/useLocalStorage';
import { GitBranch, Loader2, RefreshCw, Shield, Clock } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

interface Branch {
    name: string;
    commit_sha: string;
    commit_url: string;
    protected: boolean;
}

interface Repository {
    owner: string;
    repo: string;
    default_branch: string;
}

interface GitHubBranchSelectorProps {
    repository: Repository;
    onBranchSelected: (branch: Branch) => void;
    onError: (error: string) => void;
    disabled?: boolean;
    selectedBranch?: string;
}

export default function GitHubBranchSelector({ repository, onBranchSelected, onError, disabled = false, selectedBranch }: GitHubBranchSelectorProps) {
    const [branches, setBranches] = useState<Branch[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [currentBranch, setCurrentBranch] = useState<string>(selectedBranch || repository.default_branch);
    const [isUsingCache, setIsUsingCache] = useState(false);
    const [lastFetchTime, setLastFetchTime] = useState<number | null>(null);

    const branchCache = useBranchCache();

    const getNetworkErrorMessage = (error: any): string => {
        if (error?.name === 'TypeError' && error?.message?.includes('fetch')) {
            return 'Unable to connect to GitHub. Please check your internet connection.';
        }
        if (error?.message?.includes('timeout')) {
            return 'Request timed out. GitHub may be experiencing issues.';
        }
        return 'Network error occurred while fetching branches';
    };

    const fetchBranches = useCallback(async (forceRefresh = false) => {
        // Check cache first unless forcing refresh
        if (!forceRefresh) {
            const cachedBranches = branchCache.getCachedBranches(repository.owner, repository.repo);
            if (cachedBranches) {
                console.log(`[GitHubBranchSelector] Using cached branches for ${repository.owner}/${repository.repo}`);
                setBranches(cachedBranches);
                setIsUsingCache(true);
                setError(null);

                // Auto-select default branch if no branch is currently selected
                if (!currentBranch && cachedBranches.length > 0) {
                    const defaultBranch = cachedBranches.find((b: Branch) => b.name === repository.default_branch) || cachedBranches[0];
                    setCurrentBranch(defaultBranch.name);
                    onBranchSelected(defaultBranch);
                }
                return;
            }
        }

        setIsLoading(true);
        setError(null);
        setIsUsingCache(false);

        try {
            console.log(`[GitHubBranchSelector] Fetching branches from API for ${repository.owner}/${repository.repo}`);
            const response = await fetch('/thinktest/github/branches', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    owner: repository.owner,
                    repo: repository.repo,
                }),
            });

            const result = await response.json();

            if (result.success) {
                setBranches(result.branches);
                setLastFetchTime(Date.now());

                // Cache the successful result
                branchCache.setCachedBranches(repository.owner, repository.repo, result.branches);
                console.log(`[GitHubBranchSelector] Cached branches for ${repository.owner}/${repository.repo}`);

                // Auto-select default branch if no branch is currently selected
                if (!currentBranch && result.branches.length > 0) {
                    const defaultBranch = result.branches.find((b: Branch) => b.name === repository.default_branch) || result.branches[0];
                    setCurrentBranch(defaultBranch.name);
                    onBranchSelected(defaultBranch);
                }
            } else {
                const errorMessage = result.message || 'Failed to fetch branches';
                setError(errorMessage);
                onError(errorMessage);

                // Try to fall back to cached data if available
                const cachedBranches = branchCache.getCachedBranches(repository.owner, repository.repo);
                if (cachedBranches) {
                    console.log(`[GitHubBranchSelector] Falling back to cached branches due to API error`);
                    setBranches(cachedBranches);
                    setIsUsingCache(true);
                    setError(`${errorMessage} (showing cached data)`);
                }
            }
        } catch (fetchError) {
            const errorMessage = getNetworkErrorMessage(fetchError);
            console.error('[GitHubBranchSelector] Network error:', fetchError);

            // Try to fall back to cached data if available
            const cachedBranches = branchCache.getCachedBranches(repository.owner, repository.repo);
            if (cachedBranches) {
                console.log(`[GitHubBranchSelector] Falling back to cached branches due to network error`);
                setBranches(cachedBranches);
                setIsUsingCache(true);
                setError(`${errorMessage} (showing cached data)`);
                // Don't call onError when we have fallback data, just log it
                console.warn(`[GitHubBranchSelector] ${errorMessage}, but using cached data`);
            } else {
                // No cached data available, this is a real error
                setError(errorMessage);
                onError(errorMessage);
            }
        } finally {
            setIsLoading(false);
        }
    }, [repository.owner, repository.repo, repository.default_branch, currentBranch, onBranchSelected, onError, branchCache]);

    useEffect(() => {
        if (repository.owner && repository.repo) {
            fetchBranches();
        }
    }, [repository.owner, repository.repo, fetchBranches]);

    // Clean up expired cache entries periodically
    useEffect(() => {
        const interval = setInterval(() => {
            branchCache.cleanExpiredEntries();
        }, 60000); // Clean every minute

        return () => clearInterval(interval);
    }, [branchCache]);

    const handleBranchChange = (branchName: string) => {
        setCurrentBranch(branchName);
        const selectedBranchData = branches.find((b) => b.name === branchName);
        if (selectedBranchData) {
            onBranchSelected(selectedBranchData);
        }
    };

    const handleRefresh = () => {
        console.log(`[GitHubBranchSelector] Manual refresh requested for ${repository.owner}/${repository.repo}`);
        fetchBranches(true); // Force refresh
    };

    const formatCommitSha = (sha: string): string => {
        return sha.substring(0, 7);
    };

    const formatCacheAge = (timestamp: number): string => {
        const ageMs = Date.now() - timestamp;
        const ageMinutes = Math.floor(ageMs / 60000);
        const ageSeconds = Math.floor((ageMs % 60000) / 1000);

        if (ageMinutes > 0) {
            return `${ageMinutes}m ${ageSeconds}s ago`;
        }
        return `${ageSeconds}s ago`;
    };

    const cacheInfo = branchCache.getCacheInfo(repository.owner, repository.repo);

    return (
        <div className="space-y-4">
            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <Label htmlFor="branch-select" className="text-sm font-medium text-gray-700">
                        Select Branch
                    </Label>
                    <div className="flex items-center space-x-2">
                        {isUsingCache && cacheInfo.cached && (
                            <div className="flex items-center space-x-1 text-xs text-muted-foreground">
                                <Clock className="h-3 w-3" />
                                <span>Cached {formatCacheAge(cacheInfo.age ? Date.now() - cacheInfo.age : Date.now())}</span>
                            </div>
                        )}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleRefresh}
                            disabled={disabled || isLoading}
                            title="Refresh branches from GitHub"
                            className="h-8 px-2"
                        >
                            <RefreshCw className={`h-3 w-3 ${isLoading ? 'animate-spin' : ''}`} />
                        </Button>
                    </div>
                </div>

                <Select value={currentBranch} onValueChange={handleBranchChange} disabled={disabled || isLoading || branches.length === 0}>
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder={isLoading ? 'Loading branches...' : 'Select a branch'} />
                    </SelectTrigger>
                    <SelectContent>
                        {branches.map((branch) => (
                            <SelectItem key={branch.name} value={branch.name}>
                                <div className="flex w-full items-center justify-between">
                                    <div className="flex items-center space-x-2">
                                        <GitBranch className="h-4 w-4 text-gray-500" />
                                        <span className="font-medium">{branch.name}</span>
                                        {branch.name === repository.default_branch && (
                                            <span className="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">
                                                Default
                                            </span>
                                        )}
                                        {branch.protected && <Shield className="h-3 w-3 text-yellow-500" title="Protected branch" />}
                                    </div>
                                    <span className="ml-2 text-xs text-gray-500">{formatCommitSha(branch.commit_sha)}</span>
                                </div>
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {isLoading && (
                    <div className="flex items-center space-x-2 text-sm text-gray-500">
                        <Loader2 className="h-4 w-4 animate-spin" />
                        <span>Loading branches...</span>
                    </div>
                )}

                {branches.length > 0 && !isLoading && (
                    <div className="flex items-center justify-between text-xs text-gray-500">
                        <span>
                            Found {branches.length} branch{branches.length !== 1 ? 'es' : ''}
                            {isUsingCache && ' (cached)'}
                        </span>
                        {lastFetchTime && !isUsingCache && (
                            <span>Updated {formatCacheAge(lastFetchTime)}</span>
                        )}
                    </div>
                )}
            </div>

            {error && (
                <Alert variant={isUsingCache ? "default" : "destructive"}>
                    <AlertDescription>
                        {error}
                        {isUsingCache && (
                            <div className="mt-2 text-xs">
                                <strong>Note:</strong> Showing cached data from previous successful request.
                            </div>
                        )}
                    </AlertDescription>
                </Alert>
            )}


        </div>
    );
}
