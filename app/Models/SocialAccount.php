<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialAccount extends Model
{
    protected $fillable = [
        'platform',
        'name',
        'username',
        'external_account_id',
        'connection_type',
        'auth_status',
        'status',
        'capabilities',
        'profile_image_url',
        'followers_count',
        'inbox_count',
        'credentials',
        'auto_sync_enabled',
        'sync_frequency_minutes',
        'sync_status',
        'sync_error',
        'last_sync_started_at',
        'last_sync_completed_at',
        'last_webhook_at',
        'token_expires_at',
        'last_token_checked_at',
        'webhook_verify_token',
        'metadata',
        'last_synced_at',
        'is_active',
        'connected_by',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'followers_count' => 'integer',
        'inbox_count' => 'integer',
        'credentials' => 'encrypted:array',
        'auto_sync_enabled' => 'boolean',
        'sync_frequency_minutes' => 'integer',
        'last_sync_started_at' => 'datetime',
        'last_sync_completed_at' => 'datetime',
        'last_webhook_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'last_token_checked_at' => 'datetime',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function connector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public function postTargets(): HasMany
    {
        return $this->hasMany(SocialPostTarget::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(SocialConversation::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SocialComment::class);
    }
}
