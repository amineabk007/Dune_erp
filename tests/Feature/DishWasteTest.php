<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class DishWasteTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    public function test_declaring_a_dish_waste_consumes_recipe_ingredients_and_reports_cost(): void
    {
        $this->seedRolesAndPermissions();

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => 'Couscous Poulet']);

        $flour = Ingredient::factory()->create(['current_stock' => 100, 'unit_cost' => 6]);
        $chicken = Ingredient::factory()->create(['current_stock' => 50, 'unit_cost' => 40]);

        $recipe = Recipe::factory()->create(['product_id' => $product->id, 'yield_quantity' => 1]);
        $recipe->items()->create(['ingredient_id' => $flour->id, 'quantity' => 0.5]);
        $recipe->items()->create(['ingredient_id' => $chicken->id, 'quantity' => 0.3]);

        $response = $this->actingAs($manager)->post('/stock/dish-waste', [
            'type' => 'dish',
            'product_id' => $product->id,
            'quantity' => 2,
            'reason' => 'Tombé en cuisine',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        // 2 portions: flour 2*0.5=1kg, chicken 2*0.3=0.6kg
        $this->assertSame('99.000', (string) $flour->fresh()->current_stock);
        $this->assertSame('49.400', (string) $chicken->fresh()->current_stock);

        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $flour->id,
            'type' => 'waste',
            'quantity' => '-1.000',
            'reason' => 'Tombé en cuisine',
        ]);

        // cost = 1*6 + 0.6*40 = 6 + 24 = 30
        $this->assertStringContainsString('30.00', session('status'));
    }

    public function test_cuisine_role_can_declare_dish_waste(): void
    {
        $this->seedRolesAndPermissions();

        $cuisine = User::factory()->create();
        $cuisine->assignRole('cuisine');

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $ingredient = Ingredient::factory()->create(['current_stock' => 10]);
        $recipe = Recipe::factory()->create(['product_id' => $product->id, 'yield_quantity' => 1]);
        $recipe->items()->create(['ingredient_id' => $ingredient->id, 'quantity' => 1]);

        $this->actingAs($cuisine)->post('/stock/dish-waste', [
            'type' => 'dish',
            'product_id' => $product->id,
            'quantity' => 1,
            'reason' => 'Brûlé',
        ])->assertRedirect();

        $this->assertSame('9.000', (string) $ingredient->fresh()->current_stock);
    }

    public function test_a_role_without_stock_adjust_cannot_declare_dish_waste(): void
    {
        $this->seedRolesAndPermissions();

        $serveur = User::factory()->create();
        $serveur->assignRole('serveur');

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($serveur)->post('/stock/dish-waste', [
            'type' => 'dish',
            'product_id' => $product->id,
            'quantity' => 1,
            'reason' => 'Test',
        ])->assertForbidden();
    }

    public function test_declaring_waste_for_a_product_without_a_recipe_fails(): void
    {
        $this->seedRolesAndPermissions();

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs($manager)->post('/stock/dish-waste', [
            'type' => 'dish',
            'product_id' => $product->id,
            'quantity' => 1,
            'reason' => 'Test',
        ])->assertSessionHasErrors('product_id');
    }

    public function test_declaring_a_raw_ingredient_waste_directly(): void
    {
        $this->seedRolesAndPermissions();

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $oil = Ingredient::factory()->create(['current_stock' => 20, 'unit_cost' => 15]);

        $response = $this->actingAs($manager)->post('/stock/dish-waste', [
            'type' => 'ingredient',
            'ingredient_id' => $oil->id,
            'quantity' => 2,
            'reason' => 'Bidon renversé',
        ]);

        $response->assertRedirect();
        $this->assertSame('18.000', (string) $oil->fresh()->current_stock);

        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $oil->id,
            'type' => 'waste',
            'quantity' => '-2.000',
            'unit_cost' => '15.0000',
            'reason' => 'Bidon renversé',
        ]);

        // cost = 2 * 15
        $this->assertStringContainsString('30.00', session('status'));
    }
}
