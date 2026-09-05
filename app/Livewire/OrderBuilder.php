<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Services\CashSessionService;
use App\Services\OrderService;
use App\Services\PaymentService;
use DomainException;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OrderBuilder extends Component
{
    public Order $order;

    public string $search = '';

    public ?int $categoryFilter = null;

    public ?string $error = null;

    public ?string $status = null;

    // Discount form
    public string $discountAmount = '';

    public string $discountReason = '';

    // Payment form
    public string $paymentMethod = 'cash';

    public string $paymentAmount = '';

    public string $paymentReference = '';

    // Cancel form
    public string $cancelReason = '';

    // Refund reasons keyed by payment id
    public array $refundReasons = [];

    public function mount(Order $order): void
    {
        $this->order = $order;
        $this->discountAmount = (string) $order->discount_amount;
        $this->paymentAmount = $order->balanceDue();
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->with('category')
            ->orderBy('name')
            ->limit(60)
            ->get();
    }

    #[Computed]
    public function items(): Collection
    {
        return $this->order->items()->with('product')->orderBy('id')->get();
    }

    #[Computed]
    public function currentSession()
    {
        return app(CashSessionService::class)->currentOpenSession();
    }

    #[Computed]
    public function payments(): Collection
    {
        return $this->order->payments()->with('receivedBy')->latest()->get();
    }

    public function addProduct(int $productId): void
    {
        if (! $this->authorizeAction('orders.update')) {
            return;
        }

        $product = Product::with('category')->findOrFail($productId);

        try {
            app(OrderService::class)->addItem($this->order, $product, 1);
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }

        $this->refreshOrder();
    }

    public function incrementQuantity(int $itemId): void
    {
        $this->changeQuantity($itemId, 1);
    }

    public function decrementQuantity(int $itemId): void
    {
        $this->changeQuantity($itemId, -1);
    }

    private function changeQuantity(int $itemId, int $delta): void
    {
        if (! $this->authorizeAction('orders.update')) {
            return;
        }

        $item = OrderItem::where('order_id', $this->order->id)->findOrFail($itemId);
        $newQuantity = $item->quantity + $delta;

        try {
            $service = app(OrderService::class);

            if ($newQuantity < 1) {
                $service->removeItem($item);
            } else {
                $service->updateItemQuantity($item, $newQuantity);
            }
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }

        $this->refreshOrder();
    }

    public function applyDiscount(): void
    {
        if (! $this->authorizeAction('orders.discount')) {
            return;
        }

        try {
            app(OrderService::class)->applyDiscount(
                $this->order,
                auth()->user(),
                (float) ($this->discountAmount ?: 0),
                (string) $this->discountReason
            );
            $this->status = 'Remise appliquée.';
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }

        $this->refreshOrder();
    }

    public function sendToProduction(): void
    {
        if (! $this->authorizeAction('orders.update')) {
            return;
        }

        try {
            app(OrderService::class)->sendToProduction($this->order);
            $this->status = 'Commande envoyée en cuisine/bar.';
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }

        $this->refreshOrder();
    }

    public function markServed(): void
    {
        if (! $this->authorizeAction('orders.update')) {
            return;
        }

        try {
            app(OrderService::class)->markServed($this->order);
            $this->status = 'Commande marquée servie.';
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }

        $this->refreshOrder();
    }

    public function cancelOrder(): void
    {
        if (! $this->authorizeAction('orders.cancel')) {
            return;
        }

        if (trim($this->cancelReason) === '') {
            $this->error = "Un motif d'annulation est requis.";

            return;
        }

        try {
            app(OrderService::class)->cancelOrder($this->order, auth()->user(), $this->cancelReason);
            $this->status = 'Commande annulée.';
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }

        $this->refreshOrder();
    }

    public function recordPayment(): void
    {
        if (! $this->authorizeAction('payments.create')) {
            return;
        }

        $session = app(CashSessionService::class)->currentOpenSession();

        if (! $session) {
            $this->error = 'Aucune session de caisse ouverte.';

            return;
        }

        try {
            app(PaymentService::class)->recordPayment(
                $this->order,
                $session,
                auth()->user(),
                $this->paymentMethod,
                (float) ($this->paymentAmount ?: 0),
                $this->paymentReference ?: null
            );
            $this->status = 'Paiement enregistré.';
            $this->paymentAmount = '';
            $this->paymentReference = '';
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }

        $this->refreshOrder();
    }

    public function refundPayment(int $paymentId): void
    {
        if (! $this->authorizeAction('payments.refund')) {
            return;
        }

        $reason = trim($this->refundReasons[$paymentId] ?? '');

        if ($reason === '') {
            $this->error = 'Un motif de remboursement est requis.';

            return;
        }

        $payment = Payment::where('order_id', $this->order->id)->findOrFail($paymentId);

        try {
            app(PaymentService::class)->refundPayment($payment, auth()->user(), $reason);
            $this->status = 'Paiement remboursé.';
            unset($this->refundReasons[$paymentId]);
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }

        $this->refreshOrder();
    }

    private function authorizeAction(string $permission): bool
    {
        $this->error = null;

        if (! auth()->user()->can($permission)) {
            $this->error = "Vous n'avez pas la permission d'effectuer cette action.";

            return false;
        }

        return true;
    }

    private function refreshOrder(): void
    {
        $this->order->refresh();
        $this->paymentAmount = $this->order->balanceDue();
        unset($this->items, $this->currentSession, $this->payments);
    }

    public function render()
    {
        return view('livewire.order-builder');
    }
}
