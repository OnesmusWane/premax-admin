<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialConversation extends Model
{
    protected $fillable = [
        'social_account_id',
        'platform_thread_id',
        'customer_name',
        'customer_handle',
        'customer_avatar_url',
        'status',
        'unread_count',
        'priority',
        'last_message_preview',
        'last_message_at',
        'metadata',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'unread_count' => 'integer',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class, 'social_account_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SocialMessage::class);
    }
}
