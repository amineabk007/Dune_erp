<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use App\Services\CashSessionService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class ReportingTest extends TestCase
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

    private function payOrder(float $total, string $productName = 'Tajine'): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => $productName, 'price' => $total, 'tax_rate' => 0]);

        $order = app(OrderService::class)->createOrder($this->manager, null, null, null);
        app(OrderService::class)->addItem($order->fresh(), $product, 1);

        $session = app(CashSessionService::class)->currentOpenSession()
            ?? app(CashSessionService::class)->open($this->manager, 0);

        app(PaymentService::class)->recordPayment($order->fresh(), $session, $this->manager, 'cash', $total);
    }

    public function test_sales_summary_reflects_non_refunded_payments_in_the_period(): void
    {
        $this->payOrder(150, 'Tajine Agneau');
        $this->payOrder(50, 'Thé à la menthe');

        $summary = app(ReportService::class)->salesSummary(now()->startOfDay(), now());

        $this->assertSame(200.0, $summary['revenue']);
        $this->assertSame(2, $summary['orders_count']);
        $this->assertSame(100.0, $summary['average_ticket']);
        $this->assertSame(200.0, $summary['by_method']['cash']);
    }

    public function test_top_products_ranks_by_quantity_sold(): void
    {
        $this->payOrder(150, 'Tajine Agneau');
        $this->payOrder(50, 'Thé à la menthe');

        $top = app(ReportService::class)->topProducts(now()->startOfDay(), now());

        $this->assertCount(2, $top);
        $this->assertContains('Tajine Agneau', $top->pluck('product_name'));
    }

    public function test_expenses_summary_groups_by_category_and_includes_received_purchases(): void
    {
        Expense::factory()->create(['category' => 'rent', 'amount' => 3000, 'expense_date' => now(), 'created_by' => $this->manager->id]);
        Expense::factory()->create(['category' => 'utilities', 'amount' => 500, 'expense_date' => now(), 'created_by' => $this->manager->id]);

        $summary = app(ReportService::class)->expensesSummary(now()->startOfDay(), now());

        $this->assertSame(3500.0, $summary['total_expenses']);
        $this->assertSame(3000.0, $summary['by_category']['rent']);
        $this->assertSame(500.0, $summary['by_category']['utilities']);
    }

    public function test_dashboard_shows_kpis_for_a_role_with_reports_view(): void
    {
        $this->payOrder(80);

        $response = $this->actingAs($this->manager)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('CA du jour');
    }

    public function test_dashboard_hides_kpis_for_a_role_without_reports_view(): void
    {
        $serveur = User::factory()->create();
        $serveur->assignRole('serveur');

        $response = $this->actingAs($serveur)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('CA du jour');
    }

    public function test_a_role_without_reports_view_cannot_access_reports(): void
    {
        $serveur = User::factory()->create();
        $serveur->assignRole('serveur');

        $this->actingAs($serveur)->get('/reports')->assertForbidden();
    }
}
