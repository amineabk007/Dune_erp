<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\User;
use App\Services\CashSessionService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class RecipeManagementTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private User $stockUser;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->stockUser = User::factory()->create();
        $this->stockUser->assignRole('stock');

        $category = Category::factory()->create();
        $this->product = Product::factory()->create(['category_id' => $category->id, 'price' => 130]);
    }

    public function test_creating_a_recipe_with_ingredients_and_computing_food_cost(): void
    {
        $flour = Ingredient::factory()->create(['unit_cost' => 6, 'unit' => 'kg']);
        $chicken = Ingredient::factory()->create(['unit_cost' => 40, 'unit' => 'kg']);

        $response = $this->actingAs($this->stockUser)->post('/recipes', [
            'product_id' => $this->product->id,
            'yield_quantity' => 2,
            'ingredient_id' => [$flour->id, $chicken->id],
            'quantity' => [0.5, 0.3], // 0.5kg flour + 0.3kg chicken per batch of 2 portions
        ]);

        $response->assertRedirect();

        $recipe = Recipe::where('product_id', $this->product->id)->firstOrFail();
        $this->assertCount(2, $recipe->items);

        // batch cost = 0.5*6 + 0.3*40 = 3 + 12 = 15; per portion (yield 2) = 7.5
        $this->assertSame(7.5, $recipe->foodCostPerUnit());
    }

    public function test_a_role_without_recipes_manage_cannot_create_recipes(): void
    {
        $serveur = User::factory()->create();
        $serveur->assignRole('serveur');

        $ingredient = Ingredient::factory()->create();

        $this->actingAs($serveur)->post('/recipes', [
            'product_id' => $this->product->id,
            'yield_quantity' => 1,
            'ingredient_id' => [$ingredient->id],
            'quantity' => [1],
        ])->assertForbidden();
    }

    public function test_selling_a_recipe_linked_product_consumes_stock_on_payment(): void
    {
        $flour = Ingredient::factory()->create(['name' => 'Farine test', 'current_stock' => 100, 'unit' => 'kg']);

        $recipe = Recipe::factory()->create(['product_id' => $this->product->id, 'yield_quantity' => 1]);
        $recipe->items()->create(['ingredient_id' => $flour->id, 'quantity' => 0.2]);

        $caissier = User::factory()->create();
        $caissier->assignRole('caissier');

        app(CashSessionService::class)->open($caissier, 0);

        $order = app(OrderService::class)->createOrder($caissier, null, null, null);
        app(OrderService::class)->addItem($order, $this->product, 3); // 3 portions sold

        $this->actingAs($caissier)->post("/orders/{$order->id}/payments", [
            'method' => 'cash',
            'amount' => (string) $order->fresh()->total,
        ])->assertRedirect();

        // 3 portions * 0.2kg flour each = 0.6kg consumed
        $this->assertSame('99.400', (string) $flour->fresh()->current_stock);
        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $flour->id,
            'type' => 'sale_consumption',
            'quantity' => '-0.600',
        ]);
    }

    public function test_cancelled_items_do_not_consume_stock(): void
    {
        $flour = Ingredient::factory()->create(['current_stock' => 100]);
        $recipe = Recipe::factory()->create(['product_id' => $this->product->id, 'yield_quantity' => 1]);
        $recipe->items()->create(['ingredient_id' => $flour->id, 'quantity' => 1]);

        $caissier = User::factory()->create();
        $caissier->assignRole('caissier');
        app(CashSessionService::class)->open($caissier, 0);

        $orders = app(OrderService::class);
        $order = $orders->createOrder($caissier, null, null, null);
        $item = $orders->addItem($order, $this->product, 1);
        $orders->sendToProduction($order);
        $orders->cancelItem($item, 'client a changé d\'avis');

        // Order total is now 0 (only item was cancelled) - add a second, non-recipe item to allow payment.
        $otherCategory = Category::factory()->create();
        $otherProduct = Product::factory()->create(['category_id' => $otherCategory->id, 'price' => 20]);
        $orders->addItem($order, $otherProduct, 1);

        $this->actingAs($caissier)->post("/orders/{$order->id}/payments", [
            'method' => 'cash',
            'amount' => (string) $order->fresh()->total,
        ]);

        $this->assertSame('100.000', (string) $flour->fresh()->current_stock);
    }
}
