<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class RoleBasedLandingTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    private function loginWithPin(): \Illuminate\Testing\TestResponse
    {
        return $this->post('/pin-login', ['pin' => '12345678']);
    }

    public function test_cuisine_role_lands_on_the_kitchen_screen(): void
    {
        $user = User::factory()->create(['pin' => Hash::make('12345678')]);
        $user->assignRole('cuisine');

        $this->loginWithPin()->assertRedirect(route('kitchen.index'));
    }

    public function test_bar_role_lands_on_the_bar_screen(): void
    {
        $user = User::factory()->create(['pin' => Hash::make('12345678')]);
        $user->assignRole('bar');

        $this->loginWithPin()->assertRedirect(route('bar.index'));
    }

    public function test_caissier_role_lands_on_orders(): void
    {
        $user = User::factory()->create(['pin' => Hash::make('12345678')]);
        $user->assignRole('caissier');

        $this->loginWithPin()->assertRedirect(route('orders.index'));
    }

    public function test_serveur_role_lands_on_the_floor_plan(): void
    {
        $user = User::factory()->create(['pin' => Hash::make('12345678')]);
        $user->assignRole('serveur');

        $this->loginWithPin()->assertRedirect(route('floor-plan.index'));
    }

    public function test_manager_always_lands_on_the_dashboard_even_with_an_operational_role(): void
    {
        $user = User::factory()->create(['pin' => Hash::make('12345678')]);
        $user->assignRole(['manager', 'cuisine']);

        $this->loginWithPin()->assertRedirect(route('dashboard'));
    }

    public function test_admin_lands_on_the_dashboard(): void
    {
        $user = User::factory()->create(['pin' => Hash::make('12345678')]);
        $user->assignRole('admin');

        $this->loginWithPin()->assertRedirect(route('dashboard'));
    }

    public function test_a_deep_link_still_wins_over_the_default_landing_route(): void
    {
        $user = User::factory()->create(['pin' => Hash::make('12345678')]);
        $user->assignRole('serveur'); // default landing would be the floor plan

        // Visiting a permitted page while a guest stores it as "intended" and
        // should still take priority over the role's default once logged in.
        $this->get('/orders');
        $this->loginWithPin()->assertRedirect(route('orders.index'));
    }
}
