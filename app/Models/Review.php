<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reviewer_name',
        'reviewer_email',
        'reviewer_phone',
        'reviewer_avatar_url',
        'reviewer_initials',
        'reviewer_avatar_color',
        'rating',
        'body',
        'service_id',
        'service_category_id',
        'source',
        'source_url',
        'source_review_id',
        'is_verified_customer',
        'verification_reference',
        'status',
        'moderation_note',
        'is_featured',
        'show_on_website',
        'owner_response',
        'owner_responded_at',
        'reviewed_at',
    ];

    protected $casts = [
        'is_verified_customer' => 'boolean',
        'is_featured'          => 'boolean',
        'show_on_website'      => 'boolean',
        'reviewed_at'          => 'datetime',
        'owner_responded_at'   => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    /**
     * Auto-generate initials from reviewer name if not set.
     * e.g. "David Ochieng" → "DO"
     */
    public function getInitialsAttribute(): string
    {
        if ($this->reviewer_initials) {
            return $this->reviewer_initials;
        }

        return collect(explode(' ', $this->reviewer_name))
            ->take(2)
            ->map(fn($word) => strtoupper($word[0] ?? ''))
            ->implode('');
    }

    /**
     * Avatar background color — falls back to brand red.
     */
    public function getAvatarColorAttribute(): string
    {
        return $this->reviewer_avatar_color ?? '#DC2626';
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', 'approved')->where('show_on_website', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}