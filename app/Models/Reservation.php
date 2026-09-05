<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reservation extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show'];

    /**
     * How long a reservation blocks its table(s) for conflict detection.
     */
    public const DURATION_MINUTES = 120;

    protected $fillable = ['customer_id', 'created_by', 'reserved_at', 'guests', 'status', 'notes', 'order_id'];

    protected function casts(): array
    {
        return ['reserved_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function tables(): BelongsToMany
    {
        return $this->belongsToMany(RestaurantTable::class, 'reservation_tables', 'reservation_id', 'table_id');
    }

    public function endsAt(): Carbon
    {
        return $this->reserved_at->clone()->addMinutes(self::DURATION_MINUTES);
    }
}
