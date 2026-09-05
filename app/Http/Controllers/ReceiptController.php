<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Order;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ReceiptController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.view', only: ['order']),
            new Middleware('permission:cash.view', only: ['cashSession']),
        ];
    }

    /**
     * Printable "addition" / receipt for a single order.
     */
    public function order(Order $order): View
    {
        return view('receipts.order', [
            'order' => $order->load('items', 'table', 'customer', 'server', 'payments.receivedBy'),
        ]);
    }

    /**
     * Printable cash session report ("rapport de caisse").
     */
    public function cashSession(CashSession $cashSession): View
    {
        return view('receipts.cash-session', [
            'session' => $cashSession->load('openedBy', 'closedBy', 'movements.user', 'payments.order'),
        ]);
    }
}
