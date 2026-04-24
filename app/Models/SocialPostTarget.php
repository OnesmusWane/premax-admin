<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPostTarget extends Model
{
    protected $fillable = [
        'social_post_id',
        'social_account_id',
        'status',
        'likes_count',
        'comments_count',
        'shares_count',
        'external_post_id',
        'published_at',
        'failure_reason',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }
}
