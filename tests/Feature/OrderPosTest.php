<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Zone;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class OrderPosTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private User $serveur;

    private RestaurantTable $table;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->serveur = User::factory()->create();
        $this->serveur->assignRole('serveur');

        $zone = Zone::factory()->create();
        $this->table = RestaurantTable::factory()->create(['zone_id' => $zone->id, 'status' => 'available']);

        $category = Category::factory()->create(['type' => 'food']);
        $this->product = Product::factory()->create(['category_id' => $category->id, 'price' => 100, 'tax_rate' => 20]);
    }

    public function test_creating_an_order_on_a_table_occupies_it(): void
    {
        $response = $this->actingAs($this->serveur)->post('/orders', ['table_id' => $this->table->id]);

        $response->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame($this->table->id, $order->table_id);
        $this->assertSame('occupied', $this->table->fresh()->status);
        $this->assertNotNull($order->order_number);
        $this->assertDatabaseHas('audit_logs', ['action' => 'create', 'module' => 'orders', 'record_id' => $order->id]);
    }

    public function test_cannot_open_an_order_on_an_occupied_table(): void
    {
        $this->table->update(['status' => 'occupied']);

        $response = $this->actingAs($this->serveur)->post('/orders', ['table_id' => $this->table->id]);

        $response->assertSessionHasErrors('table_id');
    }

    public function test_adding_items_recalculates_totals_server_side(): void
    {
        $order = app(OrderService::class)->createOrder($this->serveur, null, null, null);

        $this->actingAs($this->serveur)->post("/orders/{$order->id}/items", [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        $order->refresh();
        $this->assertSame('200.00', (string) $order->subtotal);
        $this->assertSame('40.00', (string) $order->tax_amount); // 20% of 200
        $this->assertSame('240.00', (string) $order->total);

        // client cannot override the total: no 'total' field is even accepted by the request.
        $item = $order->items()->first();
        $this->assertSame(2, $item->quantity);
        $this->assertSame('kitchen', $item->destination); // food category
    }

    public function test_a_manager_can_apply_a_discount_but_a_serveur_cannot(): void
    {
        $order = app(OrderService::class)->createOrder($this->serveur, null, null, null);
        app(OrderService::class)->addItem($order, $this->product, 1);

        $this->actingAs($this->serveur)
            ->post("/orders/{$order->id}/discount", ['amount' => 10, 'reason' => 'geste commercial'])
            ->assertForbidden();

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $response = $this->actingAs($manager)->post("/orders/{$order->id}/discount", [
            'amount' => 10, 'reason' => 'geste commercial',
        ]);
        $response->assertRedirect();

        $order->refresh();
        $this->assertSame('10.00', (string) $order->discount_amount);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'discount', 'module' => 'orders', 'record_id' => $order->id, 'reason' => 'geste commercial',
        ]);
    }

    public function test_cancelling_an_order_requires_a_reason_and_frees_the_table(): void
    {
        // Cancellation is a manager-level action per the role grants (accountability
        // control on a sensitive, audited action) — a plain serveur cannot do it.
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $order = app(OrderService::class)->createOrder($this->serveur, $this->table->id, null, null);

        $response = $this->actingAs($manager)->post("/orders/{$order->id}/cancel", []);
        $response->assertSessionHasErrors('reason');

        $response = $this->actingAs($manager)->post("/orders/{$order->id}/cancel", ['reason' => 'client parti']);
        $response->assertRedirect();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('client parti', $order->cancel_reason);
        $this->assertSame($manager->id, $order->cancelled_by);
        $this->assertSame('available', $this->table->fresh()->status);
    }

    public function test_a_paid_order_cannot_be_cancelled(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $order = app(OrderService::class)->createOrder($this->serveur, null, null, null);
        $order->update(['status' => 'paid', 'total' => 100, 'amount_paid' => 100]);

        $response = $this->actingAs($manager)->post("/orders/{$order->id}/cancel", ['reason' => 'x']);

        $response->assertSessionHasErrors('order');
        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_a_serveur_without_orders_cancel_cannot_cancel_an_order(): void
    {
        $order = app(OrderService::class)->createOrder($this->serveur, null, null, null);

        $this->actingAs($this->serveur)
            ->post("/orders/{$order->id}/cancel", ['reason' => 'x'])
            ->assertForbidden();
    }

    public function test_removing_a_new_item_is_allowed_but_a_sent_item_must_be_cancelled_instead(): void
    {
        $order = app(OrderService::class)->createOrder($this->serveur, null, null, null);
        $item = app(OrderService::class)->addItem($order, $this->product, 1);

        $this->actingAs($this->serveur)->delete("/orders/{$order->id}/items/{$item->id}")->assertRedirect();
        $this->assertDatabaseMissing('order_items', ['id' => $item->id]);

        $item2 = app(OrderService::class)->addItem($order, $this->product, 1);
        app(OrderService::class)->sendToProduction($order);

        $response = $this->actingAs($this->serveur)->delete("/orders/{$order->id}/items/{$item2->id}");
        $response->assertSessionHasErrors('item');
    }
}
