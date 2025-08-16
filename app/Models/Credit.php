<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'balance',
        'total_purchased',
        'total_used',
        'last_purchase_at',
        'last_usage_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'total_purchased' => 'decimal:2',
        'total_used' => 'decimal:2',
        'last_purchase_at' => 'datetime',
        'last_usage_at' => 'datetime',
    ];

    /**
     * Get the user that owns the credits.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all credit transactions for this user.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Check if user has sufficient credits.
     */
    public function hasCredits(float $amount = 1.0): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get or create credits record for a user.
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'balance' => 0.00,
                'total_purchased' => 0.00,
                'total_used' => 0.00,
            ]
        );
    }

    /**
     * Add credits to the user's balance.
     */
    public function addCredits(float $amount, string $description, array $metadata = []): CreditTransaction
    {
        $balanceBefore = $this->balance;
        $this->increment('balance', $amount);
        $this->increment('total_purchased', $amount);
        $this->update(['last_purchase_at' => now()]);

        return CreditTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'purchase',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->fresh()->balance,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Deduct credits from the user's balance.
     */
    public function deductCredits(float $amount, string $description, array $metadata = []): CreditTransaction
    {
        if (!$this->hasCredits($amount)) {
            throw new \RuntimeException('Insufficient credits. Current balance: ' . $this->balance);
        }

        $balanceBefore = $this->balance;
        $this->decrement('balance', $amount);
        $this->increment('total_used', $amount);
        $this->update(['last_usage_at' => now()]);

        return CreditTransaction::create([
            'user_id' => $this->user_id,
            'type' => 'usage',
            'amount' => -$amount, // Negative for deduction
            'balance_before' => $balanceBefore,
            'balance_after' => $this->fresh()->balance,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get recent transactions.
     */
    public function getRecentTransactions(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->transactions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get usage statistics.
     */
    public function getUsageStats(): array
    {
        $transactions = $this->transactions()
            ->where('type', 'usage')
            ->selectRaw('
                COUNT(*) as total_uses,
                SUM(ABS(amount)) as total_credits_used,
                ai_provider,
                COUNT(*) as provider_uses
            ')
            ->groupBy('ai_provider')
            ->get();

        return [
            'total_uses' => $transactions->sum('total_uses'),
            'total_credits_used' => $this->total_used,
            'provider_breakdown' => $transactions->mapWithKeys(function ($item) {
                return [$item->ai_provider => [
                    'uses' => $item->provider_uses,
                    'credits' => $item->total_credits_used,
                ]];
            })->toArray(),
        ];
    }
}
