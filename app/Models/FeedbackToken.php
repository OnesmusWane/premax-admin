<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class FeedbackToken extends Model
{
    protected $fillable = [
        'token', 'customer_name', 'customer_phone',
        'vehicle_reg', 'service', 'used', 'expires_at',
    ];
 
    protected $casts = [
        'used'       => 'boolean',
        'expires_at' => 'datetime',
    ];
 
    // ── Relationships ────────────────────────────
 
    public function feedback(): HasOne
    {
        return $this->hasOne(CustomerFeedback::class);
    }
 
    // ── Helpers ──────────────────────────────────
 
    /** Generate a fresh one-time token */
    public static function generate(array $prefill = []): self
    {
        return self::create(array_merge([
            'token'      => Str::random(48),
            'used'       => false,
            'expires_at' => now()->addDays(7), // link valid for 7 days
        ], $prefill));
    }
 
    /** Is this token still usable? */
    public function isValid(): bool
    {
        return ! $this->used
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
