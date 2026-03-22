<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
 
class Department extends Model
{
    use SoftDeletes;
 
    protected $fillable = ['name', 'slug', 'description', 'head_employee_id', 'is_active'];
 
    protected $casts = ['is_active' => 'boolean'];
 
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
 
    public function activeEmployees(): HasMany
    {
        return $this->hasMany(Employee::class)->where('employment_status', 'active');
    }
 
    public function head(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'head_employee_id');
    }
}
