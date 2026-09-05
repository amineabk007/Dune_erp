<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class StockMovementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:stock.view')];
    }

    public function index(Request $request): View
    {
        $movements = StockMovement::with('ingredient', 'user')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('ingredient_id'), fn ($q) => $q->where('ingredient_id', $request->integer('ingredient_id')))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('stock-movements.index', [
            'movements' => $movements,
            'types' => StockMovement::TYPES,
        ]);
    }

    public function alerts(StockService $stock): View
    {
        return view('stock-movements.alerts', ['ingredients' => $stock->lowStock()]);
    }
}
