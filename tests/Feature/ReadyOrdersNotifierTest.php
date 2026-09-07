<?php

namespace Tests\Feature;

use App\Livewire\ReadyOrdersNotifier;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class ReadyOrdersNotifierTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    public function test_the_server_who_owns_the_order_is_alerted_when_their_item_becomes_ready(): void
    {
        $this->seedRolesAndPermissions();

        $server = User::factory()->create();
        $server->assignRole('serveur');

        $category = Category::factory()->create(['type' => 'food']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $orders = app(OrderService::class);
        $order = $orders->createOrder($server, null, null, null);
        $item = $orders->addItem($order, $product, 1);
        $orders->sendToProduction($order);

        $this->actingAs($server);

        $component = Livewire::test(ReadyOrdersNotifier::class);
        $component->assertNotDispatched('dune-notify');

        $orders->advanceItemStatus($item->fresh(), 'preparing');
        $orders->advanceItemStatus($item->fresh(), 'ready');

        $component->call('poll')->assertDispatched('dune-notify');
    }

    public function test_a_different_servers_ready_item_does_not_alert_this_server(): void
    {
        $this->seedRolesAndPermissions();

        $server = User::factory()->create();
        $server->assignRole('serveur');

        $otherServer = User::factory()->create();
        $otherServer->assignRole('serveur');

        $category = Category::factory()->create(['type' => 'food']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $orders = app(OrderService::class);
        $order = $orders->createOrder($otherServer, null, null, null);
        $item = $orders->addItem($order, $product, 1);
        $orders->sendToProduction($order);

        $this->actingAs($server);
        $component = Livewire::test(ReadyOrdersNotifier::class);

        $orders->advanceItemStatus($item->fresh(), 'preparing');
        $orders->advanceItemStatus($item->fresh(), 'ready');

        $component->call('poll')->assertNotDispatched('dune-notify');
    }

    public function test_a_role_without_orders_create_never_gets_ready_alerts(): void
    {
        $this->seedRolesAndPermissions();

        $cuisine = User::factory()->create();
        $cuisine->assignRole('cuisine');

        $this->actingAs($cuisine);

        Livewire::test(ReadyOrdersNotifier::class)
            ->call('poll')
            ->assertNotDispatched('dune-notify');
    }
}
