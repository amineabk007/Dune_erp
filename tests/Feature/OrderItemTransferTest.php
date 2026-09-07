<?php

namespace Tests\Feature;

use App\Livewire\OrderBuilder;
use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Zone;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class OrderItemTransferTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    public function test_manager_can_transfer_an_item_to_another_table_with_an_open_order(): void
    {
        $this->seedRolesAndPermissions();

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $zone = Zone::factory()->create();
        $tableA = RestaurantTable::factory()->create(['zone_id' => $zone->id, 'name' => 'T1', 'status' => 'available']);
        $tableB = RestaurantTable::factory()->create(['zone_id' => $zone->id, 'name' => 'T2', 'status' => 'available']);

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 50]);

        $orders = app(OrderService::class);
        $orderA = $orders->createOrder($manager, $tableA->id, null, null);
        $item = $orders->addItem($orderA, $product, 1);
        $orderB = $orders->createOrder($manager, $tableB->id, null, null);
        $orders->addItem($orderB, $product, 1);

        $this->actingAs($manager);

        Livewire::test(OrderBuilder::class, ['order' => $orderA])
            ->set("transferTargets.{$item->id}", $tableB->id)
            ->call('transferItem', $item->id);

        $this->assertSame($orderB->id, $item->fresh()->order_id);
        $this->assertSame(0.0, (float) $orderA->fresh()->subtotal);
        $this->assertSame(100.0, (float) $orderB->fresh()->subtotal);
    }

    public function test_a_server_without_transfer_item_permission_cannot_transfer_an_item(): void
    {
        $this->seedRolesAndPermissions();

        $serveur = User::factory()->create();
        $serveur->assignRole('serveur');

        $zone = Zone::factory()->create();
        $tableA = RestaurantTable::factory()->create(['zone_id' => $zone->id, 'name' => 'T1', 'status' => 'available']);
        $tableB = RestaurantTable::factory()->create(['zone_id' => $zone->id, 'name' => 'T2', 'status' => 'available']);

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 50]);

        $orders = app(OrderService::class);
        $orderA = $orders->createOrder($serveur, $tableA->id, null, null);
        $item = $orders->addItem($orderA, $product, 1);
        $orders->createOrder($serveur, $tableB->id, null, null);

        $this->actingAs($serveur);

        Livewire::test(OrderBuilder::class, ['order' => $orderA])
            ->set("transferTargets.{$item->id}", $tableB->id)
            ->call('transferItem', $item->id)
            ->assertSet('error', "Vous n'avez pas la permission d'effectuer cette action.");

        $this->assertSame($orderA->id, $item->fresh()->order_id);
    }

    public function test_transferring_to_a_table_without_an_open_order_fails(): void
    {
        $this->seedRolesAndPermissions();

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $zone = Zone::factory()->create();
        $tableA = RestaurantTable::factory()->create(['zone_id' => $zone->id, 'name' => 'T1', 'status' => 'available']);
        $tableB = RestaurantTable::factory()->create(['zone_id' => $zone->id, 'name' => 'T2', 'status' => 'available']);

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 50]);

        $orders = app(OrderService::class);
        $orderA = $orders->createOrder($manager, $tableA->id, null, null);
        $item = $orders->addItem($orderA, $product, 1);

        $this->actingAs($manager);

        Livewire::test(OrderBuilder::class, ['order' => $orderA])
            ->set("transferTargets.{$item->id}", $tableB->id)
            ->call('transferItem', $item->id)
            ->assertSet('error', "La table de destination n'a pas de commande ouverte.");

        $this->assertSame($orderA->id, $item->fresh()->order_id);
    }
}
