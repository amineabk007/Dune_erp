<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_create_a_user_with_a_role(): void
    {
        $response = $this->actingAs($this->admin)->post('/users', [
            'name' => 'Nouveau Serveur',
            'email' => 'serveur.test@dune-erp.test',
            'phone' => null,
            'password' => 'Secret1234!',
            'password_confirmation' => 'Secret1234!',
            'roles' => ['serveur'],
        ]);

        $response->assertRedirect('/users');

        $user = User::where('email', 'serveur.test@dune-erp.test')->firstOrFail();
        $this->assertTrue($user->hasRole('serveur'));
        $this->assertTrue(Hash::check('Secret1234!', $user->password));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'module' => 'users',
            'record_id' => $user->id,
        ]);
    }

    public function test_creating_a_user_requires_a_role_and_a_unique_email(): void
    {
        $existing = User::factory()->create();

        $response = $this->actingAs($this->admin)->post('/users', [
            'name' => 'Test',
            'email' => $existing->email,
            'password' => 'Secret1234!',
            'password_confirmation' => 'Secret1234!',
            'roles' => [],
        ]);

        $response->assertSessionHasErrors(['email', 'roles']);
    }

    public function test_admin_can_update_a_user(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $user->assignRole('serveur');

        $response = $this->actingAs($this->admin)->put("/users/{$user->id}", [
            'name' => 'New Name',
            'email' => $user->email,
            'roles' => ['caissier'],
            'is_active' => '1',
        ]);

        $response->assertRedirect('/users');
        $user->refresh();

        $this->assertSame('New Name', $user->name);
        $this->assertTrue($user->hasRole('caissier'));
        $this->assertFalse($user->hasRole('serveur'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'module' => 'users',
            'record_id' => $user->id,
        ]);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $response = $this->actingAs($this->admin)->patch("/users/{$this->admin->id}/toggle-active");

        $response->assertForbidden();
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_admin_can_deactivate_another_user_and_it_is_audited(): void
    {
        $user = User::factory()->create();
        $user->assignRole('serveur');

        $response = $this->actingAs($this->admin)->patch("/users/{$user->id}/toggle-active");

        $response->assertRedirect();
        $this->assertFalse($user->fresh()->is_active);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deactivate',
            'module' => 'users',
            'record_id' => $user->id,
        ]);
    }

    public function test_a_user_without_users_manage_cannot_create_users(): void
    {
        $caissier = User::factory()->create();
        $caissier->assignRole('caissier');

        $response = $this->actingAs($caissier)->post('/users', [
            'name' => 'X',
            'email' => 'x@dune-erp.test',
            'password' => 'Secret1234!',
            'password_confirmation' => 'Secret1234!',
            'roles' => ['serveur'],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'x@dune-erp.test']);
    }

    public function test_audit_log_is_immutable(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => 'create',
            'module' => 'users',
            'record_id' => 1,
        ]);

        $this->expectException(\LogicException::class);
        $log->action = 'update';
        $log->save();
    }
}
