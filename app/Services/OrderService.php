<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private readonly AuditService $audit) {}

    public function createOrder(User $user, ?int $tableId, ?int $customerId, ?string $notes): Order
    {
        return DB::transaction(function () use ($user, $tableId, $customerId, $notes) {
            if ($tableId) {
                $table = RestaurantTable::lockForUpdate()->findOrFail($tableId);
                if (! in_array($table->status, ['available', 'reserved'], true)) {
                    throw new DomainException("Cette table n'est pas disponible.");
                }
            }

            $order = Order::create([
                'table_id' => $tableId,
                'customer_id' => $customerId,
                'user_id' => $user->id,
                'status' => 'open',
                'notes' => $notes,
            ]);

            $order->order_number = 'CMD-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
            $order->save();

            if ($tableId) {
                $table->update(['status' => 'occupied']);
            }

            $this->audit->log('create', 'orders', $order, null, ['table_id' => $tableId, 'customer_id' => $customerId]);

            return $order;
        });
    }

    public function addItem(Order $order, Product $product, int $quantity, ?string $notes = null, ?string $kitchenNote = null): OrderItem
    {
        $this->assertEditable($order);

        if ($quantity < 1) {
            throw new DomainException('La quantité doit être au moins 1.');
        }

        return DB::transaction(function () use ($order, $product, $quantity, $notes, $kitchenNote) {
            $destination = match ($product->category->type) {
                'food' => 'kitchen',
                'drink' => 'bar',
                default => 'none',
            };

            $item = $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $product->price,
                'tax_rate' => $product->tax_rate,
                'destination' => $destination,
                'quantity' => $quantity,
                'line_total' => round((float) $product->price * $quantity, 2),
                'notes' => $notes,
                'kitchen_note' => $kitchenNote,
                'status' => 'new',
            ]);

            $this->recalculateTotals($order->fresh());

            return $item;
        });
    }

    public function updateItemQuantity(OrderItem $item, int $quantity): OrderItem
    {
        $order = $item->order;
        $this->assertEditable($order);

        if ($quantity < 1) {
            throw new DomainException('La quantité doit être au moins 1.');
        }

        return DB::transaction(function () use ($item, $order, $quantity) {
            $item->update([
                'quantity' => $quantity,
                'line_total' => round((float) $item->unit_price * $quantity, 2),
            ]);

            $this->recalculateTotals($order->fresh());

            return $item->fresh();
        });
    }

    public function removeItem(OrderItem $item): void
    {
        $order = $item->order;
        $this->assertEditable($order);

        if ($item->status !== 'new') {
            throw new DomainException('Cet article a déjà été envoyé ; annulez-le au lieu de le supprimer.');
        }

        DB::transaction(function () use ($item, $order) {
            $item->delete();
            $this->recalculateTotals($order->fresh());
        });
    }

    public function cancelItem(OrderItem $item, string $reason): OrderItem
    {
        $order = $item->order;
        $this->assertEditable($order);

        return DB::transaction(function () use ($item, $order, $reason) {
            $item->update([
                'status' => 'cancelled',
                'status_changed_at' => now(),
                'status_changed_by' => auth()->id(),
            ]);

            $this->recalculateTotals($order->fresh());

            $this->audit->log('cancel', 'order_items', $item, null, null, $reason);

            return $item->fresh();
        });
    }

    /**
     * Kitchen/bar screens moving an item through its own lifecycle
     * (sent → preparing → ready → served), independent of the order's
     * overall status which the server manages from the POS screen.
     */
    public function advanceItemStatus(OrderItem $item, string $status): OrderItem
    {
        $allowed = [
            'sent' => ['preparing'],
            'preparing' => ['ready'],
            'ready' => ['served'],
        ];

        if ($item->order->status === 'cancelled') {
            throw new DomainException('Cette commande est annulée.');
        }

        if (! in_array($status, $allowed[$item->status] ?? [], true)) {
            throw new DomainException("Transition invalide : {$item->status} → {$status}.");
        }

        $item->update([
            'status' => $status,
            'status_changed_at' => now(),
            'status_changed_by' => auth()->id(),
        ]);

        return $item->fresh();
    }

    public function recalculateTotals(Order $order): Order
    {
        $items = $order->items()->where('status', '!=', 'cancelled')->get();

        $subtotal = round((float) $items->sum('line_total'), 2);
        $taxAmount = round($items->sum(fn (OrderItem $i) => (float) $i->line_total * ((float) $i->tax_rate / 100)), 2);
        $discount = min((float) $order->discount_amount, $subtotal);
        $total = round($subtotal - $discount + $taxAmount, 2);

        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discount,
            'total' => $total,
        ]);

        return $order->fresh();
    }

    public function applyDiscount(Order $order, User $user, float $amount, string $reason): Order
    {
        $this->assertEditable($order);

        if ($amount < 0) {
            throw new DomainException('La remise ne peut pas être négative.');
        }

        return DB::transaction(function () use ($order, $user, $amount, $reason) {
            $old = $order->only(['discount_amount', 'discount_reason']);

            $order->update([
                'discount_amount' => $amount,
                'discount_reason' => $reason,
                'discount_by' => $user->id,
            ]);

            $order = $this->recalculateTotals($order->fresh());

            $this->audit->log(
                'discount',
                'orders',
                $order,
                $old,
                $order->only(['discount_amount', 'discount_reason']),
                $reason
            );

            return $order;
        });
    }

    public function sendToProduction(Order $order): Order
    {
        $this->assertEditable($order);

        return DB::transaction(function () use ($order) {
            $order->items()->where('status', 'new')->update([
                'status' => 'sent',
                'status_changed_at' => now(),
                'status_changed_by' => auth()->id(),
            ]);

            if ($order->status === 'open') {
                $order->update(['status' => 'sent']);
            }

            return $order->fresh();
        });
    }

    public function markServed(Order $order): Order
    {
        if (! in_array($order->status, ['sent', 'preparing', 'ready'], true)) {
            throw new DomainException('Cette commande ne peut pas être marquée servie depuis son état actuel.');
        }

        $order->update(['status' => 'served']);

        return $order->fresh();
    }

    public function cancelOrder(Order $order, User $user, string $reason): Order
    {
        if ($order->status === 'paid') {
            throw new DomainException('Une commande payée ne peut pas être annulée ; utilisez un remboursement.');
        }
        if ($order->status === 'cancelled') {
            throw new DomainException('Cette commande est déjà annulée.');
        }

        return DB::transaction(function () use ($order, $user, $reason) {
            $old = $order->only(['status']);

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'cancel_reason' => $reason,
            ]);

            if ($order->table_id) {
                $order->table->update(['status' => 'available']);
            }

            $this->audit->log('cancel', 'orders', $order, $old, ['status' => 'cancelled'], $reason);

            return $order->fresh();
        });
    }

    /**
     * Move an open order from its current table to another available one
     * (e.g. seating a party at a bigger table).
     */
    public function transferTable(Order $order, RestaurantTable $newTable): Order
    {
        $this->assertEditable($order);

        if (! in_array($newTable->status, ['available', 'reserved'], true)) {
            throw new DomainException("La table de destination n'est pas disponible.");
        }

        return DB::transaction(function () use ($order, $newTable) {
            $oldTable = $order->table;

            $order->update(['table_id' => $newTable->id]);
            $newTable->update(['status' => 'occupied']);

            if ($oldTable) {
                $oldTable->update(['status' => 'available']);
            }

            $this->audit->log('update', 'orders', $order, ['table_id' => $oldTable?->id], ['table_id' => $newTable->id]);

            return $order->fresh();
        });
    }

    private function assertEditable(Order $order): void
    {
        if (in_array($order->status, ['paid', 'cancelled'], true)) {
            throw new DomainException('Cette commande ne peut plus être modifiée.');
        }
    }
}
