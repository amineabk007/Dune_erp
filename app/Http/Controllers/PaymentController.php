<?php

namespace App\Http\Controllers;

use App\Http\Requests\RefundPaymentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CashSessionService;
use App\Services\PaymentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PaymentController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly CashSessionService $cashSessions,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:payments.create', only: ['store']),
            new Middleware('permission:payments.refund', only: ['refund']),
        ];
    }

    public function store(StorePaymentRequest $request, Order $order): RedirectResponse
    {
        $session = $this->cashSessions->currentOpenSession();

        if (! $session) {
            return back()->withErrors(['payment' => 'Aucune session de caisse ouverte. Ouvrez la caisse avant d\'encaisser.']);
        }

        try {
            $this->payments->recordPayment(
                $order,
                $session,
                $request->user(),
                $request->input('method'),
                (float) $request->input('amount'),
                $request->input('reference')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('status', 'Paiement enregistré.');
    }

    public function refund(RefundPaymentRequest $request, Payment $payment): RedirectResponse
    {
        try {
            $this->payments->refundPayment($payment, $request->user(), $request->input('reason'));
        } catch (DomainException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('status', 'Paiement remboursé.');
    }
}
