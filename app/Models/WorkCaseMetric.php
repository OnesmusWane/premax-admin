<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkCaseMetric extends Model
{
    protected $table = 'work_case_metrics';

    protected $fillable = [
        'work_case_id',
        'label',
        'value',
    ];

    public function workCase(): BelongsTo
    {
        return $this->belongsTo(WorkCase::class);
    }
}
