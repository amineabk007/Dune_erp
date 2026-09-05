<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Models\Zone;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private User $serveur;

    private RestaurantTable $table;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->serveur = User::factory()->create();
        $this->serveur->assignRole('serveur');

        $zone = Zone::factory()->create();
        $this->table = RestaurantTable::factory()->create(['zone_id' => $zone->id]);
        $this->customer = Customer::factory()->create();
    }

    public function test_serveur_can_create_a_reservation(): void
    {
        $response = $this->actingAs($this->serveur)->post('/reservations', [
            'customer_id' => $this->customer->id,
            'reserved_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'guests' => 4,
            'table_ids' => [$this->table->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reservations', ['customer_id' => $this->customer->id, 'status' => 'pending']);
        $this->assertDatabaseHas('reservation_tables', ['table_id' => $this->table->id]);
    }

    public function test_double_booking_the_same_table_at_an_overlapping_time_is_rejected(): void
    {
        $when = now()->addDay()->setTime(20, 0);

        $this->actingAs($this->serveur)->post('/reservations', [
            'customer_id' => $this->customer->id,
            'reserved_at' => $when->format('Y-m-d\TH:i'),
            'guests' => 2,
            'table_ids' => [$this->table->id],
        ]);

        // 30 minutes later, well inside the 120-minute block window.
        $response = $this->actingAs($this->serveur)->post('/reservations', [
            'customer_id' => $this->customer->id,
            'reserved_at' => $when->clone()->addMinutes(30)->format('Y-m-d\TH:i'),
            'guests' => 2,
            'table_ids' => [$this->table->id],
        ]);

        $response->assertSessionHasErrors('table_ids');
        $this->assertSame(1, Reservation::count());
    }

    public function test_full_reservation_to_order_workflow(): void
    {
        $reservation = app(ReservationService::class)->create(
            $this->serveur,
            $this->customer->id,
            now()->addHour(),
            2,
            [$this->table->id]
        );

        $this->actingAs($this->serveur)->post("/reservations/{$reservation->id}/confirm")->assertRedirect();
        $this->assertSame('confirmed', $reservation->fresh()->status);

        $this->actingAs($this->serveur)->post("/reservations/{$reservation->id}/seat")->assertRedirect();
        $reservation->refresh();
        $this->assertSame('seated', $reservation->status);
        $this->assertSame('reserved', $this->table->fresh()->status);

        $response = $this->actingAs($this->serveur)->post("/reservations/{$reservation->id}/create-order");
        $response->assertRedirect();

        $reservation->refresh();
        $this->assertNotNull($reservation->order_id);
        $this->assertSame($this->table->id, $reservation->order->table_id);
        $this->assertSame('occupied', $this->table->fresh()->status);
    }

    public function test_cancelling_a_reservation_frees_a_reserved_table(): void
    {
        // Cancellation requires reservations.cancel, granted to manager/admin
        // only (same accountability pattern as order cancellation).
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $reservation = app(ReservationService::class)->create(
            $this->serveur, $this->customer->id, now()->addHour(), 2, [$this->table->id]
        );
        app(ReservationService::class)->transition($reservation, 'confirmed');
        app(ReservationService::class)->transition($reservation, 'seated');

        $this->actingAs($manager)->post("/reservations/{$reservation->id}/cancel")->assertRedirect();

        $this->assertSame('cancelled', $reservation->fresh()->status);
        $this->assertSame('available', $this->table->fresh()->status);
    }

    public function test_a_serveur_without_reservations_cancel_cannot_cancel_a_reservation(): void
    {
        $reservation = app(ReservationService::class)->create(
            $this->serveur, $this->customer->id, now()->addHour(), 2, [$this->table->id]
        );

        $this->actingAs($this->serveur)
            ->post("/reservations/{$reservation->id}/cancel")
            ->assertForbidden();
    }

    public function test_a_role_without_reservations_view_cannot_access_reservations(): void
    {
        $stock = User::factory()->create();
        $stock->assignRole('stock');

        $this->actingAs($stock)->get('/reservations')->assertForbidden();
    }
}
