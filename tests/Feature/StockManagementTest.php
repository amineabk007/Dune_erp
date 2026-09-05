<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private User $stockUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->stockUser = User::factory()->create();
        $this->stockUser->assignRole('stock');
    }

    public function test_creating_an_ingredient_with_opening_stock_records_a_movement(): void
    {
        $response = $this->actingAs($this->stockUser)->post('/ingredients', [
            'name' => 'Farine',
            'unit' => 'kg',
            'current_stock' => 50,
            'minimum_stock' => 10,
            'unit_cost' => 6.5,
        ]);

        $response->assertRedirect();
        $ingredient = Ingredient::where('name', 'Farine')->firstOrFail();
        $this->assertSame('50.000', (string) $ingredient->current_stock);
        $this->assertDatabaseHas('stock_movements', ['ingredient_id' => $ingredient->id, 'quantity' => '50.000']);
    }

    public function test_updating_an_ingredient_cannot_change_stock_directly(): void
    {
        $ingredient = Ingredient::factory()->create(['current_stock' => 20]);

        $this->actingAs($this->stockUser)->put("/ingredients/{$ingredient->id}", [
            'name' => $ingredient->name,
            'unit' => $ingredient->unit,
            'minimum_stock' => 5,
            'unit_cost' => 3,
        ])->assertRedirect();

        $this->assertSame('20.000', (string) $ingredient->fresh()->current_stock);
        $this->assertDatabaseHas('audit_logs', ['action' => 'update', 'module' => 'stock']);
    }

    public function test_manual_stock_adjustment_updates_stock_and_creates_a_movement(): void
    {
        $ingredient = Ingredient::factory()->create(['current_stock' => 20]);

        $response = $this->actingAs($this->stockUser)->post("/ingredients/{$ingredient->id}/adjust", [
            'type' => 'waste',
            'direction' => 'out',
            'quantity' => 3,
            'reason' => 'Produit périmé',
        ]);

        $response->assertRedirect();
        $this->assertSame('17.000', (string) $ingredient->fresh()->current_stock);
        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $ingredient->id,
            'type' => 'waste',
            'quantity' => '-3.000',
            'reason' => 'Produit périmé',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock_waste', 'module' => 'stock']);
    }

    public function test_physical_inventory_correction_sets_stock_to_the_counted_amount(): void
    {
        $ingredient = Ingredient::factory()->create(['current_stock' => 20]);

        $this->actingAs($this->stockUser)->post("/ingredients/{$ingredient->id}/inventory", [
            'counted_quantity' => 17.5,
        ])->assertRedirect();

        $this->assertSame('17.500', (string) $ingredient->fresh()->current_stock);
        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $ingredient->id,
            'quantity' => '-2.500',
            'reason' => 'Inventaire physique',
        ]);
    }

    public function test_low_stock_alerts_only_list_ingredients_at_or_below_minimum(): void
    {
        Ingredient::factory()->create(['name' => 'Low One', 'current_stock' => 2, 'minimum_stock' => 5]);
        Ingredient::factory()->create(['name' => 'Fine One', 'current_stock' => 50, 'minimum_stock' => 5]);

        $response = $this->actingAs($this->stockUser)->get('/stock/alerts');

        $response->assertOk();
        $response->assertSee('Low One');
        $response->assertDontSee('Fine One');
    }

    public function test_a_role_without_stock_adjust_cannot_create_ingredients_but_can_view_them(): void
    {
        $cuisine = User::factory()->create();
        $cuisine->assignRole('cuisine'); // has stock.view only

        $this->actingAs($cuisine)->get('/ingredients')->assertOk();
        $this->actingAs($cuisine)->post('/ingredients', [
            'name' => 'X', 'unit' => 'kg', 'current_stock' => 1, 'minimum_stock' => 1, 'unit_cost' => 1,
        ])->assertForbidden();
    }

    public function test_stock_movements_are_immutable(): void
    {
        $ingredient = Ingredient::factory()->create();
        $movement = app(StockService::class)->adjust($ingredient, $this->stockUser, 5, 'test');

        $this->expectException(\LogicException::class);
        $movement->quantity = 999;
        $movement->save();
    }
}
