import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { GitBranch, Loader2, RefreshCw, Shield } from 'lucide-react';
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

    const fetchBranches = useCallback(async () => {
        setIsLoading(true);
        setError(null);

        try {
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
            }
        } catch {
            const errorMessage = 'Network error occurred while fetching branches';
            setError(errorMessage);
            onError(errorMessage);
        } finally {
            setIsLoading(false);
        }
    }, [repository.owner, repository.repo, repository.default_branch, currentBranch, onBranchSelected, onError]);

    useEffect(() => {
        if (repository.owner && repository.repo) {
            fetchBranches();
        }
    }, [repository.owner, repository.repo, fetchBranches]);

    const handleBranchChange = (branchName: string) => {
        setCurrentBranch(branchName);
        const selectedBranchData = branches.find((b) => b.name === branchName);
        if (selectedBranchData) {
            onBranchSelected(selectedBranchData);
        }
    };

    const handleRefresh = () => {
        fetchBranches();
    };

    const formatCommitSha = (sha: string): string => {
        return sha.substring(0, 7);
    };

    return (
        <div className="space-y-4">
            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <Label htmlFor="branch-select" className="text-sm font-medium text-gray-700">
                        Select Branch
                    </Label>
                    <Button variant="outline" size="sm" onClick={handleRefresh} disabled={disabled || isLoading} className="h-8 px-2">
                        <RefreshCw className={`h-3 w-3 ${isLoading ? 'animate-spin' : ''}`} />
                    </Button>
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
                    <p className="text-xs text-gray-500">
                        Found {branches.length} branch{branches.length !== 1 ? 'es' : ''}
                    </p>
                )}
            </div>

            {error && (
                <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            {currentBranch && branches.length > 0 && (
                <div className="rounded-md border border-blue-200 bg-blue-50 p-3">
                    <div className="flex items-center space-x-2">
                        <GitBranch className="h-4 w-4 text-blue-600" />
                        <span className="text-sm font-medium text-blue-800">Selected: {currentBranch}</span>
                        {currentBranch === repository.default_branch && (
                            <span className="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">
                                Default
                            </span>
                        )}
                    </div>
                    {(() => {
                        const selectedBranchData = branches.find((b) => b.name === currentBranch);
                        return (
                            selectedBranchData && (
                                <div className="mt-2 text-xs text-blue-700">
                                    <div className="flex items-center space-x-4">
                                        <span>Commit: {formatCommitSha(selectedBranchData.commit_sha)}</span>
                                        {selectedBranchData.protected && (
                                            <div className="flex items-center space-x-1">
                                                <Shield className="h-3 w-3 text-yellow-500" />
                                                <span>Protected</span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )
                        );
                    })()}
                </div>
            )}
        </div>
    );
}
