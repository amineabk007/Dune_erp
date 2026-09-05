<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyDiscountRequest;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Services\OrderService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class OrderController extends Controller implements HasMiddleware
{
    public function __construct(private readonly OrderService $orders) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.view', only: ['index', 'show']),
            new Middleware('permission:orders.create', only: ['create', 'store']),
            new Middleware('permission:orders.update', only: ['send', 'serve']),
            new Middleware('permission:orders.discount', only: ['discount']),
            new Middleware('permission:orders.cancel', only: ['cancel']),
        ];
    }

    public function index(Request $request): View
    {
        $orders = Order::with('table', 'server')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when(! $request->filled('status'), fn ($q) => $q->whereNotIn('status', ['paid', 'cancelled']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('orders.index', compact('orders'));
    }

    public function create(): View
    {
        return view('orders.create', [
            'tables' => RestaurantTable::whereIn('status', ['available', 'reserved'])->with('zone')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        try {
            $order = $this->orders->createOrder(
                $request->user(),
                $request->input('table_id') ? (int) $request->input('table_id') : null,
                $request->input('customer_id') ? (int) $request->input('customer_id') : null,
                $request->input('notes')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['table_id' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('status', 'Commande créée.');
    }

    public function show(Order $order): View
    {
        return view('orders.show', [
            'order' => $order->load('items.product', 'table', 'customer', 'server'),
        ]);
    }

    public function discount(ApplyDiscountRequest $request, Order $order): RedirectResponse
    {
        try {
            $this->orders->applyDiscount(
                $order,
                $request->user(),
                (float) $request->input('amount'),
                $request->input('reason')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('status', 'Remise appliquée.');
    }

    public function send(Order $order): RedirectResponse
    {
        try {
            $this->orders->sendToProduction($order);
        } catch (DomainException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Commande envoyée en cuisine/bar.');
    }

    public function serve(Order $order): RedirectResponse
    {
        try {
            $this->orders->markServed($order);
        } catch (DomainException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Commande marquée servie.');
    }

    public function cancel(CancelOrderRequest $request, Order $order): RedirectResponse
    {
        try {
            $this->orders->cancelOrder($order, $request->user(), $request->input('reason'));
        } catch (DomainException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()->route('orders.index')->with('status', 'Commande annulée.');
    }
}
