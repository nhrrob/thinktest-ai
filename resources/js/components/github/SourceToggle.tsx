import { GitBranch, Upload } from 'lucide-react';

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

            {/* Compact tab-style design */}
            <div className="inline-flex rounded-lg border border-input bg-background p-1 gap-2">
                <button
                    onClick={() => onSourceChange('file')}
                    disabled={disabled}
                    className={`
                        inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-all
                        ${selectedSource === 'file'
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                        }
                        ${disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}
                    `}
                >
                    <Upload className="h-4 w-4" />
                    Upload File
                </button>

                <button
                    onClick={() => onSourceChange('github')}
                    disabled={disabled}
                    className={`
                        inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-all
                        ${selectedSource === 'github'
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                        }
                        ${disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}
                    `}
                >
                    <GitBranch className="h-4 w-4" />
                    GitHub Repository
                </button>
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
