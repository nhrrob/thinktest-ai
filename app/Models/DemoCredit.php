<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoCredit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'credits_used',
        'credits_limit',
        'first_used_at',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'first_used_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the demo credits.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user has remaining demo credits.
     */
    public function hasCreditsRemaining(): bool
    {
        return $this->credits_used < $this->credits_limit;
    }

    /**
     * Get remaining credits count.
     */
    public function getRemainingCredits(): int
    {
        return max(0, $this->credits_limit - $this->credits_used);
    }

    /**
     * Use a demo credit.
     */
    public function useCredit(): bool
    {
        if (!$this->hasCreditsRemaining()) {
            return false;
        }

        $this->increment('credits_used');

        if ($this->credits_used === 1) {
            $this->update(['first_used_at' => now()]);
        }

        $this->update(['last_used_at' => now()]);

        return true;
    }

    /**
     * Get or create demo credits for a user.
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'credits_used' => 0,
                'credits_limit' => 5,
            ]
        );
    }
}
