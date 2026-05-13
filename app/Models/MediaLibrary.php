<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaLibrary extends Model
{
    protected $table = 'media_library';

    protected $fillable = [
        'name',
        'url',
        'cloudinary_public_id',
        'mime_type',
        'type',
        'size',
        'width',
        'height',
        'duration',
        'tags',
        'used_count',
        'last_used_at',
        'created_by',
    ];

    protected $casts = [
        'tags'        => 'array',
        'last_used_at'=> 'datetime',
        'size'        => 'integer',
        'width'       => 'integer',
        'height'      => 'integer',
        'duration'    => 'integer',
        'used_count'  => 'integer',
    ];

    protected $appends = ['size_formatted', 'type_label'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSizeFormattedAttribute(): string
    {
        $bytes = $this->size ?? 0;

        if ($bytes >= 1_073_741_824) {
            return round($bytes / 1_073_741_824, 2) . ' GB';
        }

        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'image' => 'Image',
            'video' => 'Video',
            default => ucfirst($this->type ?? 'Unknown'),
        };
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('type', 'image');
    }

    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('type', 'video');
    }

    /**
     * Search by name or tag value.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhereJsonContains('tags', $search);
        });
    }

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
