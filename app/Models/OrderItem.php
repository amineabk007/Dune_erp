<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public const STATUSES = ['new', 'sent', 'preparing', 'ready', 'served', 'cancelled'];

    private const STATUS_LABELS = [
        'new' => 'Nouveau',
        'sent' => 'Envoyé',
        'preparing' => 'En préparation',
        'ready' => 'Prêt',
        'served' => 'Servi',
        'cancelled' => 'Annulé',
    ];

    private const DESTINATION_LABELS = [
        'kitchen' => 'Cuisine',
        'bar' => 'Bar',
    ];

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

    /**
     * The tax-inclusive price the customer actually pays — `unit_price`
     * and `line_total` are stored tax-exclusive so the order's subtotal/
     * tax/total breakdown stays consistent, but any customer-facing
     * display (POS catalogue, ticket, receipt) must show the menu price.
     */
    public function unitPriceTtc(): float
    {
        return round((float) $this->unit_price * (1 + (float) $this->tax_rate / 100), 2);
    }

    public function lineTotalTtc(): float
    {
        return round((float) $this->line_total * (1 + (float) $this->tax_rate / 100), 2);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function destinationLabel(): string
    {
        return self::DESTINATION_LABELS[$this->destination] ?? $this->destination;
    }
}
