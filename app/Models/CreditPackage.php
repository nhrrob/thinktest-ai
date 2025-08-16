<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditPackage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'credits',
        'price',
        'price_per_credit',
        'bonus_credits',
        'is_popular',
        'is_active',
        'sort_order',
        'features',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'credits' => 'decimal:2',
        'price' => 'decimal:2',
        'price_per_credit' => 'decimal:4',
        'bonus_credits' => 'integer',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'features' => 'array',
    ];

    /**
     * Get payment intents for this package.
     */
    public function paymentIntents(): HasMany
    {
        return $this->hasMany(PaymentIntent::class);
    }

    /**
     * Scope for active packages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for popular packages.
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Scope for ordered packages.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /**
     * Get total credits including bonus.
     */
    public function getTotalCreditsAttribute(): float
    {
        return $this->credits + $this->bonus_credits;
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get formatted credits.
     */
    public function getFormattedCreditsAttribute(): string
    {
        $credits = number_format($this->credits, 0);
        
        if ($this->bonus_credits > 0) {
            $bonus = number_format($this->bonus_credits, 0);
            return "{$credits} + {$bonus} bonus";
        }
        
        return $credits;
    }

    /**
     * Get savings percentage compared to base price.
     */
    public function getSavingsPercentageAttribute(): ?int
    {
        $basePrice = 0.50; // Base price per credit
        $savings = (($basePrice - $this->price_per_credit) / $basePrice) * 100;
        
        return $savings > 0 ? round($savings) : null;
    }

    /**
     * Get package value proposition.
     */
    public function getValuePropositionAttribute(): string
    {
        if ($this->bonus_credits > 0) {
            return "Get {$this->bonus_credits} bonus credits";
        }
        
        if ($this->savings_percentage) {
            return "Save {$this->savings_percentage}%";
        }
        
        return "Best for getting started";
    }

    /**
     * Check if this package is the best value.
     */
    public function isBestValue(): bool
    {
        $allPackages = static::active()->get();
        $bestPricePerCredit = $allPackages->min('price_per_credit');
        
        return $this->price_per_credit == $bestPricePerCredit;
    }

    /**
     * Get recommended package based on usage.
     */
    public static function getRecommendedForUsage(int $monthlyUsage): ?self
    {
        // Recommend based on monthly usage patterns
        if ($monthlyUsage <= 25) {
            return static::where('slug', 'starter-pack')->first();
        } elseif ($monthlyUsage <= 50) {
            return static::where('slug', 'developer-pack')->first();
        } elseif ($monthlyUsage <= 100) {
            return static::where('slug', 'professional-pack')->first();
        } else {
            return static::where('slug', 'enterprise-pack')->first();
        }
    }
}
