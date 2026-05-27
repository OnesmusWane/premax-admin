<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkCaseStep extends Model
{
    protected $table = 'work_case_steps';

    protected $fillable = [
        'work_case_id',
        'step_number',
        'title',
        'detail',
    ];

    public function workCase(): BelongsTo
    {
        return $this->belongsTo(WorkCase::class);
    }
}
