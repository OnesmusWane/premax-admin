<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialComment extends Model
{
    protected $fillable = [
        'social_account_id',
        'social_post_id',
        'assigned_user_id',
        'platform_comment_id',
        'author_name',
        'author_handle',
        'comment_text',
        'status',
        'reply_text',
        'received_at',
        'replied_at',
        'metadata',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'replied_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
