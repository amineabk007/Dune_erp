<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_admin_can_access_every_foundation_screen(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/users')->assertOk();
        $this->actingAs($admin)->get('/roles')->assertOk();
        $this->actingAs($admin)->get('/audit')->assertOk();
    }

    public function test_a_role_without_users_manage_cannot_access_user_management(): void
    {
        $caissier = User::factory()->create();
        $caissier->assignRole('caissier');

        $this->actingAs($caissier)->get('/users')->assertForbidden();
        $this->actingAs($caissier)->get('/users/create')->assertForbidden();
    }

    public function test_a_role_without_roles_manage_cannot_access_role_management(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)->get('/roles')->assertForbidden();
    }

    public function test_a_role_without_audit_view_cannot_access_audit_log(): void
    {
        $serveur = User::factory()->create();
        $serveur->assignRole('serveur');

        $this->actingAs($serveur)->get('/audit')->assertForbidden();
    }

    public function test_direction_role_can_view_audit_log(): void
    {
        $direction = User::factory()->create();
        $direction->assignRole('direction');

        $this->actingAs($direction)->get('/audit')->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/users')->assertRedirect('/login');
    }
}
