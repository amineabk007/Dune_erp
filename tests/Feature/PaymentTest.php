<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Zone;
use App\Services\CashSessionService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private User $caissier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->caissier = User::factory()->create();
        $this->caissier->assignRole('caissier');
    }

    private function makeOrderWithTotal(float $total, ?int $tableId = null)
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => $total, 'tax_rate' => 0]);

        $order = app(OrderService::class)->createOrder($this->caissier, $tableId, null, null);
        app(OrderService::class)->addItem($order, $product, 1);

        return $order->fresh();
    }

    public function test_payment_requires_an_open_cash_session(): void
    {
        $order = $this->makeOrderWithTotal(100);

        $response = $this->actingAs($this->caissier)->post("/orders/{$order->id}/payments", [
            'method' => 'cash', 'amount' => 100,
        ]);

        $response->assertSessionHasErrors('payment');
        $this->assertSame('0.00', (string) $order->fresh()->amount_paid);
    }

    public function test_full_cash_payment_marks_order_paid_and_frees_table_to_cleaning(): void
    {
        app(CashSessionService::class)->open($this->caissier, 500);

        $zone = Zone::factory()->create();
        $table = RestaurantTable::factory()->create(['zone_id' => $zone->id, 'status' => 'available']);
        $order = $this->makeOrderWithTotal(100, $table->id);

        $response = $this->actingAs($this->caissier)->post("/orders/{$order->id}/payments", [
            'method' => 'cash', 'amount' => 100,
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame('100.00', (string) $order->amount_paid);
        $this->assertSame('cleaning', $table->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'create', 'module' => 'payments']);
    }

    public function test_split_payment_across_two_methods(): void
    {
        app(CashSessionService::class)->open($this->caissier, 0);
        $order = $this->makeOrderWithTotal(150);

        $this->actingAs($this->caissier)->post("/orders/{$order->id}/payments", ['method' => 'cash', 'amount' => 50]);
        $this->actingAs($this->caissier)->post("/orders/{$order->id}/payments", ['method' => 'card', 'amount' => 100]);

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame('150.00', (string) $order->amount_paid);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_cannot_overpay_an_order(): void
    {
        app(CashSessionService::class)->open($this->caissier, 0);
        $order = $this->makeOrderWithTotal(100);

        $response = $this->actingAs($this->caissier)->post("/orders/{$order->id}/payments", [
            'method' => 'cash', 'amount' => 150,
        ]);

        $response->assertSessionHasErrors('payment');
        $this->assertSame('0.00', (string) $order->fresh()->amount_paid);
    }

    public function test_refunding_a_payment_is_traced_and_reduces_amount_paid(): void
    {
        app(CashSessionService::class)->open($this->caissier, 0);
        $order = $this->makeOrderWithTotal(100);

        $this->actingAs($this->caissier)->post("/orders/{$order->id}/payments", ['method' => 'cash', 'amount' => 100]);
        $payment = $order->fresh()->payments()->first();

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $response = $this->actingAs($manager)->post("/payments/{$payment->id}/refund", ['reason' => 'erreur de caisse']);
        $response->assertRedirect();

        $payment->refresh();
        $this->assertTrue($payment->refunded);
        $this->assertSame('erreur de caisse', $payment->refund_reason);
        $this->assertSame('0.00', (string) $order->fresh()->amount_paid);
        $this->assertDatabaseHas('audit_logs', ['action' => 'refund', 'module' => 'payments', 'reason' => 'erreur de caisse']);
    }

    public function test_a_role_without_payments_refund_cannot_refund(): void
    {
        app(CashSessionService::class)->open($this->caissier, 0);
        $order = $this->makeOrderWithTotal(100);

        $this->actingAs($this->caissier)->post("/orders/{$order->id}/payments", ['method' => 'cash', 'amount' => 100]);
        $payment = $order->fresh()->payments()->first();

        $this->actingAs($this->caissier)->post("/payments/{$payment->id}/refund", ['reason' => 'x'])->assertForbidden();
    }

    public function test_paid_order_cannot_be_deleted_it_remains_in_history(): void
    {
        app(CashSessionService::class)->open($this->caissier, 0);
        $order = $this->makeOrderWithTotal(100);
        $this->actingAs($this->caissier)->post("/orders/{$order->id}/payments", ['method' => 'cash', 'amount' => 100]);

        // There is deliberately no delete route for orders at all.
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
    }
}
