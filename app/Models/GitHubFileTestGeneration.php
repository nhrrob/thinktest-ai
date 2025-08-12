<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitHubFileTestGeneration extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'github_file_test_generations';

    protected $fillable = [
        'user_id',
        'github_repository_id',
        'ai_conversation_state_id',
        'file_path',
        'file_name',
        'file_sha',
        'file_size',
        'branch',
        'provider',
        'framework',
        'generated_tests',
        'test_suite',
        'analysis_data',
        'file_content_hash',
        'generation_status',
        'generation_error',
        'generated_at',
    ];

    protected $casts = [
        'generated_tests' => 'array',
        'test_suite' => 'array',
        'analysis_data' => 'array',
        'file_size' => 'integer',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the user that owns this file test generation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the GitHub repository this file belongs to
     */
    public function githubRepository(): BelongsTo
    {
        return $this->belongsTo(GitHubRepository::class, 'github_repository_id');
    }

    /**
     * Get the AI conversation state associated with this generation
     */
    public function aiConversationState(): BelongsTo
    {
        return $this->belongsTo(AIConversationState::class);
    }

    /**
     * Scope for successful generations
     */
    public function scopeSuccessful($query)
    {
        return $query->where('generation_status', 'completed');
    }

    /**
     * Scope for failed generations
     */
    public function scopeFailed($query)
    {
        return $query->where('generation_status', 'failed');
    }

    /**
     * Scope for specific file path
     */
    public function scopeForFile($query, string $filePath)
    {
        return $query->where('file_path', $filePath);
    }

    /**
     * Scope for specific repository
     */
    public function scopeForRepository($query, int $repositoryId)
    {
        return $query->where('github_repository_id', $repositoryId);
    }

    /**
     * Check if generation was successful
     */
    public function isSuccessful(): bool
    {
        return $this->generation_status === 'completed';
    }

    /**
     * Check if generation failed
     */
    public function isFailed(): bool
    {
        return $this->generation_status === 'failed';
    }

    /**
     * Mark generation as completed
     */
    public function markAsCompleted(array $tests, array $testSuite, array $analysis): void
    {
        $this->update([
            'generated_tests' => $tests,
            'test_suite' => $testSuite,
            'analysis_data' => $analysis,
            'generation_status' => 'completed',
            'generated_at' => now(),
        ]);
    }

    /**
     * Mark generation as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'generation_status' => 'failed',
            'generation_error' => $error,
        ]);
    }
}
