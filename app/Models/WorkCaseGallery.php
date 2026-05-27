<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkCaseGallery extends Model
{
    protected $table = 'work_case_gallery';

    protected $fillable = [
        'work_case_id',
        'image_path',
        'caption',
        'sort_order',
    ];

    public function workCase(): BelongsTo
    {
        return $this->belongsTo(WorkCase::class);
    }
}
