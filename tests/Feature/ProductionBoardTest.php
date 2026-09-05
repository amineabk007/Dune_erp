<?php

namespace Tests\Feature;

use App\Livewire\ProductionBoard;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class ProductionBoardTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    public function test_kitchen_screen_lists_sent_food_items_and_can_advance_them(): void
    {
        $this->seedRolesAndPermissions();

        $cuisine = User::factory()->create();
        $cuisine->assignRole('cuisine');

        $server = User::factory()->create();
        $server->assignRole('serveur');

        $category = Category::factory()->create(['type' => 'food']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $orders = app(OrderService::class);
        $order = $orders->createOrder($server, null, null, null);
        $item = $orders->addItem($order, $product, 1);
        $orders->sendToProduction($order);

        $this->actingAs($cuisine);

        Livewire::test(ProductionBoard::class, ['destination' => 'kitchen'])
            ->assertSee($product->name)
            ->call('advance', $item->id, 'preparing')
            ->assertSee('preparing');

        $this->assertSame('preparing', $item->fresh()->status);
    }

    public function test_bar_screen_does_not_show_kitchen_items(): void
    {
        $this->seedRolesAndPermissions();

        $bar = User::factory()->create();
        $bar->assignRole('bar');

        $server = User::factory()->create();
        $server->assignRole('serveur');

        $category = Category::factory()->create(['type' => 'food']);
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => 'Tajine Test']);

        $orders = app(OrderService::class);
        $order = $orders->createOrder($server, null, null, null);
        $orders->addItem($order, $product, 1);
        $orders->sendToProduction($order);

        $this->actingAs($bar);

        Livewire::test(ProductionBoard::class, ['destination' => 'bar'])
            ->assertDontSee('Tajine Test');
    }

    public function test_a_role_without_kitchen_view_cannot_access_the_kitchen_screen(): void
    {
        $this->seedRolesAndPermissions();

        $comptable = User::factory()->create();
        $comptable->assignRole('comptable');

        $this->actingAs($comptable)->get('/kitchen')->assertForbidden();
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $this->seedRolesAndPermissions();

        $cuisine = User::factory()->create();
        $cuisine->assignRole('cuisine');

        $server = User::factory()->create();
        $server->assignRole('serveur');

        $category = Category::factory()->create(['type' => 'food']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $orders = app(OrderService::class);
        $order = $orders->createOrder($server, null, null, null);
        $item = $orders->addItem($order, $product, 1); // still 'new', not sent

        $this->actingAs($cuisine);

        Livewire::test(ProductionBoard::class, ['destination' => 'kitchen'])
            ->call('advance', $item->id, 'preparing');

        $this->assertSame('new', $item->fresh()->status);
    }
}
