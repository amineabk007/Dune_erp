<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedStarterIngredientsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_starter_ingredient_list(): void
    {
        $this->artisan('dune:seed-starter-ingredients')->assertSuccessful();

        $this->assertGreaterThan(60, Ingredient::count());
        $this->assertDatabaseHas('ingredients', ['name' => 'Tomates', 'unit' => 'kg']);
        $this->assertDatabaseHas('ingredients', ['name' => 'Huile d\'olive', 'unit' => 'L']);
    }

    public function test_running_it_twice_does_not_duplicate_or_overwrite_existing_ingredients(): void
    {
        $existing = Ingredient::factory()->create([
            'name' => 'Tomates',
            'unit' => 'kg',
            'current_stock' => 42,
            'unit_cost' => 7.5,
        ]);

        $this->artisan('dune:seed-starter-ingredients')->assertSuccessful();

        $this->assertSame(1, Ingredient::where('name', 'Tomates')->count());
        $this->assertEquals(42, $existing->fresh()->current_stock);
        $this->assertEquals(7.5, $existing->fresh()->unit_cost);
    }
}
