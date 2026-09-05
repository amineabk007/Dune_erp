<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHODS = ['cash', 'card', 'transfer', 'other'];

    protected $fillable = [
        'order_id', 'cash_session_id', 'method', 'amount', 'received_by',
        'refunded', 'refunded_at', 'refunded_by', 'refund_reason', 'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded' => 'boolean',
            'refunded_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }
}
