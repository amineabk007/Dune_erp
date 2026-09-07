<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Record a stock movement and apply its signed delta to the ingredient's
     * current stock in the same transaction. Every path that changes stock
     * (purchases, consumption, waste, returns, transfers, corrections) goes
     * through here so the ledger is always complete.
     */
    public function move(
        Ingredient $ingredient,
        string $type,
        float $quantityDelta,
        ?User $user = null,
        ?string $reason = null,
        ?string $reference = null,
        ?float $unitCost = null,
    ): StockMovement {
        $becameLowStock = false;

        $movement = DB::transaction(function () use ($ingredient, $type, $quantityDelta, $user, $reason, $reference, $unitCost, &$becameLowStock) {
            $ingredient = Ingredient::lockForUpdate()->findOrFail($ingredient->id);
            $wasLowStock = (float) $ingredient->current_stock <= (float) $ingredient->minimum_stock;

            $movement = StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => $type,
                'quantity' => $quantityDelta,
                'unit_cost' => $unitCost,
                'reference' => $reference,
                'reason' => $reason,
                'user_id' => $user?->id,
            ]);

            $ingredient->update([
                'current_stock' => round((float) $ingredient->current_stock + $quantityDelta, 3),
            ]);

            if (in_array($type, ['adjustment', 'waste'], true)) {
                $this->audit->log('stock_'.$type, 'stock', $movement, null, [
                    'ingredient' => $ingredient->name,
                    'quantity' => $quantityDelta,
                ], $reason);
            }

            // Alert only on the transition into low stock, not on every
            // subsequent movement while it stays low, to avoid spamming.
            $becameLowStock = ! $wasLowStock && (float) $ingredient->current_stock <= (float) $ingredient->minimum_stock;

            return $movement;
        });

        if ($becameLowStock) {
            $this->notifications->lowStockAlert($ingredient->fresh());
        }

        return $movement;
    }

    /**
     * Record a manual stock adjustment (correction, damage, etc.). Requires a
     * reason since it is an audited, permission-gated action.
     */
    public function adjust(Ingredient $ingredient, User $user, float $quantityDelta, string $reason): StockMovement
    {
        return $this->move($ingredient, 'adjustment', $quantityDelta, $user, $reason);
    }

    public function recordWaste(Ingredient $ingredient, User $user, float $quantity, string $reason): StockMovement
    {
        if ($quantity <= 0) {
            throw new DomainException('La quantité de casse doit être positive.');
        }

        // unit_cost is snapshotted at the ingredient's current cost so the
        // waste reports stay accurate even if the ingredient's cost later
        // changes (e.g. a new purchase at a different price).
        return $this->move($ingredient, 'waste', -$quantity, $user, $reason, null, (float) $ingredient->unit_cost);
    }

    /**
     * Physical inventory count: corrects stock to match what was actually
     * counted, recording the difference as a traced adjustment.
     */
    public function recordInventory(Ingredient $ingredient, User $user, float $countedQuantity): StockMovement
    {
        $delta = round($countedQuantity - (float) $ingredient->current_stock, 3);

        return $this->move($ingredient, 'adjustment', $delta, $user, 'Inventaire physique');
    }

    /**
     * Consume the ingredients called for by each sold item's recipe. Called
     * once, when an order is fully paid (i.e. the sale is final).
     */
    public function consumeForOrder(Order $order, User $user): void
    {
        $order->loadMissing('items.product.recipe.items.ingredient');

        foreach ($order->items as $item) {
            if ($item->status === 'cancelled' || ! $item->product?->recipe) {
                continue;
            }

            $recipe = $item->product->recipe;
            $yield = (float) $recipe->yield_quantity ?: 1;

            foreach ($recipe->items as $recipeItem) {
                $consumed = round(((float) $recipeItem->quantity / $yield) * $item->quantity, 3);

                $this->move(
                    $recipeItem->ingredient,
                    'sale_consumption',
                    -$consumed,
                    $user,
                    null,
                    $order->order_number
                );
            }
        }
    }

    /**
     * Record the waste of a finished dish (dropped, burnt, sent back...) by
     * exploding its recipe into ingredient-level "waste" movements, exactly
     * like a sale would consume them — so raw-material stock stays accurate
     * even though this dish was never actually served. Returns the food
     * cost of the loss so it can be shown to whoever declares it.
     */
    public function recordDishWaste(Product $product, User $user, float $quantity, string $reason): float
    {
        if ($quantity <= 0) {
            throw new DomainException('La quantité de perte doit être positive.');
        }

        $recipe = $product->recipe;

        if (! $recipe) {
            throw new DomainException("Ce produit n'a pas de recette associée ; la perte ne peut pas être chiffrée.");
        }

        $recipe->loadMissing('items.ingredient');
        $yield = (float) $recipe->yield_quantity ?: 1;
        $totalCost = 0.0;

        foreach ($recipe->items as $recipeItem) {
            $consumed = round(((float) $recipeItem->quantity / $yield) * $quantity, 3);

            $this->move(
                $recipeItem->ingredient,
                'waste',
                -$consumed,
                $user,
                $reason,
                $product->name,
                (float) $recipeItem->ingredient->unit_cost
            );

            $totalCost += $consumed * (float) $recipeItem->ingredient->unit_cost;
        }

        return round($totalCost, 2);
    }

    public function lowStock()
    {
        return Ingredient::whereColumn('current_stock', '<=', 'minimum_stock')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
