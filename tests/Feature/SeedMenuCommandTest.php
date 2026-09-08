<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedMenuCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_categories_products_and_extra_ingredients(): void
    {
        $this->artisan('dune:seed-menu')->assertSuccessful();

        $this->assertDatabaseHas('categories', ['name' => 'Entrées', 'type' => 'food']);
        $this->assertDatabaseHas('categories', ['name' => 'Mocktails', 'type' => 'drink']);
        $this->assertGreaterThanOrEqual(8, Category::count());
        $this->assertGreaterThanOrEqual(50, Product::count());
        $this->assertGreaterThanOrEqual(35, Ingredient::count());

        $this->assertDatabaseHas('ingredients', ['name' => 'Mozzarella', 'unit' => 'kg']);
    }

    public function test_menu_prices_are_ttc_with_tax_backed_out_of_the_stored_price(): void
    {
        $this->artisan('dune:seed-menu')->assertSuccessful();

        $product = Product::where('name', 'Pastilla au coquelet')->firstOrFail();

        $this->assertEquals(10, (float) $product->tax_rate);
        $this->assertEqualsWithDelta(63.64, (float) $product->price, 0.01);

        $total = round((float) $product->price * (1 + (float) $product->tax_rate / 100), 2);
        $this->assertEqualsWithDelta(70.0, $total, 0.01);
    }

    public function test_running_it_twice_does_not_duplicate_anything(): void
    {
        $this->artisan('dune:seed-menu')->assertSuccessful();
        $countProducts = Product::count();
        $countCategories = Category::count();
        $countIngredients = Ingredient::count();

        $this->artisan('dune:seed-menu')->assertSuccessful();

        $this->assertSame($countProducts, Product::count());
        $this->assertSame($countCategories, Category::count());
        $this->assertSame($countIngredients, Ingredient::count());
    }
}
