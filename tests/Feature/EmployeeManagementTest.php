<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
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

    public function test_a_user_with_employees_manage_can_create_an_employee(): void
    {
        $response = $this->actingAs($this->manager)->post('/employees', [
            'name' => 'Youssef Amrani',
            'position' => 'Serveur',
            'hire_date' => '2025-01-15',
            'salary' => 4500,
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', [
            'name' => 'Youssef Amrani',
            'created_by' => $this->manager->id,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'create', 'module' => 'employees']);
    }

    public function test_linking_the_same_user_account_to_two_employees_is_rejected(): void
    {
        $account = User::factory()->create();
        Employee::factory()->create(['user_id' => $account->id, 'created_by' => $this->manager->id]);

        $response = $this->actingAs($this->manager)->post('/employees', [
            'name' => 'Doublon',
            'position' => 'Cuisinier',
            'hire_date' => '2025-01-15',
            'user_id' => $account->id,
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    public function test_toggling_active_status_is_audited(): void
    {
        $employee = Employee::factory()->create(['is_active' => true, 'created_by' => $this->manager->id]);

        $this->actingAs($this->manager)->patch("/employees/{$employee->id}/toggle-active")
            ->assertRedirect();

        $this->assertFalse($employee->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'deactivate', 'module' => 'employees']);
    }

    public function test_a_role_without_employees_manage_is_forbidden(): void
    {
        $serveur = User::factory()->create();
        $serveur->assignRole('serveur');

        $this->actingAs($serveur)->get('/employees')->assertForbidden();
    }
}
