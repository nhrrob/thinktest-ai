import { Button } from '@/components/ui/button';
import { Github, Upload } from 'lucide-react';

export type SourceType = 'file' | 'github';

interface SourceToggleProps {
    selectedSource: SourceType;
    onSourceChange: (source: SourceType) => void;
    disabled?: boolean;
}

export default function SourceToggle({ selectedSource, onSourceChange, disabled = false }: SourceToggleProps) {
    return (
        <div className="space-y-3">
            <h3 className="text-lg font-medium text-foreground">Choose Source</h3>
            <p className="text-sm text-muted-foreground">Select how you want to provide your WordPress plugin code for analysis.</p>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Button
                    variant={selectedSource === 'file' ? 'default' : 'outline'}
                    onClick={() => onSourceChange('file')}
                    disabled={disabled}
                    className="flex h-auto flex-col items-center space-y-2 p-4 text-left"
                >
                    <Upload className="h-8 w-8" />
                    <div>
                        <div className="font-medium">Upload File</div>
                        <div className="text-xs opacity-75">Upload a .php file or .zip archive</div>
                    </div>
                </Button>

                <Button
                    variant={selectedSource === 'github' ? 'default' : 'outline'}
                    onClick={() => onSourceChange('github')}
                    disabled={disabled}
                    className="flex h-auto flex-col items-center space-y-2 p-4 text-left"
                >
                    <Github className="h-8 w-8" />
                    <div>
                        <div className="font-medium">GitHub Repository</div>
                        <div className="text-xs opacity-75">Connect a GitHub repository</div>
                    </div>
                </Button>
            </div>

            <div className="space-y-1 text-xs text-muted-foreground">
                {selectedSource === 'file' && (
                    <div className="rounded-md border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-950">
                        <p className="mb-1 font-medium text-blue-800 dark:text-blue-200">File Upload</p>
                        <ul className="space-y-1 text-blue-700 dark:text-blue-300">
                            <li>• Supports .php files and .zip archives</li>
                            <li>• Maximum file size: 10MB</li>
                            <li>• ZIP files will be automatically extracted</li>
                        </ul>
                    </div>
                )}

                {selectedSource === 'github' && (
                    <div className="rounded-md border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950">
                        <p className="mb-1 font-medium text-green-800 dark:text-green-200">GitHub Repository</p>
                        <ul className="space-y-1 text-green-700 dark:text-green-300">
                            <li>• Supports public and private repositories</li>
                            <li>• Automatically detects WordPress plugin structure</li>
                            <li>• Select any branch for analysis</li>
                            <li>• Maximum repository size: 50MB</li>
                        </ul>
                    </div>
                )}
            </div>
        </div>
    );
}
