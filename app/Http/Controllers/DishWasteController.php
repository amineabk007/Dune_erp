<?php

namespace App\Http\Controllers;

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

        return view('stock.dish-waste', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['product_id']);

        try {
            $cost = $this->stock->recordDishWaste(
                $product,
                $request->user(),
                (float) $data['quantity'],
                $data['reason']
            );
        } catch (DomainException $e) {
            return back()->withErrors(['product_id' => $e->getMessage()])->withInput();
        }

        return back()->with('status', sprintf(
            'Perte enregistrée : %s × %s — coût matière perdu : %s DH.',
            rtrim(rtrim(number_format($data['quantity'], 3), '0'), '.'),
            $product->name,
            number_format($cost, 2)
        ));
    }
}
