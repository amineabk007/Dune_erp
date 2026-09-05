<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustmentRequest;
use App\Http\Requests\StockInventoryRequest;
use App\Http\Requests\StoreIngredientRequest;
use App\Http\Requests\UpdateIngredientRequest;
use App\Models\Ingredient;
use App\Services\StockService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class IngredientController extends Controller implements HasMiddleware
{
    public function __construct(private readonly StockService $stock) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:stock.view', only: ['index', 'show']),
            new Middleware('permission:stock.adjust', only: ['create', 'store', 'edit', 'update', 'adjust']),
            new Middleware('permission:stock.inventory', only: ['inventory']),
        ];
    }

    public function index(Request $request): View
    {
        $ingredients = Ingredient::when($request->boolean('low_stock'), fn ($q) => $q->whereColumn('current_stock', '<=', 'minimum_stock'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('ingredients.index', compact('ingredients'));
    }

    public function create(): View
    {
        return view('ingredients.create');
    }

    public function store(StoreIngredientRequest $request): RedirectResponse
    {
        $ingredient = Ingredient::create([
            ...$request->safe()->except('current_stock'),
            'current_stock' => 0,
        ]);

        $opening = (float) $request->input('current_stock');
        if ($opening > 0) {
            $this->stock->move($ingredient, 'adjustment', $opening, $request->user(), 'Stock initial');
        }

        return redirect()->route('ingredients.index')->with('status', 'Ingrédient créé.');
    }

    public function show(Ingredient $ingredient): View
    {
        return view('ingredients.show', [
            'ingredient' => $ingredient,
            'movements' => $ingredient->movements()->with('user')->latest('created_at')->paginate(20),
        ]);
    }

    public function edit(Ingredient $ingredient): View
    {
        return view('ingredients.edit', compact('ingredient'));
    }

    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): RedirectResponse
    {
        $ingredient->update($request->validated());

        return redirect()->route('ingredients.index')->with('status', 'Ingrédient mis à jour.');
    }

    public function adjust(StockAdjustmentRequest $request, Ingredient $ingredient): RedirectResponse
    {
        $quantity = (float) $request->input('quantity');
        $delta = $request->input('direction') === 'out' ? -$quantity : $quantity;

        try {
            $this->stock->move($ingredient, $request->input('type'), $delta, $request->user(), $request->input('reason'));
        } catch (DomainException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('status', 'Mouvement de stock enregistré.');
    }

    public function inventory(StockInventoryRequest $request, Ingredient $ingredient): RedirectResponse
    {
        $this->stock->recordInventory($ingredient, $request->user(), (float) $request->input('counted_quantity'));

        return back()->with('status', 'Inventaire enregistré.');
    }
}
