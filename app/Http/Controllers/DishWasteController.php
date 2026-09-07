<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Product;
use App\Services\StockService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class DishWasteController extends Controller implements HasMiddleware
{
    public function __construct(private readonly StockService $stock) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:stock.adjust'),
        ];
    }

    public function create(): View
    {
        $products = Product::whereHas('recipe')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $ingredients = Ingredient::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('stock.dish-waste', compact('products', 'ingredients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:dish,ingredient'],
            'product_id' => ['required_if:type,dish', 'nullable', 'exists:products,id'],
            'ingredient_id' => ['required_if:type,ingredient', 'nullable', 'exists:ingredients,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            if ($data['type'] === 'dish') {
                $product = Product::findOrFail($data['product_id']);
                $cost = $this->stock->recordDishWaste(
                    $product,
                    $request->user(),
                    (float) $data['quantity'],
                    $data['reason']
                );
                $label = $product->name;
            } else {
                $ingredient = Ingredient::findOrFail($data['ingredient_id']);
                $this->stock->recordWaste(
                    $ingredient,
                    $request->user(),
                    (float) $data['quantity'],
                    $data['reason']
                );
                $cost = (float) $data['quantity'] * (float) $ingredient->unit_cost;
                $label = $ingredient->name;
            }
        } catch (DomainException $e) {
            return back()->withErrors(['product_id' => $e->getMessage()])->withInput();
        }

        return back()->with('status', sprintf(
            'Perte enregistrée : %s × %s — coût perdu : %s DH.',
            rtrim(rtrim(number_format($data['quantity'], 3), '0'), '.'),
            $label,
            number_format($cost, 2)
        ));
    }
}
