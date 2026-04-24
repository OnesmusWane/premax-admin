<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPost extends Model
{
    protected $fillable = [
        'title',
        'content',
        'media',
        'link_url',
        'status',
        'scheduled_for',
        'published_at',
        'failure_reason',
        'created_by',
    ];

    protected $casts = [
        'media' => 'array',
        'scheduled_for' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(SocialPostTarget::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SocialComment::class, 'social_post_id');
    }
}
