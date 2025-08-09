<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIConversationState extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_conversation_states';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'provider',
        'status',
        'context',
        'messages',
        'metadata',
        'plugin_file_path',
        'plugin_file_hash',
        'generated_tests',
        'step',
        'total_steps',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'context' => 'array',
        'messages' => 'array',
        'metadata' => 'array',
        'step' => 'integer',
        'total_steps' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the conversation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active conversations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for completed conversations.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for conversations by provider.
     */
    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Check if conversation is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if conversation is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_steps === 0) {
            return 0;
        }
        
        return round(($this->step / $this->total_steps) * 100, 2);
    }

    /**
     * Add a message to the conversation.
     */
    public function addMessage(array $message): void
    {
        $messages = $this->messages ?? [];
        $messages[] = array_merge($message, ['timestamp' => now()->toISOString()]);
        $this->update(['messages' => $messages]);
    }

    /**
     * Update conversation context.
     */
    public function updateContext(array $context): void
    {
        $currentContext = $this->context ?? [];
        $this->update(['context' => array_merge($currentContext, $context)]);
    }

    /**
     * Mark conversation as completed.
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark conversation as failed.
     */
    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
