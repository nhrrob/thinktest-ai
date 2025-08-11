import { Button } from '@/components/ui/button';
import { Upload, Github } from 'lucide-react';

export type SourceType = 'file' | 'github';

interface SourceToggleProps {
    selectedSource: SourceType;
    onSourceChange: (source: SourceType) => void;
    disabled?: boolean;
}

export default function SourceToggle({ 
    selectedSource, 
    onSourceChange, 
    disabled = false 
}: SourceToggleProps) {
    return (
        <div className="space-y-3">
            <h3 className="text-lg font-medium text-gray-900">
                Choose Source
            </h3>
            <p className="text-sm text-gray-600">
                Select how you want to provide your WordPress plugin code for analysis.
            </p>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Button
                    variant={selectedSource === 'file' ? 'default' : 'outline'}
                    onClick={() => onSourceChange('file')}
                    disabled={disabled}
                    className="h-auto p-4 flex flex-col items-center space-y-2 text-left"
                >
                    <Upload className="h-8 w-8" />
                    <div>
                        <div className="font-medium">Upload File</div>
                        <div className="text-xs opacity-75">
                            Upload a .php file or .zip archive
                        </div>
                    </div>
                </Button>
                
                <Button
                    variant={selectedSource === 'github' ? 'default' : 'outline'}
                    onClick={() => onSourceChange('github')}
                    disabled={disabled}
                    className="h-auto p-4 flex flex-col items-center space-y-2 text-left"
                >
                    <Github className="h-8 w-8" />
                    <div>
                        <div className="font-medium">GitHub Repository</div>
                        <div className="text-xs opacity-75">
                            Connect a GitHub repository
                        </div>
                    </div>
                </Button>
            </div>
            
            <div className="text-xs text-gray-500 space-y-1">
                {selectedSource === 'file' && (
                    <div className="p-3 bg-blue-50 border border-blue-200 rounded-md">
                        <p className="font-medium text-blue-800 mb-1">File Upload</p>
                        <ul className="text-blue-700 space-y-1">
                            <li>• Supports .php files and .zip archives</li>
                            <li>• Maximum file size: 10MB</li>
                            <li>• ZIP files will be automatically extracted</li>
                        </ul>
                    </div>
                )}
                
                {selectedSource === 'github' && (
                    <div className="p-3 bg-green-50 border border-green-200 rounded-md">
                        <p className="font-medium text-green-800 mb-1">GitHub Repository</p>
                        <ul className="text-green-700 space-y-1">
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
