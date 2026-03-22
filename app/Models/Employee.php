<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
 
class Employee extends Model
{
    use SoftDeletes;
 
    protected $fillable = [
        'user_id', 'department_id', 'employee_number',
        'first_name', 'last_name', 'phone', 'national_id',
        'date_of_birth', 'gender', 'avatar_url',
        'role', 'job_title', 'bio',
        'start_date', 'end_date', 'employment_type', 'employment_status',
        'termination_reason', 'salary', 'pay_frequency',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
        'show_on_website', 'sort_order',
    ];
 
    protected $casts = [
        'date_of_birth'  => 'date',
        'start_date'     => 'date',
        'end_date'       => 'date',
        'show_on_website'=> 'boolean',
        'salary'         => 'integer',
    ];
 
    // ── Relationships ─────────────────────────────────────────────────────
 
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
 
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
 
    // ── Accessors ─────────────────────────────────────────────────────────
 
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->first_name . ' ' . $this->last_name
        );
    }
 
    protected function initials(): Attribute
    {
        return Attribute::make(
            get: fn() => strtoupper($this->first_name[0] ?? '') . strtoupper($this->last_name[0] ?? '')
        );
    }
 
    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->employment_status === 'active'
        );
    }
 
    protected function yearsOfService(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->start_date) return null;
                $end = $this->end_date ?? now();
                return $this->start_date->diffInYears($end);
            }
        );
    }
 
    // ── Scopes ────────────────────────────────────────────────────────────
 
    public function scopeActive($query)
    {
        return $query->where('employment_status', 'active');
    }
 
    public function scopeVisibleOnWebsite($query)
    {
        return $query->where('show_on_website', true)->orderBy('sort_order');
    }
 
    public static function generateNumber(): string
    {
        $last = static::withTrashed()->count() + 1;
        return sprintf('EMP-%03d', $last);
    }
}