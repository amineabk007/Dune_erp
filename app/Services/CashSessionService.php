<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CashSessionService
{
    public function __construct(private readonly AuditService $audit) {}

    public function currentOpenSession(): ?CashSession
    {
        return CashSession::where('status', 'open')->latest('opened_at')->first();
    }

    public function open(User $user, float $openingCash): CashSession
    {
        if ($this->currentOpenSession()) {
            throw new DomainException('Une session de caisse est déjà ouverte.');
        }

        return DB::transaction(function () use ($user, $openingCash) {
            $session = CashSession::create([
                'opened_by' => $user->id,
                'opened_at' => now(),
                'opening_cash' => $openingCash,
                'status' => 'open',
            ]);

            $this->audit->log('open', 'cash', $session, null, $session->only(['opening_cash', 'opened_by']));

            return $session;
        });
    }

    public function recordMovement(CashSession $session, User $user, string $type, float $amount, string $reason): CashMovement
    {
        if (! $session->isOpen()) {
            throw new DomainException('Cette session de caisse est fermée.');
        }

        if ($amount <= 0) {
            throw new DomainException('Le montant doit être positif.');
        }

        return DB::transaction(function () use ($session, $user, $type, $amount, $reason) {
            $movement = CashMovement::create([
                'cash_session_id' => $session->id,
                'type' => $type,
                'amount' => $amount,
                'reason' => $reason,
                'user_id' => $user->id,
            ]);

            $this->audit->log('movement', 'cash', $movement, null, $movement->only(['type', 'amount', 'reason']));

            return $movement;
        });
    }

    /**
     * Close the session, computing the expected cash drawer amount from the
     * opening float, cash payments (minus refunded cash), and cash movements,
     * then recording the counted amount and the resulting difference.
     */
    public function close(CashSession $session, User $user, float $countedCash, ?string $notes = null): CashSession
    {
        if (! $session->isOpen()) {
            throw new DomainException('Cette session de caisse est déjà fermée.');
        }

        return DB::transaction(function () use ($session, $user, $countedCash, $notes) {
            $cashPayments = (float) $session->payments()
                ->where('method', 'cash')
                ->where('refunded', false)
                ->sum('amount');

            $cashIn = (float) $session->movements()->where('type', 'cash_in')->sum('amount');
            $cashOut = (float) $session->movements()->where('type', 'cash_out')->sum('amount');

            $expected = (float) $session->opening_cash + $cashPayments + $cashIn - $cashOut;
            $difference = $countedCash - $expected;

            $old = $session->only(['status', 'expected_cash', 'counted_cash', 'difference']);

            $session->update([
                'closed_by' => $user->id,
                'closed_at' => now(),
                'expected_cash' => round($expected, 2),
                'counted_cash' => round($countedCash, 2),
                'difference' => round($difference, 2),
                'status' => 'closed',
                'notes' => $notes,
            ]);

            $this->audit->log(
                'close',
                'cash',
                $session,
                $old,
                $session->only(['status', 'expected_cash', 'counted_cash', 'difference'])
            );

            return $session->fresh();
        });
    }
}
