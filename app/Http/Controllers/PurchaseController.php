<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Models\Ingredient;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PurchaseController extends Controller implements HasMiddleware
{
    public function __construct(private readonly PurchaseService $purchases) {}

    public static function middleware(): array
    {
        return [new Middleware('permission:purchases.manage')];
    }

    public function index(): View
    {
        $purchases = Purchase::with('supplier')->latest()->paginate(20);

        return view('purchases.index', compact('purchases'));
    }

    public function create(): View
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $ingredients = Ingredient::orderBy('name')->get();

        return view('purchases.create', compact('suppliers', 'ingredients'));
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $lines = [];
        foreach ($data['ingredient_id'] as $index => $ingredientId) {
            $lines[] = [
                'ingredient_id' => $ingredientId,
                'quantity' => $data['quantity'][$index],
                'unit_cost' => $data['unit_cost'][$index],
            ];
        }

        $purchase = $this->purchases->create(
            $request->user(),
            (int) $data['supplier_id'],
            $lines,
            $data['reference'] ?? null,
            $data['notes'] ?? null,
        );

        return redirect()->route('purchases.show', $purchase)->with('status', 'Commande d\'achat créée.');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'user', 'receivedBy', 'lines.ingredient']);

        return view('purchases.show', compact('purchase'));
    }

    public function receive(Request $request, Purchase $purchase): RedirectResponse
    {
        try {
            $this->purchases->receive($purchase, $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['purchase' => $e->getMessage()]);
        }

        return redirect()->route('purchases.show', $purchase)->with('status', 'Commande réceptionnée, stock mis à jour.');
    }

    public function cancel(Request $request, Purchase $purchase): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        try {
            $this->purchases->cancel($purchase, $request->user(), $request->string('reason')->toString());
        } catch (DomainException $e) {
            return back()->withErrors(['purchase' => $e->getMessage()]);
        }

        return redirect()->route('purchases.show', $purchase)->with('status', 'Commande annulée.');
    }
}
