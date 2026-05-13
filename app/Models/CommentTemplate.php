<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentTemplate extends Model
{
    protected $fillable = [
        'name',
        'body',
        'platform',
        'shortcut',
        'usage_count',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Filter templates that apply to the given platform (includes 'all' and null).
     */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where(function (Builder $q) use ($platform) {
            $q->where('platform', $platform)
              ->orWhere('platform', 'all')
              ->orWhereNull('platform');
        });
    }

    /**
     * Full-text search across name, body, and shortcut.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('body', 'like', "%{$search}%")
              ->orWhere('shortcut', 'like', "%{$search}%");
        });
    }
}
