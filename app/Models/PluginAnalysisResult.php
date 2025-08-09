<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginAnalysisResult extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'plugin_analysis_results';

    protected $fillable = [
        'user_id',
        'filename',
        'file_hash',
        'analysis_data',
        'wordpress_patterns',
        'functions',
        'classes',
        'hooks',
        'filters',
        'security_patterns',
        'test_recommendations',
        'complexity_score',
        'analyzed_at',
    ];

    protected $casts = [
        'analysis_data' => 'array',
        'wordpress_patterns' => 'array',
        'functions' => 'array',
        'classes' => 'array',
        'hooks' => 'array',
        'filters' => 'array',
        'security_patterns' => 'array',
        'test_recommendations' => 'array',
        'complexity_score' => 'integer',
        'analyzed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the analysis result.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for recent analyses.
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('analyzed_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for analyses by complexity.
     */
    public function scopeByComplexity($query, $minScore = null, $maxScore = null)
    {
        if ($minScore !== null) {
            $query->where('complexity_score', '>=', $minScore);
        }
        
        if ($maxScore !== null) {
            $query->where('complexity_score', '<=', $maxScore);
        }
        
        return $query;
    }

    /**
     * Get the number of WordPress patterns found.
     */
    public function getWordPressPatternCount(): int
    {
        return count($this->wordpress_patterns ?? []);
    }

    /**
     * Get the number of functions found.
     */
    public function getFunctionCount(): int
    {
        return count($this->functions ?? []);
    }

    /**
     * Get the number of classes found.
     */
    public function getClassCount(): int
    {
        return count($this->classes ?? []);
    }

    /**
     * Check if the plugin has security patterns.
     */
    public function hasSecurityPatterns(): bool
    {
        return !empty($this->security_patterns);
    }

    /**
     * Get complexity level as string.
     */
    public function getComplexityLevel(): string
    {
        if ($this->complexity_score === null) {
            return 'Unknown';
        }

        if ($this->complexity_score <= 5) {
            return 'Low';
        } elseif ($this->complexity_score <= 10) {
            return 'Medium';
        } else {
            return 'High';
        }
    }
}
