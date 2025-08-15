import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useToast } from '@/hooks/use-toast';
import {
    File,
    Code,
    FileText,
    ExternalLink,
    Download,
    Loader2,
    CheckCircle,
    Eye,
    Copy
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
    const [lastRequestTime, setLastRequestTime] = useState<number>(0);

    const { error: showError, warning: showWarning, success: showSuccess } = useToast();

    // Get file language for syntax highlighting
    const getFileLanguage = (filename: string): string => {
        const extension = filename.split('.').pop()?.toLowerCase();
        const languageMap: { [key: string]: string } = {
            'js': 'javascript',
            'jsx': 'javascript',
            'ts': 'typescript',
            'tsx': 'typescript',
            'py': 'python',
            'php': 'php',
            'java': 'java',
            'cpp': 'cpp',
            'c': 'c',
            'cs': 'csharp',
            'rb': 'ruby',
            'go': 'go',
            'rs': 'rust',
            'swift': 'swift',
            'kt': 'kotlin',
            'scala': 'scala',
            'html': 'html',
            'css': 'css',
            'scss': 'scss',
            'sass': 'sass',
            'less': 'less',
            'json': 'json',
            'xml': 'xml',
            'yaml': 'yaml',
            'yml': 'yaml',
            'md': 'markdown',
            'sql': 'sql',
            'sh': 'bash',
            'bash': 'bash',
            'zsh': 'bash',
            'fish': 'bash',
            'ps1': 'powershell',
            'dockerfile': 'dockerfile',
            'makefile': 'makefile',
            'r': 'r',
            'matlab': 'matlab',
            'm': 'matlab'
        };
        return languageMap[extension || ''] || 'text';
    };

    // Copy content to clipboard
    const copyToClipboard = async (content: string) => {
        try {
            await navigator.clipboard.writeText(content);
            showSuccess('File content copied to clipboard');
        } catch (err) {
            showError('Failed to copy content to clipboard');
        }
    };

    // Generate line numbers
    const generateLineNumbers = (content: string): string[] => {
        return content.split('\n').map((_, index) => (index + 1).toString());
    };

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
        // Debounce API calls to prevent rapid requests
        const now = Date.now();
        const timeSinceLastRequest = now - lastRequestTime;
        const minInterval = 500; // 500ms minimum between requests

        if (timeSinceLastRequest < minInterval) {
            return;
        }

        setLastRequestTime(now);

        setIsLoadingContent(true);
        setError(null);
        setFileContent(null);

        const requestPayload = {
            owner: repository.owner,
            repo: repository.repo,
            path: file.path,
            branch: branch,
        };

        try {
            const response = await fetch('/thinktest/github/file', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(requestPayload),
            });

            const result = await response.json();

            if (response.status === 429) {
                // Handle rate limiting
                const retryAfterSeconds = result.retry_after || 60;
                const errorMessage = result.message || `Rate limit exceeded. Please wait ${retryAfterSeconds} seconds before trying again.`;



                setError(errorMessage);
                onError(errorMessage);
                // Use showError with retryAfter to trigger specialized rate limit handling
                showError(errorMessage, { retryAfter: retryAfterSeconds });
                return;
            }

            if (result.success) {
                setFileContent(result.file);
                onFileContentLoaded(result.file);
                showSuccess(`File "${file.name}" loaded successfully`);
            } else {
                const errorMessage = result.message || 'Failed to fetch file content. Please check the file path and try again.';
                setError(errorMessage);
                onError(errorMessage);
                showError(errorMessage);
            }
        } catch (err) {
            const errorMessage = 'Network error occurred while fetching file content. Please check your internet connection and try again.';
            setError(errorMessage);
            onError(errorMessage);
            showError(errorMessage);
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
                    <div className="space-y-4">
                        <div className="flex items-center gap-2 text-sm text-green-600">
                            <CheckCircle className="h-4 w-4" />
                            <span>File content loaded successfully</span>
                        </div>

                        <Tabs defaultValue="preview" className="w-full">
                            <TabsList className="grid w-full grid-cols-2">
                                <TabsTrigger value="preview" className="flex items-center gap-2">
                                    <Eye className="h-4 w-4" />
                                    Preview
                                </TabsTrigger>
                                <TabsTrigger value="raw" className="flex items-center gap-2">
                                    <Code className="h-4 w-4" />
                                    Raw
                                </TabsTrigger>
                            </TabsList>

                            <TabsContent value="preview" className="mt-4">
                                <div className="border rounded-lg overflow-hidden bg-white">
                                    <div className="flex items-center justify-between px-3 py-2 bg-gray-50 border-b">
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs font-medium text-gray-600">
                                                {fileContent.name} ({getFileLanguage(fileContent.name)})
                                            </span>
                                            <Badge variant="outline" className="text-xs">
                                                {fileContent.content.split('\n').length} lines
                                            </Badge>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => copyToClipboard(fileContent.content)}
                                            className="h-6 px-2"
                                        >
                                            <Copy className="h-3 w-3" />
                                        </Button>
                                    </div>

                                    <div className="relative">
                                        <div className="flex max-h-96 overflow-hidden">
                                            {/* Line numbers */}
                                            <div className="bg-gray-50 dark:bg-gray-800 px-3 py-3 text-xs text-gray-500 dark:text-gray-400 font-mono line-numbers border-r border-gray-200 dark:border-gray-700 overflow-y-auto code-preview-container">
                                                {generateLineNumbers(fileContent.content).map((lineNum, index) => (
                                                    <div key={index} className="leading-5 text-right min-w-[2rem]">
                                                        {lineNum}
                                                    </div>
                                                ))}
                                            </div>

                                            {/* Code content */}
                                            <div className="flex-1 overflow-auto code-preview-container">
                                                <pre className="p-3 text-xs font-mono leading-5 code-content bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                                                    <code className={`language-${getFileLanguage(fileContent.name)}`}>
                                                        {fileContent.content}
                                                    </code>
                                                </pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </TabsContent>

                            <TabsContent value="raw" className="mt-4">
                                <div className="border rounded-lg overflow-hidden bg-white">
                                    <div className="flex items-center justify-between px-3 py-2 bg-gray-50 border-b">
                                        <span className="text-xs font-medium text-gray-600">
                                            Raw Content ({(new Blob([fileContent.content]).size / 1024).toFixed(1)} KB)
                                        </span>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => copyToClipboard(fileContent.content)}
                                            className="h-6 px-2"
                                        >
                                            <Copy className="h-3 w-3" />
                                        </Button>
                                    </div>

                                    <div className="max-h-96 overflow-auto p-3 code-preview-container bg-white dark:bg-gray-900">
                                        <textarea
                                            value={fileContent.content}
                                            readOnly
                                            className="w-full h-full min-h-[300px] text-xs font-mono bg-transparent border-none outline-none resize-none text-gray-900 dark:text-gray-100"
                                            style={{ scrollbarWidth: 'thin' }}
                                        />
                                    </div>
                                </div>
                            </TabsContent>
                        </Tabs>

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
