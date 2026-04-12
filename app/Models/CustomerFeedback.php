<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFeedback extends Model
{
    protected $table = 'customer_feedbacks';
    protected $fillable = [
        'feedback_token_id', 'name', 'phone', 'vehicle',
        'service', 'rating', 'liked', 'suggestions', 'recommend',
    ];
 
    public function token(): BelongsTo
    {
        return $this->belongsTo(FeedbackToken::class, 'feedback_token_id');
    }
}
