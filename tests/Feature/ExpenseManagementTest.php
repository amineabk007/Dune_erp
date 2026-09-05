<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    private User $comptable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->comptable = User::factory()->create();
        $this->comptable->assignRole('comptable');
    }

    public function test_a_user_with_expenses_manage_can_record_an_expense(): void
    {
        $response = $this->actingAs($this->comptable)->post('/expenses', [
            'category' => 'utilities',
            'description' => 'Facture électricité',
            'amount' => 850.50,
            'expense_date' => '2026-09-01',
            'paid_via' => 'bank',
        ]);

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'category' => 'utilities',
            'amount' => '850.50',
            'created_by' => $this->comptable->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'create', 'module' => 'expenses']);
    }

    public function test_an_expense_can_be_updated(): void
    {
        $expense = Expense::factory()->create(['amount' => 100, 'created_by' => $this->comptable->id]);

        $this->actingAs($this->comptable)->put("/expenses/{$expense->id}", [
            'category' => $expense->category,
            'description' => 'Corrigée',
            'amount' => 120,
            'expense_date' => $expense->expense_date->format('Y-m-d'),
            'paid_via' => $expense->paid_via,
        ])->assertRedirect(route('expenses.index'));

        $expense->refresh();
        $this->assertSame('120.00', (string) $expense->amount);
        $this->assertSame('Corrigée', $expense->description);
        $this->assertDatabaseHas('audit_logs', ['action' => 'update', 'module' => 'expenses']);
    }

    public function test_a_role_without_expenses_manage_is_forbidden(): void
    {
        $serveur = User::factory()->create();
        $serveur->assignRole('serveur');

        $this->actingAs($serveur)->get('/expenses')->assertForbidden();
    }
}
