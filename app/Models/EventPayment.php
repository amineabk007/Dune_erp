<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPayment extends Model
{
    public const TYPES = ['deposit', 'balance', 'other'];

    public const METHODS = ['cash', 'card', 'transfer', 'other'];

    protected $fillable = ['event_id', 'type', 'method', 'amount', 'received_by', 'reference'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
