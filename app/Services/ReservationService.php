<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly OrderService $orders,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  int[]  $tableIds
     */
    public function create(
        User $user,
        int $customerId,
        Carbon $reservedAt,
        int $guests,
        array $tableIds,
        ?string $notes = null,
    ): Reservation {
        return DB::transaction(function () use ($user, $customerId, $reservedAt, $guests, $tableIds, $notes) {
            $this->assertNoConflict($tableIds, $reservedAt, null);

            $reservation = Reservation::create([
                'customer_id' => $customerId,
                'created_by' => $user->id,
                'reserved_at' => $reservedAt,
                'guests' => $guests,
                'status' => 'pending',
                'notes' => $notes,
            ]);

            $reservation->tables()->sync($tableIds);

            $this->audit->log('create', 'reservations', $reservation, null, [
                'reserved_at' => $reservedAt->toDateTimeString(),
                'tables' => $tableIds,
            ]);

            return $reservation;
        });
    }

    /**
     * @param  int[]  $tableIds
     */
    public function update(
        Reservation $reservation,
        Carbon $reservedAt,
        int $guests,
        array $tableIds,
        ?string $notes = null,
    ): Reservation {
        if (in_array($reservation->status, ['completed', 'cancelled', 'no_show'], true)) {
            throw new DomainException('Cette réservation est terminée et ne peut plus être modifiée.');
        }

        return DB::transaction(function () use ($reservation, $reservedAt, $guests, $tableIds, $notes) {
            $this->assertNoConflict($tableIds, $reservedAt, $reservation->id);

            $old = $reservation->only(['reserved_at', 'guests', 'notes']);

            $reservation->update([
                'reserved_at' => $reservedAt,
                'guests' => $guests,
                'notes' => $notes,
            ]);
            $reservation->tables()->sync($tableIds);

            $this->audit->log('update', 'reservations', $reservation, $old, $reservation->only(['reserved_at', 'guests', 'notes']));

            return $reservation->fresh();
        });
    }

    public function transition(Reservation $reservation, string $status): Reservation
    {
        $allowed = [
            'pending' => ['confirmed', 'cancelled', 'no_show'],
            'confirmed' => ['seated', 'cancelled', 'no_show'],
            'seated' => ['completed', 'cancelled'],
        ];

        if (! in_array($status, $allowed[$reservation->status] ?? [], true)) {
            throw new DomainException("Transition de statut invalide : {$reservation->status} → {$status}.");
        }

        $reservation = DB::transaction(function () use ($reservation, $status) {
            $old = $reservation->only(['status']);
            $reservation->update(['status' => $status]);

            if ($status === 'seated') {
                foreach ($reservation->tables as $table) {
                    if ($table->status === 'available') {
                        $table->update(['status' => 'reserved']);
                    }
                }
            }

            if (in_array($status, ['cancelled', 'no_show', 'completed'], true)) {
                foreach ($reservation->tables as $table) {
                    if ($table->status === 'reserved') {
                        $table->update(['status' => 'available']);
                    }
                }
            }

            $this->audit->log('update', 'reservations', $reservation, $old, ['status' => $status]);

            return $reservation->fresh();
        });

        if ($status === 'confirmed') {
            $this->notifications->reservationConfirmed($reservation->load('customer'));
        }

        return $reservation;
    }

    /**
     * Reservation → table → order: create the order for a seated reservation,
     * using its first assigned table and customer.
     */
    public function createOrderFromReservation(Reservation $reservation, User $user): Order
    {
        if ($reservation->status !== 'seated') {
            throw new DomainException('La réservation doit être "installée" avant de créer une commande.');
        }

        if ($reservation->order_id) {
            throw new DomainException('Cette réservation a déjà une commande associée.');
        }

        $table = $reservation->tables()->first();

        return DB::transaction(function () use ($reservation, $user, $table) {
            $order = $this->orders->createOrder($user, $table?->id, $reservation->customer_id, null);

            $reservation->update(['order_id' => $order->id]);

            return $order;
        });
    }

    /**
     * @param  int[]  $tableIds
     */
    private function assertNoConflict(array $tableIds, Carbon $reservedAt, ?int $excludeReservationId): void
    {
        if (empty($tableIds)) {
            throw new DomainException('Sélectionnez au moins une table.');
        }

        $windowStart = $reservedAt->clone()->subMinutes(Reservation::DURATION_MINUTES);
        $windowEnd = $reservedAt->clone()->addMinutes(Reservation::DURATION_MINUTES);

        $conflict = RestaurantTable::whereIn('restaurant_tables.id', $tableIds)
            ->join('reservation_tables', 'reservation_tables.table_id', '=', 'restaurant_tables.id')
            ->join('reservations', 'reservations.id', '=', 'reservation_tables.reservation_id')
            ->whereNotIn('reservations.status', ['cancelled', 'no_show', 'completed'])
            ->when($excludeReservationId, fn ($q) => $q->where('reservations.id', '!=', $excludeReservationId))
            ->whereBetween('reservations.reserved_at', [$windowStart, $windowEnd])
            ->exists();

        if ($conflict) {
            throw new DomainException('Une table sélectionnée est déjà réservée sur ce créneau.');
        }
    }
}
