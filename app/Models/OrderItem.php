<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public const STATUSES = ['new', 'sent', 'preparing', 'ready', 'served', 'cancelled'];

    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'unit_price', 'tax_rate', 'destination',
        'quantity', 'line_total', 'notes', 'kitchen_note',
        'status', 'status_changed_at', 'status_changed_by',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'line_total' => 'decimal:2',
            'status_changed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }
}
