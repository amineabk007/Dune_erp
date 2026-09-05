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
}
