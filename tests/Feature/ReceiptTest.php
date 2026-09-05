<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CashSessionService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_order_receipt_is_printable_by_a_user_with_orders_view(): void
    {
        $caissier = User::factory()->create();
        $caissier->assignRole('caissier');

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 100]);
        $order = app(OrderService::class)->createOrder($caissier, null, null, null);
        app(OrderService::class)->addItem($order, $product, 1);

        $response = $this->actingAs($caissier)->get("/orders/{$order->id}/receipt");

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertSee('100.00');
    }

    public function test_a_role_without_orders_view_cannot_print_a_receipt(): void
    {
        $caissier = User::factory()->create();
        $caissier->assignRole('caissier');
        $order = app(OrderService::class)->createOrder($caissier, null, null, null);

        $comptable = User::factory()->create();
        $comptable->assignRole('comptable'); // no orders.view grant

        $this->actingAs($comptable)->get("/orders/{$order->id}/receipt")->assertForbidden();
    }

    public function test_cash_session_report_is_printable_by_a_user_with_cash_view(): void
    {
        $caissier = User::factory()->create();
        $caissier->assignRole('caissier');

        $session = app(CashSessionService::class)->open($caissier, 500);

        $response = $this->actingAs($caissier)->get("/cash-sessions/{$session->id}/report");

        $response->assertOk();
        $response->assertSee('500.00');
    }
}
