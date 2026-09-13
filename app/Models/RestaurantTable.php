<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantTable extends Model
{
    use HasFactory;

    public const STATUSES = ['available', 'occupied', 'reserved', 'cleaning', 'inactive'];

    public const STATUS_LABELS = [
        'available' => 'Disponible',
        'occupied' => 'Occupée',
        'reserved' => 'Réservée',
        'cleaning' => 'Nettoyage',
        'inactive' => 'Inactive',
    ];

    protected $fillable = ['zone_id', 'name', 'capacity', 'status'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public static function labelForStatus(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }
}
