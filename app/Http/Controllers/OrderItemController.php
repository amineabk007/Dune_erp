<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddOrderItemRequest;
use App\Http\Requests\UpdateOrderItemRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderItemController extends Controller implements HasMiddleware
{
    public function __construct(private readonly OrderService $orders) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.update'),
        ];
    }

    public function store(AddOrderItemRequest $request, Order $order): RedirectResponse
    {
        $product = Product::with('category')->findOrFail($request->input('product_id'));

        try {
            $this->orders->addItem(
                $order,
                $product,
                (int) $request->input('quantity'),
                $request->input('notes'),
                $request->input('kitchen_note')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['product_id' => $e->getMessage()]);
        }

        return back()->with('status', 'Article ajouté.');
    }

    public function update(UpdateOrderItemRequest $request, Order $order, OrderItem $item): RedirectResponse
    {
        $this->assertBelongsToOrder($order, $item);

        try {
            $this->orders->updateItemQuantity($item, (int) $request->input('quantity'));
        } catch (DomainException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('status', 'Article mis à jour.');
    }

    public function destroy(Order $order, OrderItem $item): RedirectResponse
    {
        $this->assertBelongsToOrder($order, $item);

        try {
            $this->orders->removeItem($item);
        } catch (DomainException $e) {
            return back()->withErrors(['item' => $e->getMessage()]);
        }

        return back()->with('status', 'Article retiré.');
    }

    public function cancel(Request $request, Order $order, OrderItem $item): RedirectResponse
    {
        $this->assertBelongsToOrder($order, $item);

        $request->validate(['reason' => ['required', 'string', 'max:255']]);

        try {
            $this->orders->cancelItem($item, $request->input('reason'));
        } catch (DomainException $e) {
            return back()->withErrors(['item' => $e->getMessage()]);
        }

        return back()->with('status', 'Article annulé.');
    }

    private function assertBelongsToOrder(Order $order, OrderItem $item): void
    {
        if ($item->order_id !== $order->id) {
            throw new NotFoundHttpException;
        }
    }
}
