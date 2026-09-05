<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
    }

    public function test_a_user_with_events_manage_can_create_an_event(): void
    {
        $response = $this->actingAs($this->manager)->post('/events', [
            'name' => 'Anniversaire Yasmine',
            'event_date' => now()->addWeek()->format('Y-m-d H:i:s'),
            'guest_count' => 40,
            'total_amount' => 12000,
        ]);

        $event = Event::firstOrFail();
        $response->assertRedirect(route('events.show', $event));
        $this->assertSame('pending', $event->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'create', 'module' => 'events']);
    }

    public function test_confirming_and_completing_an_event_follows_the_allowed_transitions(): void
    {
        $event = Event::factory()->create(['status' => 'pending', 'created_by' => $this->manager->id]);

        $this->actingAs($this->manager)->post("/events/{$event->id}/transition/confirmed")
            ->assertRedirect(route('events.show', $event));
        $this->assertSame('confirmed', $event->fresh()->status);

        $this->actingAs($this->manager)->post("/events/{$event->id}/transition/completed")
            ->assertRedirect(route('events.show', $event));
        $this->assertSame('completed', $event->fresh()->status);
    }

    public function test_completing_a_pending_event_directly_is_rejected(): void
    {
        $event = Event::factory()->create(['status' => 'pending', 'created_by' => $this->manager->id]);

        $response = $this->actingAs($this->manager)->post("/events/{$event->id}/transition/completed");

        $response->assertSessionHasErrors('event');
        $this->assertSame('pending', $event->fresh()->status);
    }

    public function test_cancelling_an_event_requires_a_reason(): void
    {
        $event = Event::factory()->create(['status' => 'pending', 'created_by' => $this->manager->id]);

        $this->actingAs($this->manager)->post("/events/{$event->id}/transition/cancelled")
            ->assertSessionHasErrors('reason');

        $this->actingAs($this->manager)->post("/events/{$event->id}/transition/cancelled", [
            'reason' => 'Client a annulé',
        ])->assertRedirect(route('events.show', $event));

        $event->refresh();
        $this->assertSame('cancelled', $event->status);
        $this->assertSame('Client a annulé', $event->cancel_reason);
    }

    public function test_recording_a_deposit_updates_amount_paid_and_balance(): void
    {
        $event = Event::factory()->create(['total_amount' => 10000, 'amount_paid' => 0, 'created_by' => $this->manager->id]);

        $response = $this->actingAs($this->manager)->post("/events/{$event->id}/payments", [
            'type' => 'deposit',
            'method' => 'cash',
            'amount' => 3000,
        ]);

        $response->assertRedirect(route('events.show', $event));
        $event->refresh();
        $this->assertSame('3000.00', (string) $event->amount_paid);
        $this->assertSame('7000.00', $event->balanceDue());
    }

    public function test_cannot_overpay_an_event(): void
    {
        $event = Event::factory()->create(['total_amount' => 1000, 'amount_paid' => 0, 'created_by' => $this->manager->id]);

        $response = $this->actingAs($this->manager)->post("/events/{$event->id}/payments", [
            'type' => 'deposit',
            'method' => 'cash',
            'amount' => 1500,
        ]);

        $response->assertSessionHasErrors('payment');
        $this->assertSame('0.00', (string) $event->fresh()->amount_paid);
    }

    public function test_a_cancelled_event_cannot_receive_a_payment(): void
    {
        $event = Event::factory()->create(['status' => 'cancelled', 'total_amount' => 1000, 'created_by' => $this->manager->id]);

        $response = $this->actingAs($this->manager)->post("/events/{$event->id}/payments", [
            'type' => 'deposit',
            'method' => 'cash',
            'amount' => 100,
        ]);

        $response->assertSessionHasErrors('payment');
    }

    public function test_a_role_without_events_manage_is_forbidden(): void
    {
        $serveur = User::factory()->create();
        $serveur->assignRole('serveur');

        $this->actingAs($serveur)->get('/events')->assertForbidden();
    }
}
