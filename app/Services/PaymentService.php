<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly StockService $stock,
    ) {}

    public function recordPayment(
        Order $order,
        CashSession $session,
        User $user,
        string $method,
        float $amount,
        ?string $reference = null,
    ): Payment {
        if (in_array($order->status, ['cancelled'], true)) {
            throw new DomainException('Impossible de payer une commande annulée.');
        }

        if (! $session->isOpen()) {
            throw new DomainException('Aucune session de caisse ouverte.');
        }

        if ($amount <= 0) {
            throw new DomainException('Le montant du paiement doit être positif.');
        }

        $balanceDue = round((float) $order->total - (float) $order->amount_paid, 2);

        if ($amount > $balanceDue + 0.001) {
            throw new DomainException('Le montant dépasse le solde restant dû ('.number_format($balanceDue, 2).' DH).');
        }

        return DB::transaction(function () use ($order, $session, $user, $method, $amount, $reference) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'cash_session_id' => $session->id,
                'method' => $method,
                'amount' => $amount,
                'received_by' => $user->id,
                'reference' => $reference,
            ]);

            $wasAlreadyPaid = $order->status === 'paid';

            $newAmountPaid = round((float) $order->amount_paid + $amount, 2);
            $order->amount_paid = $newAmountPaid;

            if ($newAmountPaid >= (float) $order->total - 0.001) {
                $order->status = 'paid';
            }

            $order->save();

            if ($order->status === 'paid' && $order->table_id) {
                $order->table->update(['status' => 'cleaning']);
            }

            // Ingredients are only consumed once, the moment the sale is finalized.
            if ($order->status === 'paid' && ! $wasAlreadyPaid) {
                $this->stock->consumeForOrder($order, $user);
            }

            $this->audit->log('create', 'payments', $payment, null, $payment->only(['method', 'amount', 'order_id']));

            return $payment;
        });
    }

    public function refundPayment(Payment $payment, User $user, string $reason): Payment
    {
        if ($payment->refunded) {
            throw new DomainException('Ce paiement a déjà été remboursé.');
        }

        return DB::transaction(function () use ($payment, $user, $reason) {
            $payment->update([
                'refunded' => true,
                'refunded_at' => now(),
                'refunded_by' => $user->id,
                'refund_reason' => $reason,
            ]);

            $order = $payment->order;
            $newAmountPaid = max(0, round((float) $order->amount_paid - (float) $payment->amount, 2));
            $order->amount_paid = $newAmountPaid;

            if ($order->status === 'paid' && $newAmountPaid < (float) $order->total - 0.001) {
                $order->status = 'served';
            }

            $order->save();

            $this->audit->log(
                'refund',
                'payments',
                $payment,
                ['refunded' => false],
                ['refunded' => true, 'amount' => $payment->amount],
                $reason
            );

            return $payment->fresh();
        });
    }
}
