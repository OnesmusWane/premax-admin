<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use SoftDeletes;

    protected $table = 'services';

    protected $fillable = [
        'service_category_id',
        'name',
        'slug',
        'description',
        'icon',
        'price_from',
        'price_to',
        'price_is_estimate',
        'duration_minutes',
        'is_popular',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_is_estimate' => 'boolean',
        'is_popular'        => 'boolean',
        'is_active'         => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }
}