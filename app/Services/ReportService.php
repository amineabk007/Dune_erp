<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\RestaurantTable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private readonly CashSessionService $cashSessions,
        private readonly StockService $stock,
    ) {}

    /**
     * Revenue actually collected in the period, from non-refunded payments,
     * broken down by method — matches the figures the caisse already
     * reconciles against.
     */
    public function salesSummary(CarbonInterface $from, CarbonInterface $to): array
    {
        $payments = Payment::whereBetween('created_at', [$from, $to->endOfDay()])
            ->where('refunded', false)
            ->get();

        $paidOrders = Order::where('status', 'paid')
            ->whereBetween('updated_at', [$from, $to->endOfDay()])
            ->count();

        $revenue = (float) $payments->sum('amount');

        return [
            'revenue' => round($revenue, 2),
            'orders_count' => $paidOrders,
            'average_ticket' => $paidOrders > 0 ? round($revenue / $paidOrders, 2) : 0.0,
            'by_method' => $this->byMethod($payments),
        ];
    }

    private function byMethod(Collection $payments): array
    {
        return $payments->groupBy('method')
            ->map(fn (Collection $group) => round((float) $group->sum('amount'), 2))
            ->all();
    }

    /**
     * Best-selling products by quantity, restricted to items on paid orders
     * (cancelled items never sold, so they are excluded).
     */
    public function topProducts(CarbonInterface $from, CarbonInterface $to, int $limit = 10): Collection
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'paid')
            ->where('order_items.status', '!=', 'cancelled')
            ->whereBetween('order_items.created_at', [$from, $to->endOfDay()])
            ->selectRaw('order_items.product_name, SUM(order_items.quantity) as total_quantity, SUM(order_items.line_total) as total_revenue')
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Manual expenses and received-purchase costs recorded in the period,
     * grouped by category / supplier respectively.
     */
    public function expensesSummary(CarbonInterface $from, CarbonInterface $to): array
    {
        $expenses = Expense::whereBetween('expense_date', [$from, $to])->get();

        $purchaseCost = (float) Purchase::where('status', 'received')
            ->whereBetween('received_at', [$from, $to->endOfDay()])
            ->sum('total_cost');

        return [
            'total_expenses' => round((float) $expenses->sum('amount'), 2),
            'by_category' => $expenses->groupBy('category')
                ->map(fn (Collection $group) => round((float) $group->sum('amount'), 2))
                ->all(),
            'purchases_received' => round($purchaseCost, 2),
        ];
    }

    /**
     * Simplified P&L for the period: revenue collected minus manual expenses
     * and the cost of stock received via purchases. A V1 approximation —
     * it does not attempt accrual accounting or cost-of-goods-sold by unit
     * consumed.
     */
    public function profitAndLoss(CarbonInterface $from, CarbonInterface $to): array
    {
        $sales = $this->salesSummary($from, $to);
        $expenses = $this->expensesSummary($from, $to);

        $netResult = round($sales['revenue'] - $expenses['total_expenses'] - $expenses['purchases_received'], 2);

        return [
            'revenue' => $sales['revenue'],
            'total_expenses' => $expenses['total_expenses'],
            'purchases_received' => $expenses['purchases_received'],
            'net_result' => $netResult,
        ];
    }

    public function dashboardKpis(): array
    {
        $today = now()->startOfDay();

        $todayRevenue = (float) Payment::where('refunded', false)
            ->whereDate('created_at', $today)
            ->sum('amount');

        $openOrders = Order::whereNotIn('status', ['paid', 'cancelled'])->count();

        $totalTables = RestaurantTable::where('status', '!=', 'inactive')->count();
        $occupiedTables = RestaurantTable::where('status', 'occupied')->count();

        return [
            'today_revenue' => round($todayRevenue, 2),
            'open_orders' => $openOrders,
            'occupied_tables' => $occupiedTables,
            'total_tables' => $totalTables,
            'low_stock_count' => $this->stock->lowStock()->count(),
            'cash_session' => $this->cashSessions->currentOpenSession(),
        ];
    }
}
