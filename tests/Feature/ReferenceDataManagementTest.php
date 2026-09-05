<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class ReferenceDataManagementTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private User $manager;

    private User $serveur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager'); // has tables.manage, categories.manage, products.*, customers.manage

        $this->serveur = User::factory()->create();
        $this->serveur->assignRole('serveur'); // has tables.manage but not categories/products.create/customers.manage
    }

    // --- Zones & Tables ---

    public function test_manager_can_create_a_zone_and_a_table_in_it(): void
    {
        $response = $this->actingAs($this->manager)->post('/zones', [
            'name' => 'Rooftop',
            'description' => 'Vue sur la Koutoubia',
            'is_active' => '1',
        ]);
        $response->assertRedirect('/zones');
        $zone = Zone::where('name', 'Rooftop')->firstOrFail();

        $response = $this->actingAs($this->manager)->post('/tables', [
            'zone_id' => $zone->id,
            'name' => 'T1',
            'capacity' => 4,
        ]);
        $response->assertRedirect('/tables');

        $table = RestaurantTable::where('name', 'T1')->firstOrFail();
        $this->assertSame('available', $table->status);
        $this->assertSame($zone->id, $table->zone_id);

        $this->assertDatabaseHas('audit_logs', ['action' => 'create', 'module' => 'zones', 'record_id' => $zone->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'create', 'module' => 'tables', 'record_id' => $table->id]);
    }

    public function test_table_names_must_be_unique_within_a_zone(): void
    {
        $zone = Zone::factory()->create();
        RestaurantTable::factory()->create(['zone_id' => $zone->id, 'name' => 'T1']);

        $response = $this->actingAs($this->manager)->post('/tables', [
            'zone_id' => $zone->id,
            'name' => 'T1',
            'capacity' => 2,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_a_zone_with_tables_cannot_be_deleted(): void
    {
        $zone = Zone::factory()->create();
        RestaurantTable::factory()->create(['zone_id' => $zone->id]);

        $response = $this->actingAs($this->manager)->delete("/zones/{$zone->id}");

        $response->assertSessionHasErrors('zone');
        $this->assertDatabaseHas('zones', ['id' => $zone->id]);
    }

    public function test_a_role_without_tables_manage_cannot_manage_zones(): void
    {
        $cuisine = User::factory()->create();
        $cuisine->assignRole('cuisine');

        $this->actingAs($cuisine)->get('/zones')->assertForbidden();
        $this->actingAs($cuisine)->post('/zones', ['name' => 'X'])->assertForbidden();
    }

    // --- Categories & Products ---

    public function test_manager_can_create_a_category_and_a_product(): void
    {
        $response = $this->actingAs($this->manager)->post('/categories', [
            'name' => 'Tajines',
            'type' => 'food',
            'is_active' => '1',
        ]);
        $response->assertRedirect('/categories');
        $category = Category::where('name', 'Tajines')->firstOrFail();

        $response = $this->actingAs($this->manager)->post('/products', [
            'category_id' => $category->id,
            'sku' => 'TAJ-001',
            'name' => 'Tajine Poulet',
            'price' => 120,
            'tax_rate' => 20,
            'is_active' => '1',
        ]);
        $response->assertRedirect('/products');

        $product = Product::where('sku', 'TAJ-001')->firstOrFail();
        $this->assertSame('120.00', (string) $product->price);
    }

    public function test_a_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->manager)->delete("/categories/{$category->id}");

        $response->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_updating_a_products_price_records_price_history(): void
    {
        $product = Product::factory()->create(['price' => 100]);

        $response = $this->actingAs($this->manager)->put("/products/{$product->id}", [
            'category_id' => $product->category_id,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => 150,
            'tax_rate' => $product->tax_rate,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/products');
        $this->assertDatabaseHas('product_price_histories', [
            'product_id' => $product->id,
            'old_price' => '100.00',
            'new_price' => '150.00',
        ]);
    }

    public function test_updating_a_product_without_changing_price_does_not_record_history(): void
    {
        $product = Product::factory()->create(['price' => 100]);

        $this->actingAs($this->manager)->put("/products/{$product->id}", [
            'category_id' => $product->category_id,
            'sku' => $product->sku,
            'name' => 'Nouveau nom',
            'price' => 100,
            'tax_rate' => $product->tax_rate,
            'is_active' => '1',
        ]);

        $this->assertDatabaseCount('product_price_histories', 0);
    }

    public function test_a_role_without_products_create_cannot_create_products_but_can_view_them(): void
    {
        $caissier = User::factory()->create();
        $caissier->assignRole('caissier'); // has products.view only

        $this->actingAs($caissier)->get('/products')->assertOk();
        $this->actingAs($caissier)->post('/products', [
            'category_id' => Category::factory()->create()->id,
            'sku' => 'X',
            'name' => 'X',
            'price' => 10,
            'tax_rate' => 0,
        ])->assertForbidden();
    }

    // --- Customers ---

    public function test_manager_can_create_and_search_customers(): void
    {
        $response = $this->actingAs($this->manager)->post('/customers', [
            'name' => 'Fatima Zahra',
            'phone' => '0612345678',
        ]);
        $response->assertRedirect('/customers');

        $response = $this->actingAs($this->manager)->get('/customers?q=Fatima');
        $response->assertOk();
        $response->assertSee('Fatima Zahra');
    }

    public function test_a_role_without_customers_manage_cannot_access_customers(): void
    {
        $this->actingAs($this->serveur)->get('/customers')->assertForbidden();
    }
}
