<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\User;
use App\Services\CashSessionService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ReportService;
use App\Services\StockService;
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

    public function test_dashboard_kpis_include_todays_covers(): void
    {
        app(OrderService::class)->createOrder($this->manager, null, null, null, 4);
        app(OrderService::class)->createOrder($this->manager, null, null, null, 2);
        $cancelled = app(OrderService::class)->createOrder($this->manager, null, null, null, 10);
        $cancelled->update(['status' => 'cancelled']);

        $kpis = app(ReportService::class)->dashboardKpis();

        $this->assertSame(6, $kpis['today_covers']);
    }

    public function test_dashboard_kpis_include_average_service_time(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'price' => 50, 'tax_rate' => 0]);

        $orderA = app(OrderService::class)->createOrder($this->manager, null, null, null);
        app(OrderService::class)->addItem($orderA->fresh(), $product, 1);
        app(OrderService::class)->sendToProduction($orderA->fresh());
        $orderA->update(['sent_at' => now()->subMinutes(20)]);
        app(OrderService::class)->markServed($orderA->fresh());

        $orderB = app(OrderService::class)->createOrder($this->manager, null, null, null);
        app(OrderService::class)->addItem($orderB->fresh(), $product, 1);
        app(OrderService::class)->sendToProduction($orderB->fresh());
        $orderB->update(['sent_at' => now()->subMinutes(10)]);
        app(OrderService::class)->markServed($orderB->fresh());

        $kpis = app(ReportService::class)->dashboardKpis();

        $this->assertSame(15.0, $kpis['average_service_minutes']);
    }

    public function test_average_service_time_is_null_when_nothing_has_been_served_today(): void
    {
        $kpis = app(ReportService::class)->dashboardKpis();

        $this->assertNull($kpis['average_service_minutes']);
    }

    public function test_dashboard_hides_kpis_for_a_role_without_reports_view(): void
    {
        $serveur = User::factory()->create();
        $serveur->assignRole('serveur');

        $response = $this->actingAs($serveur)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('CA du jour');
    }

    public function test_waste_summary_splits_dish_waste_from_raw_ingredient_waste(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => 'Couscous']);
        $flour = Ingredient::factory()->create(['name' => 'Farine', 'current_stock' => 100, 'unit_cost' => 6]);
        $oil = Ingredient::factory()->create(['name' => 'Huile', 'current_stock' => 20, 'unit_cost' => 15]);

        $recipe = Recipe::factory()->create(['product_id' => $product->id, 'yield_quantity' => 1]);
        $recipe->items()->create(['ingredient_id' => $flour->id, 'quantity' => 1]);

        $stock = app(StockService::class);
        $stock->recordDishWaste($product, $this->manager, 2, 'Tombé'); // 2 * 1 * 6 = 12
        $stock->recordWaste($oil, $this->manager, 1, 'Renversé'); // 1 * 15 = 15

        $summary = app(ReportService::class)->wasteSummary(now()->startOfDay(), now());

        $this->assertSame(27.0, $summary['total_cost']);
        $this->assertSame(12.0, $summary['dish_cost']);
        $this->assertSame(15.0, $summary['ingredient_cost']);
        $this->assertSame(12.0, $summary['by_ingredient']['Farine']['cost']);
        $this->assertSame(15.0, $summary['by_ingredient']['Huile']['cost']);

        $this->assertCount(1, $summary['dish_events']);
        $this->assertSame('Couscous', $summary['dish_events'][0]['product']);
        $this->assertSame('Tombé', $summary['dish_events'][0]['reason']);
        $this->assertSame(12.0, $summary['dish_events'][0]['cost']);
    }

    public function test_waste_summary_lists_each_dish_declaration_separately_with_its_own_reason(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => 'Tajine']);
        $meat = Ingredient::factory()->create(['current_stock' => 50, 'unit_cost' => 20]);

        $recipe = Recipe::factory()->create(['product_id' => $product->id, 'yield_quantity' => 1]);
        $recipe->items()->create(['ingredient_id' => $meat->id, 'quantity' => 1]);

        $stock = app(StockService::class);
        $stock->recordDishWaste($product, $this->manager, 1, 'Tombé au sol');
        $stock->recordDishWaste($product, $this->manager, 1, 'Retourné par le client');

        $summary = app(ReportService::class)->wasteSummary(now()->startOfDay(), now());

        $this->assertCount(2, $summary['dish_events']);
        $reasons = collect($summary['dish_events'])->pluck('reason')->all();
        $this->assertContains('Tombé au sol', $reasons);
        $this->assertContains('Retourné par le client', $reasons);
    }

    public function test_waste_summary_falls_back_to_the_ingredients_current_cost_when_unit_cost_was_not_snapshotted(): void
    {
        $tomato = Ingredient::factory()->create(['name' => 'Tomates', 'current_stock' => 50, 'unit_cost' => 8]);

        // Simulate an old waste movement recorded before unit_cost was
        // snapshotted (unit_cost left at 0/null on the row itself).
        \App\Models\StockMovement::create([
            'ingredient_id' => $tomato->id,
            'type' => 'waste',
            'quantity' => -5,
            'unit_cost' => null,
            'reason' => 'Ancienne perte',
        ]);

        $summary = app(ReportService::class)->wasteSummary(now()->startOfDay(), now());

        // 5kg * 8 DH (current ingredient cost, used as fallback) = 40
        $this->assertSame(40.0, $summary['total_cost']);
        $this->assertSame(40.0, $summary['by_ingredient']['Tomates']['cost']);
    }

    public function test_a_role_without_reports_view_cannot_see_the_waste_report_section(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => 'Couscous']);
        $flour = Ingredient::factory()->create(['current_stock' => 100, 'unit_cost' => 6]);
        $recipe = Recipe::factory()->create(['product_id' => $product->id, 'yield_quantity' => 1]);
        $recipe->items()->create(['ingredient_id' => $flour->id, 'quantity' => 1]);

        app(StockService::class)->recordDishWaste($product, $this->manager, 1, 'Tombé');

        $this->actingAs($this->manager)->get('/reports')->assertSee('Pertes (casse)');
    }

    public function test_a_role_without_reports_view_cannot_access_reports(): void
    {
        $serveur = User::factory()->create();
        $serveur->assignRole('serveur');

        $this->actingAs($serveur)->get('/reports')->assertForbidden();
    }
}
