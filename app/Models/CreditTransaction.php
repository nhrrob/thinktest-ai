<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'metadata',
        'payment_intent_id',
        'payment_method',
        'payment_status',
        'ai_provider',
        'ai_model',
        'tokens_used',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'tokens_used' => 'integer',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment intent associated with this transaction.
     */
    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class, 'payment_intent_id', 'stripe_payment_intent_id');
    }

    /**
     * Scope for purchase transactions.
     */
    public function scopePurchases($query)
    {
        return $query->where('type', 'purchase');
    }

    /**
     * Scope for usage transactions.
     */
    public function scopeUsage($query)
    {
        return $query->where('type', 'usage');
    }

    /**
     * Scope for refund transactions.
     */
    public function scopeRefunds($query)
    {
        return $query->where('type', 'refund');
    }

    /**
     * Scope for transactions by AI provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('ai_provider', $provider);
    }

    /**
     * Get formatted amount with sign.
     */
    public function getFormattedAmountAttribute(): string
    {
        $sign = $this->amount >= 0 ? '+' : '';
        return $sign . number_format($this->amount, 2);
    }

    /**
     * Get transaction type display name.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'purchase' => 'Credit Purchase',
            'usage' => 'AI Usage',
            'refund' => 'Refund',
            'bonus' => 'Bonus Credits',
            'adjustment' => 'Balance Adjustment',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get AI provider display name.
     */
    public function getProviderDisplayAttribute(): string
    {
        if (!$this->ai_provider) {
            return 'N/A';
        }

        return match ($this->ai_provider) {
            'openai-gpt5' => 'OpenAI GPT-5',
            'openai-gpt5-mini' => 'OpenAI GPT-5 Mini',
            'anthropic-claude4-opus' => 'Anthropic Claude 4 Opus',
            'anthropic-claude4-sonnet' => 'Anthropic Claude 4 Sonnet',
            'anthropic-claude' => 'Anthropic Claude 3.5 Sonnet',
            default => ucfirst(str_replace('-', ' ', $this->ai_provider)),
        };
    }

    /**
     * Check if this is a credit addition transaction.
     */
    public function isCreditAddition(): bool
    {
        return in_array($this->type, ['purchase', 'bonus', 'refund']) && $this->amount > 0;
    }

    /**
     * Check if this is a credit deduction transaction.
     */
    public function isCreditDeduction(): bool
    {
        return $this->type === 'usage' && $this->amount < 0;
    }

    /**
     * Get the cost per credit for this transaction.
     */
    public function getCostPerCreditAttribute(): ?float
    {
        if ($this->type !== 'purchase' || !$this->metadata || !isset($this->metadata['price'])) {
            return null;
        }

        return round($this->metadata['price'] / $this->amount, 4);
    }
}
