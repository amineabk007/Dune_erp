<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantTable extends Model
{
    use HasFactory;

    public const STATUSES = ['available', 'occupied', 'reserved', 'cleaning', 'inactive'];

    protected $fillable = ['zone_id', 'name', 'capacity', 'status'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
