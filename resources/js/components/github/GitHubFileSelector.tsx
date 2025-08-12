import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    File, 
    Code, 
    FileText, 
    ExternalLink, 
    Download,
    Loader2,
    CheckCircle
} from 'lucide-react';
import { useState, useEffect } from 'react';

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

interface FileContent {
    name: string;
    path: string;
    content: string;
    size: number;
    sha: string;
    encoding: string;
    url: string;
    html_url: string;
    download_url: string;
}

interface GitHubFileSelectorProps {
    repository: Repository;
    branch: string;
    selectedFile: FileItem | null;
    onFileContentLoaded: (fileContent: FileContent) => void;
    onError: (error: string) => void;
    onGenerateTests: () => void;
    disabled?: boolean;
    isGenerating?: boolean;
}

export default function GitHubFileSelector({ 
    repository, 
    branch, 
    selectedFile, 
    onFileContentLoaded, 
    onError, 
    onGenerateTests,
    disabled = false,
    isGenerating = false
}: GitHubFileSelectorProps) {
    const [fileContent, setFileContent] = useState<FileContent | null>(null);
    const [isLoadingContent, setIsLoadingContent] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const getCsrfToken = () => {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    };

    const getFileIcon = (fileName: string) => {
        const extension = fileName.split('.').pop()?.toLowerCase();
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

    const getFileTypeColor = (fileName: string) => {
        const extension = fileName.split('.').pop()?.toLowerCase();
        switch (extension) {
            case 'php':
                return 'bg-purple-100 text-purple-800';
            case 'js':
            case 'jsx':
                return 'bg-yellow-100 text-yellow-800';
            case 'ts':
            case 'tsx':
                return 'bg-blue-100 text-blue-800';
            case 'md':
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const formatFileSize = (bytes: number) => {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    };

    const fetchFileContent = async (file: FileItem) => {
        setIsLoadingContent(true);
        setError(null);
        setFileContent(null);

        try {
            const response = await fetch('/thinktest/github/file', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    owner: repository.owner,
                    repo: repository.repo,
                    path: file.path,
                    branch: branch,
                }),
            });

            const result = await response.json();

            if (result.success) {
                setFileContent(result.file);
                onFileContentLoaded(result.file);
            } else {
                const errorMessage = result.message || 'Failed to fetch file content';
                setError(errorMessage);
                onError(errorMessage);
            }
        } catch (err) {
            const errorMessage = 'Network error occurred while fetching file content';
            setError(errorMessage);
            onError(errorMessage);
        } finally {
            setIsLoadingContent(false);
        }
    };

    useEffect(() => {
        if (selectedFile && selectedFile.type === 'file') {
            fetchFileContent(selectedFile);
        } else {
            setFileContent(null);
            setError(null);
        }
    }, [selectedFile, repository.owner, repository.repo, branch]);

    if (!selectedFile) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="text-lg">Select a File</CardTitle>
                    <CardDescription>
                        Choose a file from the repository to generate tests for
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="text-center py-8">
                        <File className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                        <p className="text-gray-500">No file selected</p>
                        <p className="text-sm text-gray-400 mt-1">
                            Browse the repository files and click on a file to select it
                        </p>
                    </div>
                </CardContent>
            </Card>
        );
    }

    if (selectedFile.type === 'dir') {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="text-lg">Directory Selected</CardTitle>
                    <CardDescription>
                        Please select a file, not a directory
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="text-center py-8">
                        <File className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                        <p className="text-gray-500">Directory cannot be processed</p>
                        <p className="text-sm text-gray-400 mt-1">
                            Select an individual file to generate tests for
                        </p>
                    </div>
                </CardContent>
            </Card>
        );
    }

    const Icon = getFileIcon(selectedFile.name);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-lg flex items-center gap-2">
                    <Icon className="h-5 w-5" />
                    Selected File
                </CardTitle>
                <CardDescription>
                    File ready for test generation
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="flex items-start justify-between">
                    <div className="flex-1 min-w-0">
                        <h3 className="font-medium text-gray-900 truncate">{selectedFile.name}</h3>
                        <p className="text-sm text-gray-500 truncate">{selectedFile.path}</p>
                    </div>
                    <div className="flex items-center gap-2 ml-4">
                        <Badge className={getFileTypeColor(selectedFile.name)}>
                            {selectedFile.name.split('.').pop()?.toUpperCase() || 'FILE'}
                        </Badge>
                        <a
                            href={selectedFile.html_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-gray-400 hover:text-gray-600"
                        >
                            <ExternalLink className="h-4 w-4" />
                        </a>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span className="text-gray-500">Size:</span>
                        <span className="ml-2 font-medium">{formatFileSize(selectedFile.size)}</span>
                    </div>
                    <div>
                        <span className="text-gray-500">Repository:</span>
                        <span className="ml-2 font-medium">{repository.full_name}</span>
                    </div>
                </div>

                {isLoadingContent && (
                    <div className="flex items-center justify-center py-4">
                        <Loader2 className="h-6 w-6 animate-spin mr-2" />
                        <span className="text-sm text-gray-600">Loading file content...</span>
                    </div>
                )}

                {error && (
                    <Alert>
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                {fileContent && !isLoadingContent && (
                    <div className="space-y-3">
                        <div className="flex items-center gap-2 text-sm text-green-600">
                            <CheckCircle className="h-4 w-4" />
                            <span>File content loaded successfully</span>
                        </div>
                        
                        <div className="bg-gray-50 rounded-md p-3">
                            <div className="text-xs text-gray-500 mb-2">Preview (first 200 characters):</div>
                            <code className="text-xs text-gray-700 block whitespace-pre-wrap">
                                {fileContent.content.substring(0, 200)}
                                {fileContent.content.length > 200 && '...'}
                            </code>
                        </div>

                        <Button 
                            onClick={onGenerateTests}
                            disabled={disabled || isGenerating}
                            className="w-full"
                        >
                            {isGenerating ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Generating Tests...
                                </>
                            ) : (
                                'Generate Tests for This File'
                            )}
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
