import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Calendar, Code, FileText, Shield, Target, Zap } from 'lucide-react';

interface Analysis {
    id: number;
    filename: string;
    file_hash: string;
    analysis_data: any;
    wordpress_patterns: any[];
    functions: any[];
    classes: any[];
    hooks: any[];
    filters: any[];
    security_patterns: any[];
    test_recommendations: any[];
    complexity_score: number;
    analyzed_at: string;
    created_at: string;
    updated_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
}

interface AnalysisDetailsProps {
    analysis: Analysis;
}

export default function AnalysisDetails({ analysis }: AnalysisDetailsProps) {
    const getComplexityLevel = (score: number) => {
        if (score <= 5) return { level: 'Low', color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' };
        if (score <= 10) return { level: 'Medium', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' };
        return { level: 'High', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' };
    };

    const complexity = getComplexityLevel(analysis.complexity_score || 0);

    return (
        <AppLayout>
            <Head title={`Analysis Details - ${analysis.filename}`} />

            <div className="container mx-auto px-4 py-8 max-w-4xl">
                {/* Header */}
                <div className="mb-6">
                    <div className="flex items-center gap-4 mb-4">
                        <Link href="/thinktest">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Back to ThinkTest AI
                            </Button>
                        </Link>
                    </div>
                    
                    <div className="flex items-start justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-foreground mb-2">
                                Analysis Details
                            </h1>
                            <p className="text-muted-foreground">
                                {analysis.filename}
                            </p>
                        </div>
                        <Badge className={complexity.color}>
                            {complexity.level} Complexity
                        </Badge>
                    </div>
                </div>

                <div className="grid gap-6">
                    {/* Overview Card */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Analysis Overview
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2 text-sm">
                                        <Calendar className="h-4 w-4 text-muted-foreground" />
                                        <span className="font-medium">Analyzed:</span>
                                        <span>{new Date(analysis.analyzed_at).toLocaleString()}</span>
                                    </div>
                                    <div className="flex items-center gap-2 text-sm">
                                        <Zap className="h-4 w-4 text-muted-foreground" />
                                        <span className="font-medium">Complexity Score:</span>
                                        <span>{analysis.complexity_score || 'N/A'}</span>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2 text-sm">
                                        <Code className="h-4 w-4 text-muted-foreground" />
                                        <span className="font-medium">Functions:</span>
                                        <span>{analysis.functions?.length || 0}</span>
                                    </div>
                                    <div className="flex items-center gap-2 text-sm">
                                        <Target className="h-4 w-4 text-muted-foreground" />
                                        <span className="font-medium">Classes:</span>
                                        <span>{analysis.classes?.length || 0}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* WordPress Patterns */}
                    {analysis.wordpress_patterns && analysis.wordpress_patterns.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>WordPress Patterns</CardTitle>
                                <CardDescription>
                                    WordPress-specific patterns detected in the code
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-2">
                                    {analysis.wordpress_patterns.map((pattern, index) => (
                                        <div key={index} className="flex items-center gap-2 p-2 bg-muted rounded">
                                            <Badge variant="outline">{pattern.type || 'Pattern'}</Badge>
                                            <span className="text-sm">{pattern.name || pattern}</span>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Functions */}
                    {analysis.functions && analysis.functions.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Functions</CardTitle>
                                <CardDescription>
                                    Functions detected in the code
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-2">
                                    {analysis.functions.slice(0, 10).map((func, index) => (
                                        <div key={index} className="flex items-center gap-2 p-2 bg-muted rounded">
                                            <Code className="h-4 w-4 text-muted-foreground" />
                                            <span className="text-sm font-mono">{func.name || func}</span>
                                            {func.visibility && (
                                                <Badge variant="outline" className="text-xs">
                                                    {func.visibility}
                                                </Badge>
                                            )}
                                        </div>
                                    ))}
                                    {analysis.functions.length > 10 && (
                                        <p className="text-sm text-muted-foreground">
                                            ... and {analysis.functions.length - 10} more functions
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Classes */}
                    {analysis.classes && analysis.classes.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Classes</CardTitle>
                                <CardDescription>
                                    Classes detected in the code
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-2">
                                    {analysis.classes.map((cls, index) => (
                                        <div key={index} className="flex items-center gap-2 p-2 bg-muted rounded">
                                            <Target className="h-4 w-4 text-muted-foreground" />
                                            <span className="text-sm font-mono">{cls.name || cls}</span>
                                            {cls.extends && (
                                                <Badge variant="outline" className="text-xs">
                                                    extends {cls.extends}
                                                </Badge>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Hooks and Filters */}
                    {((analysis.hooks && analysis.hooks.length > 0) || (analysis.filters && analysis.filters.length > 0)) && (
                        <Card>
                            <CardHeader>
                                <CardTitle>WordPress Hooks & Filters</CardTitle>
                                <CardDescription>
                                    WordPress hooks and filters used in the code
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {analysis.hooks && analysis.hooks.length > 0 && (
                                    <div>
                                        <h4 className="font-medium mb-2">Hooks</h4>
                                        <div className="grid gap-2">
                                            {analysis.hooks.map((hook, index) => (
                                                <div key={index} className="flex items-center gap-2 p-2 bg-muted rounded">
                                                    <Badge variant="outline" className="text-xs">Hook</Badge>
                                                    <span className="text-sm font-mono">{hook.name || hook}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                                
                                {analysis.filters && analysis.filters.length > 0 && (
                                    <div>
                                        <h4 className="font-medium mb-2">Filters</h4>
                                        <div className="grid gap-2">
                                            {analysis.filters.map((filter, index) => (
                                                <div key={index} className="flex items-center gap-2 p-2 bg-muted rounded">
                                                    <Badge variant="outline" className="text-xs">Filter</Badge>
                                                    <span className="text-sm font-mono">{filter.name || filter}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {/* Security Patterns */}
                    {analysis.security_patterns && analysis.security_patterns.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Shield className="h-5 w-5" />
                                    Security Patterns
                                </CardTitle>
                                <CardDescription>
                                    Security-related patterns detected in the code
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-2">
                                    {analysis.security_patterns.map((pattern, index) => (
                                        <div key={index} className="flex items-center gap-2 p-2 bg-muted rounded">
                                            <Shield className="h-4 w-4 text-muted-foreground" />
                                            <span className="text-sm">{pattern.type || pattern}</span>
                                            {pattern.severity && (
                                                <Badge 
                                                    variant="outline" 
                                                    className={`text-xs ${
                                                        pattern.severity === 'high' ? 'border-red-500 text-red-700' :
                                                        pattern.severity === 'medium' ? 'border-yellow-500 text-yellow-700' :
                                                        'border-green-500 text-green-700'
                                                    }`}
                                                >
                                                    {pattern.severity}
                                                </Badge>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Test Recommendations */}
                    {analysis.test_recommendations && analysis.test_recommendations.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Test Recommendations</CardTitle>
                                <CardDescription>
                                    AI-generated recommendations for testing this code
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {analysis.test_recommendations.map((recommendation, index) => (
                                        <div key={index} className="p-3 bg-muted rounded-lg">
                                            <div className="text-sm">
                                                {typeof recommendation === 'string' 
                                                    ? recommendation 
                                                    : recommendation.description || JSON.stringify(recommendation)
                                                }
                                            </div>
                                            {recommendation.priority && (
                                                <Badge variant="outline" className="mt-2 text-xs">
                                                    {recommendation.priority} priority
                                                </Badge>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Raw Analysis Data */}
                    {analysis.analysis_data && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Raw Analysis Data</CardTitle>
                                <CardDescription>
                                    Complete analysis data in JSON format
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <pre className="bg-muted p-4 rounded-lg text-sm overflow-auto max-h-96">
                                    {JSON.stringify(analysis.analysis_data, null, 2)}
                                </pre>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
