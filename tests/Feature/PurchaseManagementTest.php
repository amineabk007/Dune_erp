<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class PurchaseManagementTest extends TestCase
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

    public function test_creating_a_purchase_with_lines_computes_the_total_cost(): void
    {
        $supplier = Supplier::factory()->create();
        $flour = Ingredient::factory()->create(['unit_cost' => 5]);
        $sugar = Ingredient::factory()->create(['unit_cost' => 8]);

        $response = $this->actingAs($this->stockUser)->post('/purchases', [
            'supplier_id' => $supplier->id,
            'reference' => 'INV-1001',
            'ingredient_id' => [$flour->id, $sugar->id],
            'quantity' => [10, 5],
            'unit_cost' => [6, 8.5],
        ]);

        $purchase = Purchase::where('reference', 'INV-1001')->firstOrFail();
        $response->assertRedirect(route('purchases.show', $purchase));
        $this->assertSame('102.50', (string) $purchase->total_cost);
        $this->assertSame('ordered', $purchase->status);
        $this->assertCount(2, $purchase->lines);
    }

    public function test_receiving_a_purchase_enters_stock_and_updates_ingredient_cost(): void
    {
        $supplier = Supplier::factory()->create();
        $flour = Ingredient::factory()->create(['unit_cost' => 5, 'current_stock' => 10]);

        $this->actingAs($this->stockUser)->post('/purchases', [
            'supplier_id' => $supplier->id,
            'ingredient_id' => [$flour->id],
            'quantity' => [20],
            'unit_cost' => [6.25],
        ]);
        $purchase = Purchase::firstOrFail();

        $response = $this->actingAs($this->stockUser)->post("/purchases/{$purchase->id}/receive");

        $response->assertRedirect(route('purchases.show', $purchase));
        $this->assertSame('received', $purchase->fresh()->status);
        $this->assertSame('30.000', (string) $flour->fresh()->current_stock);
        $this->assertSame('6.2500', (string) $flour->fresh()->unit_cost);
        $this->assertDatabaseHas('stock_movements', [
            'ingredient_id' => $flour->id,
            'type' => 'purchase',
            'quantity' => '20.000',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'receive', 'module' => 'purchases']);
    }

    public function test_a_received_purchase_cannot_be_cancelled(): void
    {
        $supplier = Supplier::factory()->create();
        $ingredient = Ingredient::factory()->create();

        $this->actingAs($this->stockUser)->post('/purchases', [
            'supplier_id' => $supplier->id,
            'ingredient_id' => [$ingredient->id],
            'quantity' => [1],
            'unit_cost' => [1],
        ]);
        $purchase = Purchase::firstOrFail();
        $this->actingAs($this->stockUser)->post("/purchases/{$purchase->id}/receive");

        $response = $this->actingAs($this->stockUser)->post("/purchases/{$purchase->id}/cancel", [
            'reason' => 'Erreur de saisie',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('purchase');
        $this->assertSame('received', $purchase->fresh()->status);
    }

    public function test_cancelling_an_ordered_purchase_does_not_touch_stock(): void
    {
        $supplier = Supplier::factory()->create();
        $ingredient = Ingredient::factory()->create(['current_stock' => 5]);

        $this->actingAs($this->stockUser)->post('/purchases', [
            'supplier_id' => $supplier->id,
            'ingredient_id' => [$ingredient->id],
            'quantity' => [1],
            'unit_cost' => [1],
        ]);
        $purchase = Purchase::firstOrFail();

        $this->actingAs($this->stockUser)->post("/purchases/{$purchase->id}/cancel", [
            'reason' => 'Fournisseur indisponible',
        ])->assertRedirect(route('purchases.show', $purchase));

        $this->assertSame('cancelled', $purchase->fresh()->status);
        $this->assertSame('5.000', (string) $ingredient->fresh()->current_stock);
    }

    public function test_a_role_without_purchases_manage_is_forbidden(): void
    {
        $serveur = User::factory()->create();
        $serveur->assignRole('serveur');

        $this->actingAs($serveur)->get('/purchases')->assertForbidden();
    }
}
