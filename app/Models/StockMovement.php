<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    const UPDATED_AT = null;

    public const TYPES = ['purchase', 'sale_consumption', 'adjustment', 'waste', 'return', 'transfer'];

    protected $fillable = ['ingredient_id', 'type', 'quantity', 'unit_cost', 'reference', 'reason', 'user_id'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Movements are an immutable ledger, like audit logs.
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new \LogicException('Stock movements are immutable and cannot be updated.');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new \LogicException('Stock movements are immutable and cannot be deleted.');
    }
}
