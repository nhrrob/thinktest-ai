<?php

namespace Tests\Feature;

use App\Models\PluginAnalysisResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PluginAnalysisDuplicateHandlingTest extends TestCase
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
            'exec', 'shell_exec', 'system', 'passthru', 'eval',
        ]);

        // Create required permissions
        Permission::create(['name' => 'upload files']);
        Permission::create(['name' => 'generate tests']);
    }

    public function test_duplicate_file_hash_updates_existing_record(): void
    {
        $user1 = User::factory()->create();
        $user1->givePermissionTo('upload files');

        $user2 = User::factory()->create();
        $user2->givePermissionTo('upload files');

        // Create a plugin file
        $pluginContent = '<?php
/*
Plugin Name: Test Plugin
*/
add_action("init", "test_init");
function test_init() {
    wp_enqueue_script("test-script");
}';

        $file = UploadedFile::fake()->createWithContent('test-plugin.php', $pluginContent);

        // First upload by user1
        $response1 = $this->actingAs($user1)->postJson('/thinktest/upload', [
            'plugin_file' => $file,
            'provider' => 'openai-gpt5',
            'framework' => 'phpunit',
        ]);

        $response1->assertStatus(200);
        $response1->assertJson(['success' => true]);

        // Check that one analysis result was created
        $this->assertDatabaseCount('plugin_analysis_results', 1);
        $firstAnalysis = PluginAnalysisResult::first();
        $this->assertEquals($user1->id, $firstAnalysis->user_id);

        // Second upload by user2 with the same content
        $file2 = UploadedFile::fake()->createWithContent('test-plugin.php', $pluginContent);

        $response2 = $this->actingAs($user2)->postJson('/thinktest/upload', [
            'plugin_file' => $file2,
            'provider' => 'openai-gpt5',
            'framework' => 'phpunit',
        ]);

        $response2->assertStatus(200);
        $response2->assertJson(['success' => true]);

        // Check that still only one analysis result exists (updated, not duplicated)
        $this->assertDatabaseCount('plugin_analysis_results', 1);

        // Check that the record was updated to the second user
        $updatedAnalysis = PluginAnalysisResult::first();
        $this->assertEquals($user2->id, $updatedAnalysis->user_id);
        $this->assertEquals($firstAnalysis->id, $updatedAnalysis->id); // Same record ID
        $this->assertEquals($firstAnalysis->file_hash, $updatedAnalysis->file_hash); // Same file hash
    }

    public function test_different_content_creates_separate_records(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('upload files');

        // First plugin
        $pluginContent1 = '<?php
/*
Plugin Name: Test Plugin 1
*/
add_action("init", "test_init_1");';

        $file1 = UploadedFile::fake()->createWithContent('test-plugin-1.php', $pluginContent1);

        $response1 = $this->actingAs($user)->postJson('/thinktest/upload', [
            'plugin_file' => $file1,
            'provider' => 'openai-gpt5',
            'framework' => 'phpunit',
        ]);

        $response1->assertStatus(200);

        // Second plugin with different content
        $pluginContent2 = '<?php
/*
Plugin Name: Test Plugin 2
*/
add_action("init", "test_init_2");';

        $file2 = UploadedFile::fake()->createWithContent('test-plugin-2.php', $pluginContent2);

        $response2 = $this->actingAs($user)->postJson('/thinktest/upload', [
            'plugin_file' => $file2,
            'provider' => 'openai-gpt5',
            'framework' => 'phpunit',
        ]);

        $response2->assertStatus(200);

        // Check that two separate analysis results were created
        $this->assertDatabaseCount('plugin_analysis_results', 2);

        $analyses = PluginAnalysisResult::all();
        $this->assertNotEquals($analyses[0]->file_hash, $analyses[1]->file_hash);
    }

    public function test_analysis_result_update_preserves_important_fields(): void
    {
        $user1 = User::factory()->create();
        $user1->givePermissionTo('upload files');

        $user2 = User::factory()->create();
        $user2->givePermissionTo('upload files');

        $pluginContent = '<?php
/*
Plugin Name: Test Plugin
*/
add_action("init", "test_init");
class TestClass {
    public function test_method() {
        return true;
    }
}';

        // First upload
        $file1 = UploadedFile::fake()->createWithContent('test-plugin.php', $pluginContent);

        $response1 = $this->actingAs($user1)->postJson('/thinktest/upload', [
            'plugin_file' => $file1,
            'provider' => 'openai-gpt5',
            'framework' => 'phpunit',
        ]);

        $response1->assertStatus(200);

        // Second upload with same content
        $file2 = UploadedFile::fake()->createWithContent('test-plugin.php', $pluginContent);

        $response2 = $this->actingAs($user2)->postJson('/thinktest/upload', [
            'plugin_file' => $file2,
            'provider' => 'openai-gpt5',
            'framework' => 'phpunit',
        ]);

        $response2->assertStatus(200);

        // Check that analysis data is properly updated
        $updatedAnalysis = PluginAnalysisResult::first();
        $this->assertIsArray($updatedAnalysis->analysis_data);
        $this->assertArrayHasKey('wordpress_patterns', $updatedAnalysis->analysis_data);
        $this->assertArrayHasKey('functions', $updatedAnalysis->analysis_data);
        $this->assertArrayHasKey('classes', $updatedAnalysis->analysis_data);

        // Check that the analysis found the expected patterns
        $this->assertGreaterThan(0, count($updatedAnalysis->analysis_data['wordpress_patterns']));
        $this->assertGreaterThan(0, count($updatedAnalysis->analysis_data['classes']));
    }
}
