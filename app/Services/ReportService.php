<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\RestaurantTable;
use App\Models\StockMovement;
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

    /**
     * Cost of everything wasted in the period (dish waste and direct
     * ingredient waste both land as StockMovement type "waste", each with
     * its unit_cost snapshotted at the time), split by source so a
     * manager can tell "burnt/dropped plates" apart from "spoiled raw
     * stock" at a glance.
     */
    public function wasteSummary(CarbonInterface $from, CarbonInterface $to): array
    {
        $movements = StockMovement::where('type', 'waste')
            ->whereBetween('created_at', [$from, $to->endOfDay()])
            ->with('ingredient')
            ->get();

        // Fall back to the ingredient's current cost for older movements
        // recorded before unit_cost was snapshotted on waste (or any row
        // where it's still 0/null) — better an approximation than a
        // silent 0.00 DH in the report.
        $cost = fn (StockMovement $m) => abs((float) $m->quantity)
            * ((float) $m->unit_cost ?: (float) ($m->ingredient->unit_cost ?? 0));

        $dishWaste = $movements->filter(fn (StockMovement $m) => $m->reference !== null);
        $ingredientWaste = $movements->filter(fn (StockMovement $m) => $m->reference === null);

        $byIngredient = $movements->groupBy(fn (StockMovement $m) => $m->ingredient->name ?? '—')
            ->map(function (Collection $group) use ($cost) {
                return [
                    'quantity' => round((float) $group->sum(fn (StockMovement $m) => abs((float) $m->quantity)), 3),
                    'cost' => round((float) $group->sum($cost), 2),
                ];
            })
            ->sortByDesc('cost');

        // Each "Déclarer une perte" of a dish creates one StockMovement per
        // recipe ingredient, all sharing the same reference (dish name),
        // reason and timestamp — grouping on that triplet reconstitutes
        // each individual declaration as one row, so a manager can see
        // exactly which dish was lost and why.
        $dishEvents = $dishWaste
            ->groupBy(fn (StockMovement $m) => $m->reference.'|'.$m->reason.'|'.$m->created_at)
            ->map(function (Collection $group) use ($cost) {
                $first = $group->first();

                return [
                    'product' => $first->reference,
                    'reason' => $first->reason,
                    'cost' => round((float) $group->sum($cost), 2),
                    'at' => $first->created_at,
                ];
            })
            ->sortByDesc('at')
            ->values();

        return [
            'total_cost' => round((float) $movements->sum($cost), 2),
            'dish_cost' => round((float) $dishWaste->sum($cost), 2),
            'ingredient_cost' => round((float) $ingredientWaste->sum($cost), 2),
            'movements_count' => $movements->count(),
            'by_ingredient' => $byIngredient,
            'dish_events' => $dishEvents,
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

        $todayCovers = (int) Order::whereDate('created_at', $today)
            ->whereNotIn('status', ['cancelled'])
            ->sum('covers');

        return [
            'today_revenue' => round($todayRevenue, 2),
            'open_orders' => $openOrders,
            'occupied_tables' => $occupiedTables,
            'total_tables' => $totalTables,
            'low_stock_count' => $this->stock->lowStock()->count(),
            'cash_session' => $this->cashSessions->currentOpenSession(),
            'today_covers' => $todayCovers,
        ];
    }
}
