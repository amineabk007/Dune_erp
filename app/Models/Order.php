<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = ['open', 'sent', 'preparing', 'ready', 'served', 'paid', 'cancelled'];

    protected $fillable = [
        'order_number', 'table_id', 'customer_id', 'user_id', 'status',
        'subtotal', 'discount_amount', 'discount_reason', 'discount_by',
        'tax_amount', 'total', 'amount_paid', 'notes',
        'cancelled_at', 'cancelled_by', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function discountBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discount_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function balanceDue(): string
    {
        return number_format((float) $this->total - (float) $this->amount_paid, 2, '.', '');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
