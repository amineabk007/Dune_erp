<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

    protected $fillable = [
        'name', 'event_date', 'customer_id', 'guest_count', 'description',
        'total_amount', 'amount_paid', 'status', 'created_by',
        'cancelled_at', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(EventPayment::class);
    }

    public function balanceDue(): string
    {
        return number_format((float) $this->total_amount - (float) $this->amount_paid, 2, '.', '');
    }
}
