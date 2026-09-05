<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'yield_quantity', 'instructions'];

    protected function casts(): array
    {
        return ['yield_quantity' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    /**
     * Total ingredient cost for the whole recipe batch, divided by its yield
     * to give the food cost of a single unit sold.
     */
    public function foodCostPerUnit(): float
    {
        $batchCost = $this->items->sum(fn (RecipeItem $item) => (float) $item->quantity * (float) $item->ingredient->unit_cost);

        $yield = (float) $this->yield_quantity ?: 1;

        return round($batchCost / $yield, 4);
    }
}
