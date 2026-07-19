<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nic_passport',
        'mobile_number',
        'name',
        'status',
        'registered_by',
        'activated_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    /**
     * Calculate current total points balance (cached in Redis).
     */
    public function getPointsBalanceAttribute(): int
    {
        $cacheKey = "customer:{$this->id}:points_balance";

        return (int) \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () {
            return $this->loyaltyTransactions()->sum('points');
        });
    }
}
