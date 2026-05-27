<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WorkCase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'service_type',
        'before_image',
        'after_image',
        'brief',
        'challenge',
        'outcome',
        'duration_days',
        'completed_at',
        'client_type',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'completed_at' => 'date',
        'is_featured'  => 'boolean',
        'is_active'    => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(WorkCaseStep::class)->orderBy('step_number');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(WorkCaseMetric::class);
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(WorkCaseGallery::class)->orderBy('sort_order');
    }

    public static function generateSlug(string $title): string
    {
        $slug = Str::slug($title);
        $existing = static::withTrashed()->where('slug', 'like', $slug . '%')->pluck('slug');

        if (!$existing->contains($slug)) {
            return $slug;
        }

        $count = 2;
        while ($existing->contains($slug . '-' . $count)) {
            $count++;
        }

        return $slug . '-' . $count;
    }
}
