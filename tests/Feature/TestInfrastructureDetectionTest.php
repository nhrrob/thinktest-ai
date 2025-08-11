<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AIConversationState;
use App\Services\FileProcessing\FileProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TestInfrastructureDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        // Set up configuration for file processing
        $this->app['config']->set('thinktest_ai.wordpress.analysis.max_file_size', 1024 * 1024); // 1MB
        $this->app['config']->set('thinktest_ai.wordpress.analysis.allowed_extensions', ['php', 'zip']);
        $this->app['config']->set('thinktest_ai.security.file_validation.blocked_php_functions', [
            'exec', 'shell_exec', 'system', 'passthru', 'eval'
        ]);
    }

    public function test_detect_infrastructure_endpoint_works_with_file_upload(): void
    {
        $user = User::factory()->create();
        
        // Create a simple plugin file
        $pluginContent = '<?php
/*
Plugin Name: Test Plugin
*/
add_action("init", "test_init");
function test_init() {
    wp_enqueue_script("test-script");
}';

        // Create a fake uploaded file with proper size
        $file = UploadedFile::fake()->createWithContent('test-plugin.php', $pluginContent);
        
        // Store the file content using the file service
        $fileService = app(FileProcessingService::class);
        $fileData = $fileService->processUploadedFile($file, $user->id);

        // Create a conversation with the file data
        $conversation = AIConversationState::create([
            'user_id' => $user->id,
            'conversation_id' => 'test-conversation-id',
            'provider' => 'openai',
            'status' => 'active',
            'context' => [
                'filename' => $fileData['filename'],
                'framework' => 'phpunit',
            ],
            'plugin_file_path' => $fileData['stored_path'],
            'plugin_file_hash' => $fileData['file_hash'],
            'step' => 1,
            'total_steps' => 3,
            'started_at' => now(),
        ]);

        // Test the detect infrastructure endpoint
        $response = $this->actingAs($user)->postJson('/thinktest/detect-infrastructure', [
            'conversation_id' => $conversation->conversation_id,
            'framework' => 'phpunit',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $responseData = $response->json();
        $this->assertArrayHasKey('detection', $responseData);
        $this->assertArrayHasKey('instructions', $responseData);
        
        // Check that detection results are properly structured
        $detection = $responseData['detection'];
        $this->assertArrayHasKey('has_phpunit_config', $detection);
        $this->assertArrayHasKey('has_pest_config', $detection);
        $this->assertArrayHasKey('has_composer_json', $detection);
        $this->assertArrayHasKey('has_test_directory', $detection);
        $this->assertArrayHasKey('missing_components', $detection);
        $this->assertArrayHasKey('recommendations', $detection);
    }

    public function test_detect_infrastructure_handles_missing_conversation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/thinktest/detect-infrastructure', [
            'conversation_id' => 'non-existent-conversation',
            'framework' => 'phpunit',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Conversation not found',
        ]);
    }

    public function test_detect_infrastructure_handles_missing_plugin_file(): void
    {
        $user = User::factory()->create();

        // Create a conversation without plugin file path
        $conversation = AIConversationState::create([
            'user_id' => $user->id,
            'conversation_id' => 'test-conversation-id',
            'provider' => 'openai',
            'status' => 'active',
            'context' => [
                'filename' => 'test.php',
                'framework' => 'phpunit',
            ],
            'plugin_file_path' => null, // No file path
            'step' => 1,
            'total_steps' => 3,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/thinktest/detect-infrastructure', [
            'conversation_id' => $conversation->conversation_id,
            'framework' => 'phpunit',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Plugin file not found. Please re-upload or re-process your plugin.',
        ]);
    }
}
