<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'slug',
        'content',
        'version',
        'effective_date',
        'is_active',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'effective_date' => 'datetime',
    ];

    // Type constants
    const TYPE_PRIVACY = 'privacy_policy';
    const TYPE_TERMS   = 'terms_of_service';

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::active()->where('slug', $slug)->first();
    }

    public static function findByType(string $type): ?self
    {
        return static::active()->where('type', $type)->first();
    }
}