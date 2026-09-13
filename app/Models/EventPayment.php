<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPayment extends Model
{
    public const TYPES = ['deposit', 'balance', 'other'];

    public const METHODS = ['cash', 'card', 'transfer', 'other'];

    private const TYPE_LABELS = [
        'deposit' => 'Acompte',
        'balance' => 'Solde',
        'other' => 'Autre',
    ];

    private const METHOD_LABELS = [
        'cash' => 'Espèces',
        'card' => 'Carte',
        'transfer' => 'Virement',
        'other' => 'Autre',
    ];

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

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function methodLabel(): string
    {
        return self::METHOD_LABELS[$this->method] ?? $this->method;
    }
}
