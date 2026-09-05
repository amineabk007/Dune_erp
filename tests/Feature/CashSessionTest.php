<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class CashSessionTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private User $caissier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->caissier = User::factory()->create();
        $this->caissier->assignRole('caissier');
    }

    public function test_caissier_can_open_a_cash_session(): void
    {
        $response = $this->actingAs($this->caissier)->post('/cash-sessions', ['opening_cash' => 500]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cash_sessions', ['opening_cash' => 500, 'status' => 'open']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'open', 'module' => 'cash']);
    }

    public function test_only_one_cash_session_can_be_open_at_a_time(): void
    {
        $this->actingAs($this->caissier)->post('/cash-sessions', ['opening_cash' => 500]);

        $response = $this->actingAs($this->caissier)->get('/cash-sessions/create');
        $response->assertOk();
        $response->assertSee('déjà ouverte');
    }

    public function test_closing_a_session_computes_expected_cash_and_difference(): void
    {
        $session = CashSession::create([
            'opened_by' => $this->caissier->id,
            'opened_at' => now(),
            'opening_cash' => 500,
            'status' => 'open',
        ]);

        $this->actingAs($this->caissier)->post("/cash-sessions/{$session->id}/movements", [
            'type' => 'cash_in', 'amount' => 100, 'reason' => 'Appoint',
        ]);
        $this->actingAs($this->caissier)->post("/cash-sessions/{$session->id}/movements", [
            'type' => 'cash_out', 'amount' => 20, 'reason' => 'Achat glace',
        ]);

        $response = $this->actingAs($this->caissier)->post("/cash-sessions/{$session->id}/close", [
            'counted_cash' => 575,
        ]);

        $response->assertRedirect();
        $session->refresh();

        // expected = 500 opening + 100 in - 20 out = 580 (no cash payments in this test)
        $this->assertSame('580.00', (string) $session->expected_cash);
        $this->assertSame('575.00', (string) $session->counted_cash);
        $this->assertSame('-5.00', (string) $session->difference);
        $this->assertDatabaseHas('audit_logs', ['action' => 'close', 'module' => 'cash']);
    }

    public function test_a_role_without_cash_open_cannot_open_a_session(): void
    {
        $cuisine = User::factory()->create();
        $cuisine->assignRole('cuisine');

        $this->actingAs($cuisine)->post('/cash-sessions', ['opening_cash' => 100])->assertForbidden();
    }
}
