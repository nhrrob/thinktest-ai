import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Code, Zap, Shield, Download, Upload, Cpu, CheckCircle } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import AppLogoIcon from '@/components/app-logo-icon';
import AppearanceToggleDropdown from '@/components/appearance-dropdown';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="ThinkTest AI - Smarter Unit Tests for WordPress Plugins">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>
            <div className="min-h-screen bg-background">
                {/* Header */}
                <header className="border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                    <div className="container flex h-16 items-center justify-between">
                        <div className="flex items-center space-x-2">
                            <AppLogo variant="header" iconSize="md" showText={true} />
                        </div>
                        <nav className="flex items-center gap-4">
                            <AppearanceToggleDropdown />
                            {auth.user ? (
                                <>
                                    <Link href={route('thinktest.index')}>
                                        <Button variant="ghost">Dashboard</Button>
                                    </Link>
                                    <Link href={route('dashboard')}>
                                        <Button variant="outline">Settings</Button>
                                    </Link>
                                </>
                            ) : (
                                <>
                                    <Link href={route('login')}>
                                        <Button variant="ghost">Log in</Button>
                                    </Link>
                                    <Link href={route('register')}>
                                        <Button>Get Started</Button>
                                    </Link>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                {/* Hero Section */}
                <section className="py-20 lg:py-32">
                    <div className="container">
                        <div className="mx-auto max-w-4xl text-center">
                            <Badge variant="secondary" className="mb-4">
                                AI-Powered Testing
                            </Badge>
                            <h1 className="mb-6 text-4xl font-bold tracking-tight lg:text-6xl">
                                Smarter Unit Tests for{' '}
                                <span className="text-primary">WordPress Plugins</span>
                            </h1>
                            <p className="mb-8 text-xl text-muted-foreground lg:text-2xl">
                                Generate comprehensive, intelligent test suites for your WordPress plugins using advanced AI. 
                                Support for PHPUnit and Pest frameworks with OpenAI and Anthropic integration.
                            </p>
                            <div className="flex flex-col gap-4 sm:flex-row sm:justify-center">
                                {auth.user ? (
                                    <Link href={route('thinktest.index')}>
                                        <Button size="lg" className="w-full sm:w-auto">
                                            <Code className="mr-2 size-4" />
                                            Start Testing
                                        </Button>
                                    </Link>
                                ) : (
                                    <Link href={route('register')}>
                                        <Button size="lg" className="w-full sm:w-auto">
                                            <Code className="mr-2 size-4" />
                                            Get Started Free
                                        </Button>
                                    </Link>
                                )}
                                <Button variant="outline" size="lg" className="w-full sm:w-auto">
                                    <Code className="mr-2 size-4" />
                                    View Demo
                                </Button>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Features Section */}
                <section className="py-20 bg-muted/50">
                    <div className="container">
                        <div className="mx-auto max-w-2xl text-center mb-16">
                            <h2 className="mb-4 text-3xl font-bold lg:text-4xl">
                                Powerful AI-Driven Features
                            </h2>
                            <p className="text-lg text-muted-foreground">
                                Everything you need to generate comprehensive test suites for your WordPress plugins
                            </p>
                        </div>
                        <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                            <Card>
                                <CardHeader>
                                    <Code className="size-10 text-primary mb-2" />
                                    <CardTitle>AI-Powered Analysis</CardTitle>
                                    <CardDescription>
                                        Advanced code analysis using OpenAI GPT-4 and Anthropic Claude to understand your plugin structure
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <Code className="size-10 text-primary mb-2" />
                                    <CardTitle>WordPress Expertise</CardTitle>
                                    <CardDescription>
                                        Specialized knowledge of WordPress hooks, filters, actions, and plugin patterns for accurate testing
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <Zap className="size-10 text-primary mb-2" />
                                    <CardTitle>Multiple Frameworks</CardTitle>
                                    <CardDescription>
                                        Support for both PHPUnit and Pest testing frameworks with proper WordPress integration
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <Shield className="size-10 text-primary mb-2" />
                                    <CardTitle>Security Testing</CardTitle>
                                    <CardDescription>
                                        Automatic generation of security tests for input sanitization and WordPress security best practices
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <Upload className="size-10 text-primary mb-2" />
                                    <CardTitle>Easy Upload</CardTitle>
                                    <CardDescription>
                                        Simple drag-and-drop interface for uploading your WordPress plugin files (up to 10MB)
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <Download className="size-10 text-primary mb-2" />
                                    <CardTitle>Instant Download</CardTitle>
                                    <CardDescription>
                                        Download your generated test files immediately, ready to integrate into your development workflow
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                        </div>
                    </div>
                </section>

                {/* AI Providers Section */}
                <section className="py-20">
                    <div className="container">
                        <div className="mx-auto max-w-2xl text-center mb-16">
                            <h2 className="mb-4 text-3xl font-bold lg:text-4xl">
                                Powered by Leading AI Models
                            </h2>
                            <p className="text-lg text-muted-foreground">
                                Choose from the best AI providers for your testing needs
                            </p>
                        </div>
                        <div className="grid gap-8 md:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <div className="flex items-center gap-3">
                                        <Cpu className="size-8 text-primary" />
                                        <div>
                                            <CardTitle>OpenAI GPT-4</CardTitle>
                                            <Badge variant="secondary">Default Provider</Badge>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-muted-foreground mb-4">
                                        Advanced language model with deep understanding of code patterns and WordPress development
                                    </p>
                                    <ul className="space-y-2">
                                        <li className="flex items-center gap-2">
                                            <CheckCircle className="size-4 text-green-500" />
                                            <span className="text-sm">4,000 token context</span>
                                        </li>
                                        <li className="flex items-center gap-2">
                                            <CheckCircle className="size-4 text-green-500" />
                                            <span className="text-sm">WordPress expertise</span>
                                        </li>
                                        <li className="flex items-center gap-2">
                                            <CheckCircle className="size-4 text-green-500" />
                                            <span className="text-sm">Fast response times</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <div className="flex items-center gap-3">
                                        <Cpu className="size-8 text-primary" />
                                        <div>
                                            <CardTitle>Anthropic Claude</CardTitle>
                                            <Badge variant="outline">Fallback Provider</Badge>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-muted-foreground mb-4">
                                        Constitutional AI with excellent reasoning capabilities for complex plugin analysis
                                    </p>
                                    <ul className="space-y-2">
                                        <li className="flex items-center gap-2">
                                            <CheckCircle className="size-4 text-green-500" />
                                            <span className="text-sm">Claude-3 Sonnet model</span>
                                        </li>
                                        <li className="flex items-center gap-2">
                                            <CheckCircle className="size-4 text-green-500" />
                                            <span className="text-sm">Superior code analysis</span>
                                        </li>
                                        <li className="flex items-center gap-2">
                                            <CheckCircle className="size-4 text-green-500" />
                                            <span className="text-sm">Reliable fallback</span>
                                        </li>
                                    </ul>
                                </CardContent>
            </Card>
        </div>
    </div>
</section>

                {/* How It Works Section */}
                <section className="py-20 bg-muted/50">
                    <div className="container">
                        <div className="mx-auto max-w-2xl text-center mb-16">
                            <h2 className="mb-4 text-3xl font-bold lg:text-4xl">
                                How It Works
                            </h2>
                            <p className="text-lg text-muted-foreground">
                                Generate comprehensive test suites in three simple steps
                            </p>
                        </div>
                        <div className="grid gap-8 md:grid-cols-3">
                            <div className="text-center">
                                <div className="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                    <Upload className="size-8" />
                                </div>
                                <h3 className="mb-2 text-xl font-semibold">1. Upload Plugin</h3>
                                <p className="text-muted-foreground">
                                    Upload your WordPress plugin file (PHP, JS, CSS, JSON supported)
                                </p>
                            </div>
                            <div className="text-center">
                                <div className="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                    <Code className="size-8" />
                                </div>
                                <h3 className="mb-2 text-xl font-semibold">2. AI Analysis</h3>
                                <p className="text-muted-foreground">
                                    Our AI analyzes your code structure, hooks, and WordPress patterns
                                </p>
                            </div>
                            <div className="text-center">
                                <div className="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                    <Download className="size-8" />
                                </div>
                                <h3 className="mb-2 text-xl font-semibold">3. Download Tests</h3>
                                <p className="text-muted-foreground">
                                    Get comprehensive test files ready for your development workflow
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="py-20">
                    <div className="container">
                        <div className="mx-auto max-w-2xl text-center">
                            <h2 className="mb-4 text-3xl font-bold lg:text-4xl">
                                Ready to Improve Your Testing?
                            </h2>
                            <p className="mb-8 text-lg text-muted-foreground">
                                Join developers who are already using ThinkTest AI to create better, more reliable WordPress plugins.
                            </p>
                            <div className="flex flex-col gap-4 sm:flex-row sm:justify-center">
                                {auth.user ? (
                                    <Link href={route('thinktest.index')}>
                                        <Button size="lg" className="w-full sm:w-auto">
                                            <Code className="mr-2 size-4" />
                                            Start Testing Now
                                        </Button>
                                    </Link>
                                ) : (
                                    <Link href={route('register')}>
                                        <Button size="lg" className="w-full sm:w-auto">
                                            <Code className="mr-2 size-4" />
                                            Get Started Free
                                        </Button>
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="border-t py-12">
                    <div className="container">
                        <div className="flex flex-col items-center justify-between gap-4 md:flex-row">
                            <div className="flex items-center space-x-2">
                                <div className="flex aspect-square size-6 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                    <AppLogoIcon className="size-4" />
                                </div>
                                <span className="font-bold">ThinkTest AI</span>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                © 2024 ThinkTest AI. Smarter Unit Tests for WordPress Plugins.
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
