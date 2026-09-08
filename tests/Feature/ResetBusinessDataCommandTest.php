<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class ResetBusinessDataCommandTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    public function test_it_wipes_business_data_but_keeps_users_and_roles(): void
    {
        $this->seedRolesAndPermissions();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $zone = Zone::factory()->create();
        RestaurantTable::factory()->create(['zone_id' => $zone->id]);
        Category::factory()->create();
        Product::factory()->create();
        Ingredient::factory()->create();

        $this->artisan('dune:reset-business-data', ['--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('zones', 0);
        $this->assertDatabaseCount('restaurant_tables', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('ingredients', 0);

        $this->assertTrue($admin->fresh()->exists());
        $this->assertTrue($admin->fresh()->hasRole('admin'));
        $this->assertGreaterThan(0, Role::count());
    }

    public function test_it_aborts_without_force_and_without_confirmation(): void
    {
        Zone::factory()->create();

        $this->artisan('dune:reset-business-data')
            ->expectsConfirmation(
                'This will permanently delete ALL business data (orders, stock, products, tables, reservations, etc.) '
                .'on this database. User accounts, roles and permissions are kept. Continue?',
                'no'
            )
            ->assertSuccessful();

        $this->assertDatabaseCount('zones', 1);
    }
}
