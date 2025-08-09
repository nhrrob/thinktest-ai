<?php

namespace App\Http\Controllers;

use App\Models\AIConversationState;
use App\Models\PluginAnalysisResult;
use App\Services\AI\AIProviderService;
use App\Services\WordPress\PluginAnalysisService;
use App\Services\FileProcessing\FileProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ThinkTestController extends Controller
{
    private AIProviderService $aiService;
    private PluginAnalysisService $analysisService;
    private FileProcessingService $fileService;

    public function __construct(
        AIProviderService $aiService,
        PluginAnalysisService $analysisService,
        FileProcessingService $fileService
    ) {
        $this->aiService = $aiService;
        $this->analysisService = $analysisService;
        $this->fileService = $fileService;

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
            'provider' => 'sometimes|string|in:openai,anthropic',
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
            'provider' => 'sometimes|string|in:openai,anthropic',
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
}
