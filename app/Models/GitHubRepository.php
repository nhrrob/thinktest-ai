<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GitHubRepository extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'github_repositories';

    protected $fillable = [
        'user_id',
        'owner',
        'repo',
        'full_name',
        'branch',
        'github_id',
        'description',
        'is_private',
        'default_branch',
        'size_bytes',
        'language',
        'languages',
        'clone_url',
        'html_url',
        'last_updated_at',
        'plugin_structure',
        'file_count',
        'processing_status',
        'processing_error',
        'processed_at',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'languages' => 'array',
        'plugin_structure' => 'array',
        'last_updated_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the repository record
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI conversations for this repository
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(AIConversationState::class, 'github_repository_id');
    }

    /**
     * Scope for completed processing
     */
    public function scopeCompleted($query)
    {
        return $query->where('processing_status', 'completed');
    }

    /**
     * Scope for failed processing
     */
    public function scopeFailed($query)
    {
        return $query->where('processing_status', 'failed');
    }

    /**
     * Scope for pending processing
     */
    public function scopePending($query)
    {
        return $query->where('processing_status', 'pending');
    }

    /**
     * Check if repository processing is completed
     */
    public function isProcessed(): bool
    {
        return $this->processing_status === 'completed';
    }

    /**
     * Check if repository processing failed
     */
    public function hasFailed(): bool
    {
        return $this->processing_status === 'failed';
    }

    /**
     * Check if repository is currently being processed
     */
    public function isProcessing(): bool
    {
        return $this->processing_status === 'processing';
    }

    /**
     * Mark repository as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'processing_status' => 'processing',
            'processing_error' => null,
        ]);
    }

    /**
     * Mark repository as completed
     */
    public function markAsCompleted(?array $pluginStructure = null, int $fileCount = 0): void
    {
        $this->update([
            'processing_status' => 'completed',
            'processing_error' => null,
            'processed_at' => now(),
            'plugin_structure' => $pluginStructure,
            'file_count' => $fileCount,
        ]);
    }

    /**
     * Mark repository as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'processing_status' => 'failed',
            'processing_error' => $error,
            'processed_at' => now(),
        ]);
    }

    /**
     * Get repository URL
     */
    public function getRepositoryUrlAttribute(): string
    {
        return "https://github.com/{$this->full_name}";
    }

    /**
     * Get branch URL
     */
    public function getBranchUrlAttribute(): string
    {
        return "https://github.com/{$this->full_name}/tree/{$this->branch}";
    }

    /**
     * Get formatted size
     */
    public function getFormattedSizeAttribute(): string
    {
        if (! $this->size_bytes) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->size_bytes;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
