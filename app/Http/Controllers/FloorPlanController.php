<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\Zone;
use App\Services\OrderService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class FloorPlanController extends Controller implements HasMiddleware
{
    public function __construct(private readonly OrderService $orders) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.view', only: ['index']),
            new Middleware('permission:orders.update', only: ['markAvailable', 'transfer']),
        ];
    }

    public function index(): View
    {
        $activeOrdersByTable = Order::whereNotNull('table_id')
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->get()
            ->keyBy('table_id');

        $zones = Zone::with('tables')->where('is_active', true)->orderBy('name')->get();

        return view('floor-plan.index', compact('zones', 'activeOrdersByTable'));
    }

    public function markAvailable(RestaurantTable $table): RedirectResponse
    {
        if ($table->status !== 'cleaning') {
            return back()->withErrors(['table' => 'Cette table n\'est pas en nettoyage.']);
        }

        $table->update(['status' => 'available']);

        return back()->with('status', 'Table marquée disponible.');
    }

    public function transfer(RestaurantTable $table): RedirectResponse
    {
        $order = Order::where('table_id', $table->id)->whereNotIn('status', ['paid', 'cancelled'])->firstOrFail();
        $newTableId = request()->validate(['new_table_id' => ['required', 'exists:restaurant_tables,id']])['new_table_id'];
        $newTable = RestaurantTable::findOrFail($newTableId);

        try {
            $this->orders->transferTable($order, $newTable);
        } catch (DomainException $e) {
            return back()->withErrors(['transfer' => $e->getMessage()]);
        }

        return back()->with('status', 'Table transférée.');
    }
}
