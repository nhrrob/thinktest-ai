<?php

namespace App\Http\Controllers;

use App\Models\AIConversationState;
use App\Models\PluginAnalysisResult;
use App\Models\GitHubRepository;
use App\Services\AI\AIProviderService;
use App\Services\WordPress\PluginAnalysisService;
use App\Services\WordPress\TestInfrastructureDetectionService;
use App\Services\WordPress\TestSetupInstructionsService;
use App\Services\WordPress\TestConfigurationTemplateService;
use App\Services\FileProcessing\FileProcessingService;
use App\Services\GitHub\GitHubService;
use App\Services\GitHub\GitHubRepositoryService;
use App\Services\GitHub\GitHubValidationService;
use App\Services\GitHub\GitHubErrorHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ThinkTestController extends Controller
{
    private AIProviderService $aiService;
    private PluginAnalysisService $analysisService;
    private TestInfrastructureDetectionService $testDetectionService;
    private TestSetupInstructionsService $testInstructionsService;
    private TestConfigurationTemplateService $testTemplateService;
    private FileProcessingService $fileService;
    private GitHubService $githubService;
    private GitHubRepositoryService $githubRepositoryService;
    private GitHubValidationService $githubValidationService;

    public function __construct(
        AIProviderService $aiService,
        PluginAnalysisService $analysisService,
        TestInfrastructureDetectionService $testDetectionService,
        TestSetupInstructionsService $testInstructionsService,
        TestConfigurationTemplateService $testTemplateService,
        FileProcessingService $fileService,
        GitHubService $githubService,
        GitHubRepositoryService $githubRepositoryService,
        GitHubValidationService $githubValidationService
    ) {
        $this->aiService = $aiService;
        $this->analysisService = $analysisService;
        $this->testDetectionService = $testDetectionService;
        $this->testInstructionsService = $testInstructionsService;
        $this->testTemplateService = $testTemplateService;
        $this->fileService = $fileService;
        $this->githubService = $githubService;
        $this->githubRepositoryService = $githubRepositoryService;
        $this->githubValidationService = $githubValidationService;

        // Apply permission-based middleware for ThinkTest AI functionality
        $this->middleware('permission:generate tests|limited test generation')->only(['index', 'generateTests']);
        $this->middleware('permission:upload files')->only(['upload']);
        $this->middleware('permission:download test results')->only(['downloadTests']);
    }

    /**
     * Show the main ThinkTest AI interface
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get recent conversations
        $recentConversations = AIConversationState::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get recent analysis results
        $recentAnalyses = PluginAnalysisResult::where('user_id', $user->id)
            ->orderBy('analyzed_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('ThinkTest/Index', [
            'recentConversations' => $recentConversations,
            'recentAnalyses' => $recentAnalyses,
            'availableProviders' => $this->aiService->getAvailableProviders(),
        ]);
    }

    /**
     * Upload and analyze WordPress plugin file
     */
    public function upload(Request $request)
    {
        $request->validate([
            'plugin_file' => 'required|file|max:10240', // 10MB max
            'provider' => 'sometimes|string|in:chatgpt-5,anthropic',
            'framework' => 'sometimes|string|in:phpunit,pest',
        ]);

        try {
            $user = Auth::user();
            
            // Process uploaded file
            $fileData = $this->fileService->processUploadedFile(
                $request->file('plugin_file'),
                $user->id
            );

            // Analyze plugin code
            $analysis = $this->analysisService->analyzePlugin(
                $fileData['content'],
                $fileData['filename']
            );

            // Store analysis result
            $analysisResult = PluginAnalysisResult::create([
                'user_id' => $user->id,
                'filename' => $fileData['filename'],
                'file_hash' => $fileData['file_hash'],
                'analysis_data' => $analysis,
                'wordpress_patterns' => $analysis['wordpress_patterns'],
                'functions' => $analysis['functions'],
                'classes' => $analysis['classes'],
                'hooks' => $analysis['hooks'],
                'filters' => $analysis['filters'],
                'security_patterns' => $analysis['security_patterns'],
                'test_recommendations' => $analysis['test_recommendations'],
                'complexity_score' => $this->calculateComplexityScore($analysis),
                'analyzed_at' => now(),
            ]);

            // Create AI conversation
            $conversation = AIConversationState::create([
                'user_id' => $user->id,
                'conversation_id' => Str::uuid(),
                'provider' => $request->input('provider', 'openai'),
                'status' => 'active',
                'context' => [
                    'filename' => $fileData['filename'],
                    'framework' => $request->input('framework', 'phpunit'),
                    'analysis_id' => $analysisResult->id,
                ],
                'plugin_file_path' => $fileData['stored_path'],
                'plugin_file_hash' => $fileData['file_hash'],
                'step' => 1,
                'total_steps' => 3,
                'started_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Plugin uploaded and analyzed successfully',
                'conversation_id' => $conversation->conversation_id,
                'analysis' => $analysis,
                'analysis_id' => $analysisResult->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Plugin upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'file' => $request->file('plugin_file')?->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate tests using AI
     */
    public function generateTests(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|string',
            'provider' => 'sometimes|string|in:chatgpt-5,anthropic',
            'framework' => 'sometimes|string|in:phpunit,pest',
        ]);

        try {
            $user = Auth::user();
            
            // Find conversation
            $conversation = AIConversationState::where('conversation_id', $request->conversation_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            if ($conversation->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation is not active',
                ], 422);
            }

            // Get plugin content
            $pluginContent = $this->fileService->getFileContent($conversation->plugin_file_path);

            // Generate tests using AI
            $aiOptions = [
                'provider' => $request->input('provider', $conversation->provider),
                'framework' => $request->input('framework', $conversation->context['framework'] ?? 'phpunit'),
                'test_type' => 'unit',
            ];

            $aiResult = $this->aiService->generateWordPressTests($pluginContent, $aiOptions);

            // Update conversation
            $conversation->update([
                'generated_tests' => $aiResult['generated_tests'],
                'step' => 2,
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $conversation->addMessage([
                'role' => 'assistant',
                'content' => $aiResult['generated_tests'],
                'provider' => $aiResult['provider'],
                'model' => $aiResult['model'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tests generated successfully',
                'tests' => $aiResult['generated_tests'],
                'provider' => $aiResult['provider'],
                'conversation_id' => $conversation->conversation_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Test generation failed', [
                'user_id' => Auth::id(),
                'conversation_id' => $request->conversation_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download generated tests
     */
    public function downloadTests(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|string',
        ]);

        try {
            $user = Auth::user();
            
            // Find conversation
            $conversation = AIConversationState::where('conversation_id', $request->conversation_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            if (empty($conversation->generated_tests)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tests available for download',
                ], 404);
            }

            // Create downloadable file
            $filename = 'thinktest_' . $conversation->context['filename'] . '_tests.php';
            $content = $conversation->generated_tests;

            return response($content)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            Log::error('Test download failed', [
                'user_id' => Auth::id(),
                'conversation_id' => $request->conversation_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get conversation status
     */
    public function getConversationStatus(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|string',
        ]);

        $user = Auth::user();
        
        $conversation = AIConversationState::where('conversation_id', $request->conversation_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'conversation_id' => $conversation->conversation_id,
            'status' => $conversation->status,
            'step' => $conversation->step,
            'total_steps' => $conversation->total_steps,
            'progress' => $conversation->getProgressPercentage(),
            'has_tests' => !empty($conversation->generated_tests),
            'provider' => $conversation->provider,
            'context' => $conversation->context,
        ]);
    }

    /**
     * Validate GitHub repository URL
     */
    public function validateRepository(Request $request)
    {
        $request->validate([
            'repository_url' => 'required|string|max:500',
        ]);

        try {
            $user = Auth::user();

            // Use validation service with comprehensive security checks
            $repoData = $this->githubValidationService->validateRepositoryUrl(
                $request->repository_url,
                $user->id
            );

            // Check if repository is accessible
            if (!$this->githubService->isRepositoryAccessible($repoData['owner'], $repoData['repo'])) {
                $this->githubValidationService->logSecurityEvent('Repository access denied', [
                    'user_id' => $user->id,
                    'repository_url' => $request->repository_url,
                    'owner' => $repoData['owner'],
                    'repo' => $repoData['repo'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Repository not found or not accessible. Please check the URL and ensure the repository is public or you have access.',
                ], 404);
            }

            // Get repository information
            $repoInfo = $this->githubService->getRepositoryInfo($repoData['owner'], $repoData['repo']);

            // Validate repository size
            $this->githubValidationService->validateRepositorySize($repoInfo['size']);

            Log::info('Repository validated successfully', [
                'user_id' => $user->id,
                'repository' => $repoData['full_name'],
                'size' => $repoInfo['size'],
                'private' => $repoInfo['private'],
            ]);

            return response()->json([
                'success' => true,
                'repository' => array_merge($repoData, $repoInfo),
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->githubValidationService->logSecurityEvent('Invalid repository URL', [
                'user_id' => Auth::id(),
                'repository_url' => $request->repository_url,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            // Rate limiting or size validation errors
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 429);
        } catch (\Exception $e) {
            $errorInfo = GitHubErrorHandler::handleException($e, [
                'user_id' => Auth::id(),
                'repository_url' => $request->repository_url,
                'action' => 'validate_repository',
            ]);

            return response()->json([
                'success' => false,
                'message' => $errorInfo['user_message'],
                'error_code' => $errorInfo['error_code'],
                'retry_possible' => $errorInfo['retry_possible'] ?? false,
                'retry_after' => $errorInfo['retry_after'] ?? null,
            ], $errorInfo['http_status'] ?? 500);
        }
    }

    /**
     * Get repository branches
     */
    public function getRepositoryBranches(Request $request)
    {
        $request->validate([
            'owner' => 'required|string|max:100|regex:/^[a-zA-Z0-9\-_\.]+$/',
            'repo' => 'required|string|max:100|regex:/^[a-zA-Z0-9\-_\.]+$/',
        ]);

        try {
            $user = Auth::user();

            // Rate limiting check
            $this->githubValidationService->validateRateLimit($user->id);

            // Validate repository components
            $repoData = [
                'owner' => $request->owner,
                'repo' => $request->repo,
                'full_name' => "{$request->owner}/{$request->repo}",
                'url' => "https://github.com/{$request->owner}/{$request->repo}",
            ];
            $this->githubValidationService->validateRepositoryComponents($repoData);

            $branches = $this->githubService->getRepositoryBranches($request->owner, $request->repo);

            // Validate branch names
            foreach ($branches as $branch) {
                $this->githubValidationService->validateBranchName($branch['name']);
            }

            Log::info('Repository branches fetched successfully', [
                'user_id' => $user->id,
                'repository' => "{$request->owner}/{$request->repo}",
                'branch_count' => count($branches),
            ]);

            return response()->json([
                'success' => true,
                'branches' => $branches,
            ]);

        } catch (\RuntimeException $e) {
            // Rate limiting errors
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 429);
        } catch (\InvalidArgumentException $e) {
            $this->githubValidationService->logSecurityEvent('Invalid repository components', [
                'user_id' => Auth::id(),
                'owner' => $request->owner,
                'repo' => $request->repo,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to fetch repository branches', [
                'user_id' => Auth::id(),
                'owner' => $request->owner,
                'repo' => $request->repo,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branches: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process GitHub repository
     */
    public function processRepository(Request $request)
    {
        $request->validate([
            'owner' => 'required|string|max:100|regex:/^[a-zA-Z0-9\-_\.]+$/',
            'repo' => 'required|string|max:100|regex:/^[a-zA-Z0-9\-_\.]+$/',
            'branch' => 'required|string|max:250|regex:/^[a-zA-Z0-9\-_\.\/]+$/',
            'provider' => 'sometimes|string|in:chatgpt-5,anthropic',
            'framework' => 'sometimes|string|in:phpunit,pest',
        ]);

        try {
            $user = Auth::user();

            // Comprehensive validation
            $this->githubValidationService->validateRateLimit($user->id);

            // Validate repository components
            $repoData = [
                'owner' => $request->owner,
                'repo' => $request->repo,
                'full_name' => "{$request->owner}/{$request->repo}",
                'url' => "https://github.com/{$request->owner}/{$request->repo}",
            ];
            $this->githubValidationService->validateRepositoryComponents($repoData);

            // Validate branch name
            $this->githubValidationService->validateBranchName($request->branch);

            // Check if repository record already exists
            $githubRepo = GitHubRepository::where('user_id', $user->id)
                ->where('full_name', "{$request->owner}/{$request->repo}")
                ->where('branch', $request->branch)
                ->first();

            if (!$githubRepo) {
                // Create new repository record
                $repoInfo = $this->githubService->getRepositoryInfo($request->owner, $request->repo);

                $githubRepo = GitHubRepository::create([
                    'user_id' => $user->id,
                    'owner' => $request->owner,
                    'repo' => $request->repo,
                    'full_name' => "{$request->owner}/{$request->repo}",
                    'branch' => $request->branch,
                    'github_id' => $repoInfo['id'],
                    'description' => $repoInfo['description'],
                    'is_private' => $repoInfo['private'],
                    'default_branch' => $repoInfo['default_branch'],
                    'size_bytes' => $repoInfo['size'],
                    'language' => $repoInfo['language'],
                    'clone_url' => $repoInfo['clone_url'],
                    'html_url' => $repoInfo['html_url'],
                    'last_updated_at' => $repoInfo['updated_at'],
                    'processing_status' => 'processing',
                ]);
            } else {
                $githubRepo->markAsProcessing();
            }

            // Process repository
            $processedData = $this->githubRepositoryService->processRepository(
                $request->owner,
                $request->repo,
                $request->branch,
                $user->id
            );

            // Validate processed data
            $this->githubValidationService->validateFileCount($processedData['file_count']);
            $processedData['content'] = $this->githubValidationService->sanitizeFileContent($processedData['content']);

            // Update repository record
            $githubRepo->markAsCompleted(
                $processedData['plugin_structure'],
                $processedData['file_count']
            );

            // Analyze plugin code
            $analysis = $this->analysisService->analyzePlugin(
                $processedData['content'],
                $processedData['filename']
            );

            // Store analysis result
            $analysisResult = PluginAnalysisResult::create([
                'user_id' => $user->id,
                'filename' => $processedData['filename'],
                'file_hash' => $processedData['file_hash'],
                'analysis_data' => $analysis,
                'complexity_score' => $this->calculateComplexityScore($analysis),
                'analyzed_at' => now(),
            ]);

            // Create AI conversation
            $conversation = AIConversationState::create([
                'user_id' => $user->id,
                'conversation_id' => Str::uuid(),
                'provider' => $request->input('provider', 'openai'),
                'status' => 'active',
                'context' => [
                    'filename' => $processedData['filename'],
                    'framework' => $request->input('framework', 'phpunit'),
                    'analysis_id' => $analysisResult->id,
                    'repository_info' => $processedData['repository_info'],
                    'branch' => $processedData['branch'],
                ],
                'plugin_file_path' => $processedData['stored_path'],
                'plugin_file_hash' => $processedData['file_hash'],
                'github_repository_id' => $githubRepo->id,
                'source_type' => 'github',
                'step' => 1,
                'total_steps' => 3,
                'started_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Repository processed successfully',
                'conversation_id' => $conversation->conversation_id,
                'analysis' => $analysis,
                'analysis_id' => $analysisResult->id,
                'repository' => [
                    'id' => $githubRepo->id,
                    'full_name' => $githubRepo->full_name,
                    'branch' => $githubRepo->branch,
                    'file_count' => $processedData['file_count'],
                    'plugin_structure' => $processedData['plugin_structure'],
                ],
            ]);

        } catch (\InvalidArgumentException $e) {
            // Validation errors
            $this->githubValidationService->logSecurityEvent('Repository processing validation failed', [
                'user_id' => Auth::id(),
                'owner' => $request->owner,
                'repo' => $request->repo,
                'branch' => $request->branch,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            // Rate limiting or size validation errors
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 429);
        } catch (\Exception $e) {
            // Mark repository as failed if it exists
            if (isset($githubRepo)) {
                $githubRepo->markAsFailed($e->getMessage());
            }

            Log::error('Repository processing failed', [
                'user_id' => Auth::id(),
                'owner' => $request->owner,
                'repo' => $request->repo,
                'branch' => $request->branch,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Repository processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate complexity score based on analysis
     */
    private function calculateComplexityScore(array $analysis): int
    {
        $score = 0;
        
        // Add points for various complexity factors
        $score += count($analysis['functions']) * 1;
        $score += count($analysis['classes']) * 2;
        $score += count($analysis['hooks']) * 1;
        $score += count($analysis['filters']) * 1;
        $score += count($analysis['ajax_handlers']) * 2;
        $score += count($analysis['rest_endpoints']) * 2;
        $score += count($analysis['database_operations']) * 1;
        
        return min($score, 100); // Cap at 100
    }

    /**
     * Detect test infrastructure for uploaded plugin
     */
    public function detectTestInfrastructure(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|string',
            'framework' => 'sometimes|string|in:phpunit,pest',
        ]);

        try {
            $conversationId = $request->input('conversation_id');
            $framework = $request->input('framework', 'phpunit');

            // Get conversation state
            $conversation = AIConversationState::where('conversation_id', $conversationId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            $pluginData = json_decode($conversation->plugin_data, true);

            // Detect missing test infrastructure
            $detection = $this->testDetectionService->detectMissingInfrastructure(
                $pluginData['content'],
                $pluginData['filename'],
                $pluginData['additional_files'] ?? []
            );

            // Generate setup instructions
            $instructions = $this->testInstructionsService->generateInstructions($detection, [
                'framework' => $framework,
                'plugin_name' => $pluginData['plugin_name'] ?? 'WordPress Plugin'
            ]);

            return response()->json([
                'success' => true,
                'detection' => $detection,
                'instructions' => $instructions,
            ]);

        } catch (\Exception $e) {
            Log::error('Test infrastructure detection failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'conversation_id' => $request->input('conversation_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Detection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download configuration template
     */
    public function downloadTemplate(Request $request)
    {
        $request->validate([
            'template' => 'required|string|in:phpunit_config,pest_config,composer_json,bootstrap,sample_test',
            'framework' => 'sometimes|string|in:phpunit,pest',
            'plugin_name' => 'sometimes|string|max:255',
        ]);

        try {
            $template = $request->input('template');
            $framework = $request->input('framework', 'phpunit');
            $pluginName = $request->input('plugin_name', 'WordPress Plugin');

            $options = [
                'framework' => $framework,
                'plugin_name' => $pluginName,
                'plugin_description' => "A WordPress plugin with automated testing setup",
                'namespace' => str_replace([' ', '-'], '', ucwords($pluginName, ' -'))
            ];

            $content = '';
            $filename = '';

            switch ($template) {
                case 'phpunit_config':
                    $content = $this->testTemplateService->generatePhpUnitConfig($options);
                    $filename = 'phpunit.xml';
                    break;
                case 'pest_config':
                    $content = $this->testTemplateService->generatePestConfig($options);
                    $filename = 'Pest.php';
                    break;
                case 'composer_json':
                    $content = $this->testTemplateService->generateComposerJson($options);
                    $filename = 'composer.json';
                    break;
                case 'bootstrap':
                    $content = $this->testTemplateService->generateBootstrapFile($options);
                    $filename = 'bootstrap.php';
                    break;
                case 'sample_test':
                    $content = $this->testTemplateService->generateSampleTest($options);
                    $filename = 'SampleTest.php';
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid template type',
                    ], 400);
            }

            return response($content)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            Log::error('Template download failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'template' => $request->input('template'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Template generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
