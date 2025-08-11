import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, Github, ExternalLink, GitBranch, Calendar, FileText } from 'lucide-react';

interface Repository {
    id: number;
    name: string;
    full_name: string;
    description?: string;
    private: boolean;
    default_branch: string;
    size: number;
    language?: string;
    updated_at: string;
    html_url: string;
    owner: string;
    repo: string;
}

interface GitHubRepositoryInputProps {
    onRepositoryValidated: (repository: Repository) => void;
    onError: (error: string) => void;
    disabled?: boolean;
}

export default function GitHubRepositoryInput({ 
    onRepositoryValidated, 
    onError, 
    disabled = false 
}: GitHubRepositoryInputProps) {
    const [repositoryUrl, setRepositoryUrl] = useState('');
    const [isValidating, setIsValidating] = useState(false);
    const [validatedRepository, setValidatedRepository] = useState<Repository | null>(null);
    const [error, setError] = useState<string | null>(null);

    const handleValidateRepository = async () => {
        if (!repositoryUrl.trim()) {
            setError('Please enter a repository URL');
            return;
        }

        setIsValidating(true);
        setError(null);
        setValidatedRepository(null);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('/thinktest/github/validate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                body: JSON.stringify({
                    repository_url: repositoryUrl,
                }),
            });

            const result = await response.json();

            if (result.success) {
                setValidatedRepository(result.repository);
                onRepositoryValidated(result.repository);
            } else {
                let errorMessage = result.message || 'Failed to validate repository';

                // Handle specific HTTP status codes
                if (response.status === 419) {
                    errorMessage = 'CSRF token mismatch. Please refresh the page and try again.';
                } else if (response.status === 422) {
                    errorMessage = result.message || 'Invalid repository URL format.';
                } else if (response.status === 429) {
                    errorMessage = result.message || 'Rate limit exceeded. Please try again later.';
                }

                setError(errorMessage);
                onError(errorMessage);

                // Handle retry scenarios
                if (result.retry_possible && result.retry_after) {
                    setTimeout(() => {
                        setError(null);
                    }, result.retry_after * 1000);
                }
            }
        } catch (err) {
            let errorMessage = 'Network error occurred while validating repository';

            if (err instanceof Error) {
                if (err.message.includes('timeout')) {
                    errorMessage = 'Request timed out. Please try again.';
                } else if (err.message.includes('network')) {
                    errorMessage = 'Network connection error. Please check your internet connection.';
                }
            }

            setError(errorMessage);
            onError(errorMessage);
        } finally {
            setIsValidating(false);
        }
    };

    const handleUrlChange = (value: string) => {
        setRepositoryUrl(value);
        setError(null);
        setValidatedRepository(null);
    };

    const formatFileSize = (bytes: number): string => {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }

        return `${size.toFixed(1)} ${units[unitIndex]}`;
    };

    const formatDate = (dateString: string): string => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    return (
        <div className="space-y-4">
            <div className="space-y-2">
                <Label htmlFor="repository-url" className="text-sm font-medium text-gray-700">
                    GitHub Repository URL
                </Label>
                <div className="flex space-x-2">
                    <div className="relative flex-1">
                        <Github className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <Input
                            id="repository-url"
                            type="text"
                            placeholder="https://github.com/owner/repository"
                            value={repositoryUrl}
                            onChange={(e) => handleUrlChange(e.target.value)}
                            disabled={disabled || isValidating}
                            className="pl-10"
                        />
                    </div>
                    <Button
                        onClick={handleValidateRepository}
                        disabled={disabled || isValidating || !repositoryUrl.trim()}
                        className="px-4"
                    >
                        {isValidating ? (
                            <>
                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                Validating...
                            </>
                        ) : (
                            'Validate'
                        )}
                    </Button>
                </div>
                <p className="text-xs text-gray-500">
                    Enter a GitHub repository URL (e.g., https://github.com/owner/repo)
                </p>
            </div>

            {error && (
                <Alert variant="destructive">
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            {validatedRepository && (
                <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                    <div className="flex items-start justify-between">
                        <div className="flex-1">
                            <div className="flex items-center space-x-2 mb-2">
                                <Github className="h-5 w-5 text-green-600" />
                                <h4 className="text-lg font-medium text-green-800">
                                    {validatedRepository.full_name}
                                </h4>
                                {validatedRepository.private && (
                                    <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Private
                                    </span>
                                )}
                            </div>
                            
                            {validatedRepository.description && (
                                <p className="text-green-700 mb-3">
                                    {validatedRepository.description}
                                </p>
                            )}
                            
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div className="flex items-center space-x-2">
                                    <GitBranch className="h-4 w-4 text-green-600" />
                                    <span className="text-green-700">
                                        Default: {validatedRepository.default_branch}
                                    </span>
                                </div>
                                
                                <div className="flex items-center space-x-2">
                                    <FileText className="h-4 w-4 text-green-600" />
                                    <span className="text-green-700">
                                        Size: {formatFileSize(validatedRepository.size)}
                                    </span>
                                </div>
                                
                                {validatedRepository.language && (
                                    <div className="flex items-center space-x-2">
                                        <div className="h-4 w-4 rounded-full bg-green-600"></div>
                                        <span className="text-green-700">
                                            {validatedRepository.language}
                                        </span>
                                    </div>
                                )}
                                
                                <div className="flex items-center space-x-2">
                                    <Calendar className="h-4 w-4 text-green-600" />
                                    <span className="text-green-700">
                                        Updated: {formatDate(validatedRepository.updated_at)}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <a
                            href={validatedRepository.html_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="ml-4 inline-flex items-center text-green-600 hover:text-green-800"
                        >
                            <ExternalLink className="h-4 w-4" />
                        </a>
                    </div>
                </div>
            )}
        </div>
    );
}
