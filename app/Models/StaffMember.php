<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class StaffMember extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'role',
        'bio',
        'avatar_url',
        'initials',
        'avatar_color',
        'email',
        'phone',
        'show_on_website',
        'sort_order',
    ];

    protected $casts = [
        'show_on_website' => 'boolean',
    ];

    // ── Accessors ─────────────────────────────────────────────────────────

    /**
     * Auto-generate initials from name if not explicitly set.
     * "James Mwangi" → "JM", "Grace Akinyi" → "GA"
     */
    protected function derivedInitials(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->initials) return $this->initials;

                return collect(explode(' ', $this->name))
                    ->take(2)
                    ->map(fn($w) => strtoupper($w[0] ?? ''))
                    ->implode('');
            }
        );
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeVisible($query)
    {
        return $query->where('show_on_website', true)->orderBy('sort_order');
    }
}