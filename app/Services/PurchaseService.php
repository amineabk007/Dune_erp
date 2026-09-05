<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly StockService $stock,
    ) {}

    /**
     * @param  array<int, array{ingredient_id: int, quantity: float, unit_cost: float}>  $lines
     */
    public function create(User $user, int $supplierId, array $lines, ?string $reference = null, ?string $notes = null): Purchase
    {
        if (empty($lines)) {
            throw new DomainException('Une commande doit contenir au moins une ligne.');
        }

        return DB::transaction(function () use ($user, $supplierId, $lines, $reference, $notes) {
            $purchase = Purchase::create([
                'supplier_id' => $supplierId,
                'user_id' => $user->id,
                'status' => 'ordered',
                'reference' => $reference,
                'notes' => $notes,
                'ordered_at' => now(),
            ]);

            $total = 0;
            foreach ($lines as $line) {
                $lineTotal = round((float) $line['quantity'] * (float) $line['unit_cost'], 2);
                $total += $lineTotal;

                $purchase->lines()->create([
                    'ingredient_id' => $line['ingredient_id'],
                    'quantity' => $line['quantity'],
                    'unit_cost' => $line['unit_cost'],
                    'line_total' => $lineTotal,
                ]);
            }

            $purchase->update(['total_cost' => round($total, 2)]);

            $this->audit->log('create', 'purchases', $purchase, null, [
                'supplier_id' => $supplierId,
                'total_cost' => $purchase->total_cost,
            ]);

            return $purchase->fresh('lines');
        });
    }

    /**
     * Receiving a purchase enters its ingredients into stock (one movement
     * per line) and updates each ingredient's cost to the latest purchase
     * price, per the Achat → Réception → Entrée stock workflow.
     */
    public function receive(Purchase $purchase, User $user): Purchase
    {
        if (! in_array($purchase->status, ['ordered', 'draft'], true)) {
            throw new DomainException('Cette commande ne peut pas être réceptionnée depuis son état actuel.');
        }

        return DB::transaction(function () use ($purchase, $user) {
            foreach ($purchase->lines as $line) {
                $this->stock->move(
                    $line->ingredient,
                    'purchase',
                    (float) $line->quantity,
                    $user,
                    null,
                    $purchase->reference ?? "Achat #{$purchase->id}",
                    (float) $line->unit_cost
                );

                $line->ingredient->update(['unit_cost' => $line->unit_cost]);
            }

            $purchase->update([
                'status' => 'received',
                'received_at' => now(),
                'received_by' => $user->id,
            ]);

            $this->audit->log('receive', 'purchases', $purchase, ['status' => 'ordered'], ['status' => 'received']);

            return $purchase->fresh();
        });
    }

    public function cancel(Purchase $purchase, User $user, string $reason): Purchase
    {
        if ($purchase->status === 'received') {
            throw new DomainException('Une commande déjà réceptionnée ne peut pas être annulée.');
        }

        $purchase->update(['status' => 'cancelled']);

        $this->audit->log('cancel', 'purchases', $purchase, null, ['status' => 'cancelled'], $reason);

        return $purchase->fresh();
    }
}
