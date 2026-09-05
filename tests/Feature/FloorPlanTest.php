<?php

namespace Tests\Feature;

use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Zone;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class FloorPlanTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private User $serveur;

    private Zone $zone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->serveur = User::factory()->create();
        $this->serveur->assignRole('serveur');
        $this->zone = Zone::factory()->create();
    }

    public function test_floor_plan_shows_tables_grouped_by_zone(): void
    {
        RestaurantTable::factory()->create(['zone_id' => $this->zone->id, 'name' => 'T1']);

        $response = $this->actingAs($this->serveur)->get('/floor-plan');

        $response->assertOk();
        $response->assertSee($this->zone->name);
        $response->assertSee('T1');
    }

    public function test_marking_a_cleaning_table_available_requires_it_to_be_in_cleaning(): void
    {
        $table = RestaurantTable::factory()->create(['zone_id' => $this->zone->id, 'status' => 'occupied']);

        $response = $this->actingAs($this->serveur)->post("/floor-plan/tables/{$table->id}/mark-available");

        $response->assertSessionHasErrors('table');
        $this->assertSame('occupied', $table->fresh()->status);
    }

    public function test_marking_a_cleaning_table_available_works(): void
    {
        $table = RestaurantTable::factory()->create(['zone_id' => $this->zone->id, 'status' => 'cleaning']);

        $this->actingAs($this->serveur)->post("/floor-plan/tables/{$table->id}/mark-available")->assertRedirect();

        $this->assertSame('available', $table->fresh()->status);
    }

    public function test_transferring_an_order_to_another_table(): void
    {
        $oldTable = RestaurantTable::factory()->create(['zone_id' => $this->zone->id, 'status' => 'available']);
        $newTable = RestaurantTable::factory()->create(['zone_id' => $this->zone->id, 'status' => 'available']);

        $order = app(OrderService::class)->createOrder($this->serveur, $oldTable->id, null, null);

        $response = $this->actingAs($this->serveur)->post("/floor-plan/tables/{$oldTable->id}/transfer", [
            'new_table_id' => $newTable->id,
        ]);

        $response->assertRedirect();
        $order->refresh();
        $this->assertSame($newTable->id, $order->table_id);
        $this->assertSame('occupied', $newTable->fresh()->status);
        $this->assertSame('available', $oldTable->fresh()->status);
    }
}
