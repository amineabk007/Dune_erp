<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private User $stockUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->stockUser = User::factory()->create();
        $this->stockUser->assignRole('stock');
    }

    public function test_a_user_with_suppliers_manage_can_create_a_supplier(): void
    {
        $response = $this->actingAs($this->stockUser)->post('/suppliers', [
            'name' => 'Marrakech Fresh Produce',
            'contact_name' => 'Karim',
            'phone' => '0600000000',
            'email' => 'contact@mfp.test',
        ]);

        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['name' => 'Marrakech Fresh Produce', 'is_active' => 1]);
    }

    public function test_a_supplier_can_be_updated_and_deactivated(): void
    {
        $supplier = Supplier::factory()->create(['is_active' => true]);

        $this->actingAs($this->stockUser)->put("/suppliers/{$supplier->id}", [
            'name' => $supplier->name,
            'is_active' => '0',
        ])->assertRedirect(route('suppliers.index'));

        $this->assertFalse($supplier->fresh()->is_active);
    }

    public function test_a_role_without_suppliers_manage_is_forbidden(): void
    {
        $serveur = User::factory()->create();
        $serveur->assignRole('serveur');

        $this->actingAs($serveur)->get('/suppliers')->assertForbidden();
        $this->actingAs($serveur)->post('/suppliers', ['name' => 'X'])->assertForbidden();
    }
}
