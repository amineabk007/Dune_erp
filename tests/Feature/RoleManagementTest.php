<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_admin_can_change_a_roles_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $bar = Role::where('name', 'bar')->firstOrFail();

        $response = $this->actingAs($admin)->patch("/roles/{$bar->id}", [
            'permissions' => ['bar.view', 'stock.view', 'orders.view', 'reports.view'],
        ]);

        $response->assertRedirect('/roles');

        $bar->refresh();
        $this->assertTrue($bar->hasPermissionTo('reports.view'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'module' => 'roles',
            'record_id' => $bar->id,
        ]);
    }

    public function test_admin_role_permissions_cannot_be_changed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $originalCount = $adminRole->permissions()->count();

        $this->actingAs($admin)->patch("/roles/{$adminRole->id}", [
            'permissions' => ['bar.view'],
        ]);

        $this->assertSame($originalCount, $adminRole->fresh()->permissions()->count());
    }

    public function test_a_user_without_roles_manage_cannot_update_roles(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $bar = Role::where('name', 'bar')->firstOrFail();

        $response = $this->actingAs($manager)->patch("/roles/{$bar->id}", [
            'permissions' => ['bar.view'],
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_create_a_new_role_with_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/roles', [
            'name' => 'voiturier',
            'permissions' => ['orders.view'],
        ]);

        $response->assertRedirect('/roles');
        $role = Role::where('name', 'voiturier')->firstOrFail();
        $this->assertTrue($role->hasPermissionTo('orders.view'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'create', 'module' => 'roles']);
    }

    public function test_role_name_must_be_lowercase_without_spaces_and_unique(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post('/roles', ['name' => 'Voiturier VIP'])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)->post('/roles', ['name' => 'manager'])
            ->assertSessionHasErrors('name');
    }

    public function test_a_user_without_roles_manage_cannot_create_roles(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)->post('/roles', ['name' => 'voiturier'])->assertForbidden();
    }

    public function test_admin_can_delete_an_unused_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $role = Role::create(['name' => 'voiturier', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->delete("/roles/{$role->id}");

        $response->assertRedirect('/roles');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'delete', 'module' => 'roles']);
    }

    public function test_a_role_still_assigned_to_a_user_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $bar = Role::where('name', 'bar')->firstOrFail();
        $bartender = User::factory()->create();
        $bartender->assignRole('bar');

        $this->actingAs($admin)->delete("/roles/{$bar->id}")->assertForbidden();
        $this->assertDatabaseHas('roles', ['id' => $bar->id]);
    }

    public function test_the_admin_role_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $adminRole = Role::where('name', 'admin')->firstOrFail();

        $this->actingAs($admin)->delete("/roles/{$adminRole->id}")->assertForbidden();
        $this->assertDatabaseHas('roles', ['id' => $adminRole->id]);
    }
}
