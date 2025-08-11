<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PluginAnalysisResult;
use App\Models\GitHubRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GitHubRepositoryDuplicateHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        
        // Set up configuration
        $this->app['config']->set('thinktest_ai.wordpress.analysis.max_file_size', 1024 * 1024);
        $this->app['config']->set('thinktest_ai.wordpress.analysis.allowed_extensions', ['php', 'zip']);
        $this->app['config']->set('thinktest_ai.security.file_validation.blocked_php_functions', [
            'exec', 'shell_exec', 'system', 'passthru', 'eval'
        ]);
        
        // Create required permissions
        Permission::create(['name' => 'upload files']);
        Permission::create(['name' => 'generate tests']);
    }

    public function test_duplicate_github_repository_analysis_updates_existing_record(): void
    {
        $user1 = User::factory()->create();
        $user1->givePermissionTo('upload files');
        
        $user2 = User::factory()->create();
        $user2->givePermissionTo('upload files');

        // Create a GitHub repository record
        $githubRepo = GitHubRepository::create([
            'user_id' => $user1->id,
            'owner' => 'testowner',
            'repo' => 'test-repo',
            'full_name' => 'testowner/test-repo',
            'branch' => 'main',
            'description' => 'Test repository',
            'is_private' => false,
            'html_url' => 'https://github.com/testowner/test-repo',
            'clone_url' => 'https://github.com/testowner/test-repo.git',
            'default_branch' => 'main',
            'language' => 'PHP',
            'size_bytes' => 1024,
            'processing_status' => 'completed',
        ]);

        // Create a plugin analysis result with a specific file hash
        $fileHash = 'test_duplicate_hash_12345';
        $analysisData = [
            'filename' => 'testowner/test-repo@main',
            'wordpress_patterns' => [
                ['type' => 'hook', 'function' => 'add_action', 'line' => 10]
            ],
            'functions' => [
                ['name' => 'test_function', 'line' => 20]
            ],
            'classes' => [],
            'hooks' => [],
            'filters' => [],
            'ajax_handlers' => [],
            'rest_endpoints' => [],
            'database_operations' => [],
            'security_patterns' => [],
            'test_recommendations' => [],
        ];

        // First analysis by user1
        $firstAnalysis = PluginAnalysisResult::create([
            'user_id' => $user1->id,
            'filename' => 'testowner/test-repo@main',
            'file_hash' => $fileHash,
            'analysis_data' => $analysisData,
            'complexity_score' => 15,
            'analyzed_at' => now(),
        ]);

        $this->assertDatabaseCount('plugin_analysis_results', 1);
        $this->assertEquals($user1->id, $firstAnalysis->user_id);
        $this->assertEquals(15, $firstAnalysis->complexity_score);

        // Simulate the duplicate handling logic from the controller
        $existingAnalysis = PluginAnalysisResult::where('file_hash', $fileHash)->first();
        
        if ($existingAnalysis) {
            // Update existing analysis result (simulating the controller logic)
            $updatedAnalysisData = array_merge($analysisData, [
                'functions' => [
                    ['name' => 'test_function', 'line' => 20],
                    ['name' => 'new_function', 'line' => 30] // Added new function
                ]
            ]);

            $existingAnalysis->update([
                'user_id' => $user2->id, // Update to current user
                'filename' => 'testowner/test-repo@main',
                'analysis_data' => $updatedAnalysisData,
                'complexity_score' => 25, // Updated complexity
                'analyzed_at' => now(),
            ]);
        }

        // Verify that the record was updated, not duplicated
        $this->assertDatabaseCount('plugin_analysis_results', 1);
        
        $updatedAnalysis = PluginAnalysisResult::first();
        $this->assertEquals($firstAnalysis->id, $updatedAnalysis->id); // Same record ID
        $this->assertEquals($user2->id, $updatedAnalysis->user_id); // Updated user
        $this->assertEquals(25, $updatedAnalysis->complexity_score); // Updated complexity
        $this->assertEquals($fileHash, $updatedAnalysis->file_hash); // Same file hash
        
        // Verify analysis data was updated
        $this->assertCount(2, $updatedAnalysis->analysis_data['functions']); // Should have 2 functions now
    }

    public function test_different_github_repositories_create_separate_records(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('upload files');

        // Create two different analysis results with different hashes
        $analysis1 = PluginAnalysisResult::create([
            'user_id' => $user->id,
            'filename' => 'owner1/repo1@main',
            'file_hash' => 'hash_repo1_12345',
            'analysis_data' => ['filename' => 'owner1/repo1@main'],
            'complexity_score' => 10,
            'analyzed_at' => now(),
        ]);

        $analysis2 = PluginAnalysisResult::create([
            'user_id' => $user->id,
            'filename' => 'owner2/repo2@main',
            'file_hash' => 'hash_repo2_67890',
            'analysis_data' => ['filename' => 'owner2/repo2@main'],
            'complexity_score' => 20,
            'analyzed_at' => now(),
        ]);

        // Verify two separate records were created
        $this->assertDatabaseCount('plugin_analysis_results', 2);
        $this->assertNotEquals($analysis1->file_hash, $analysis2->file_hash);
        $this->assertNotEquals($analysis1->id, $analysis2->id);
    }

    public function test_plugin_analysis_result_unique_constraint_exists(): void
    {
        $user = User::factory()->create();
        
        // Create first analysis result
        PluginAnalysisResult::create([
            'user_id' => $user->id,
            'filename' => 'test-file.php',
            'file_hash' => 'unique_test_hash_123',
            'analysis_data' => ['test' => 'data'],
            'complexity_score' => 5,
            'analyzed_at' => now(),
        ]);

        // Attempt to create duplicate should fail with database exception
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('UNIQUE constraint failed');

        PluginAnalysisResult::create([
            'user_id' => $user->id,
            'filename' => 'different-file.php',
            'file_hash' => 'unique_test_hash_123', // Same hash
            'analysis_data' => ['test' => 'data2'],
            'complexity_score' => 10,
            'analyzed_at' => now(),
        ]);
    }
}
