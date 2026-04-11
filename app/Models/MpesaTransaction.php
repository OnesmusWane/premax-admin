<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpesaTransaction extends Model
{
    protected $fillable = [
        'provider',
        'checkout_request_id',
        'location',
        'internal_reference',
        'mpesa_receipt_number',
        'phone',
        'amount',
        'status',
        'result_code',
        'result_desc',
        'callback_payload',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'result_code' => 'integer',
        'callback_payload' => 'array',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
