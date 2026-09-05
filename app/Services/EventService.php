<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPayment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class EventService
{
    /**
     * Transitions allowed from each status, mirroring the reservation
     * lifecycle: pending -> confirmed -> completed, with cancellation
     * possible from either open state.
     */
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function __construct(private readonly AuditService $audit) {}

    public function create(User $user, array $data): Event
    {
        return DB::transaction(function () use ($user, $data) {
            $event = Event::create([...$data, 'created_by' => $user->id, 'status' => 'pending']);

            $this->audit->log('create', 'events', $event, null, $event->only(['name', 'event_date', 'total_amount']));

            return $event;
        });
    }

    public function update(Event $event, array $data): Event
    {
        return DB::transaction(function () use ($event, $data) {
            $old = $event->only(['name', 'event_date', 'guest_count', 'description', 'total_amount']);
            $event->update($data);

            $this->audit->log('update', 'events', $event, $old, $event->only([
                'name', 'event_date', 'guest_count', 'description', 'total_amount',
            ]));

            return $event;
        });
    }

    public function transition(Event $event, string $status, ?string $reason = null): Event
    {
        $allowed = self::TRANSITIONS[$event->status] ?? [];

        if (! in_array($status, $allowed, true)) {
            throw new DomainException("Impossible de passer de « {$event->status} » à « {$status} ».");
        }

        return DB::transaction(function () use ($event, $status, $reason) {
            $old = ['status' => $event->status];

            $event->status = $status;
            if ($status === 'cancelled') {
                $event->cancelled_at = now();
                $event->cancel_reason = $reason;
            }
            $event->save();

            $this->audit->log($status, 'events', $event, $old, ['status' => $status], $reason);

            return $event->fresh();
        });
    }

    public function recordPayment(Event $event, User $user, string $type, string $method, float $amount, ?string $reference = null): EventPayment
    {
        if ($event->status === 'cancelled') {
            throw new DomainException('Impossible d\'encaisser un paiement pour un événement annulé.');
        }

        if ($amount <= 0) {
            throw new DomainException('Le montant doit être positif.');
        }

        $balanceDue = round((float) $event->total_amount - (float) $event->amount_paid, 2);
        if ($amount > $balanceDue + 0.001) {
            throw new DomainException('Le montant dépasse le solde restant dû ('.number_format($balanceDue, 2).' DH).');
        }

        return DB::transaction(function () use ($event, $user, $type, $method, $amount, $reference) {
            $payment = EventPayment::create([
                'event_id' => $event->id,
                'type' => $type,
                'method' => $method,
                'amount' => $amount,
                'received_by' => $user->id,
                'reference' => $reference,
            ]);

            $event->amount_paid = round((float) $event->amount_paid + $amount, 2);
            $event->save();

            $this->audit->log('create', 'events', $payment, null, $payment->only(['type', 'method', 'amount']));

            return $payment;
        });
    }
}
