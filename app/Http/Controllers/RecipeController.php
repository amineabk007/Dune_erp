<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RecipeController extends Controller implements HasMiddleware
{
    public function __construct(private readonly AuditService $audit) {}

    public static function middleware(): array
    {
        return [new Middleware('permission:recipes.manage')];
    }

    public function index(): View
    {
        $recipes = Recipe::with('product')->paginate(20);

        return view('recipes.index', compact('recipes'));
    }

    public function create(): View
    {
        return view('recipes.create', [
            'products' => Product::whereDoesntHave('recipe')->orderBy('name')->get(),
            'ingredients' => Ingredient::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        $recipe = DB::transaction(function () use ($request) {
            $recipe = Recipe::create($request->safe()->only(['product_id', 'yield_quantity', 'instructions']));

            foreach ($request->input('ingredient_id') as $index => $ingredientId) {
                $recipe->items()->create([
                    'ingredient_id' => $ingredientId,
                    'quantity' => $request->input('quantity')[$index],
                ]);
            }

            return $recipe;
        });

        $this->audit->log('create', 'recipes', $recipe, null, $recipe->only(['product_id', 'yield_quantity']));

        return redirect()->route('recipes.show', $recipe)->with('status', 'Recette créée.');
    }

    public function show(Recipe $recipe): View
    {
        return view('recipes.show', [
            'recipe' => $recipe->load('product', 'items.ingredient'),
        ]);
    }

    public function edit(Recipe $recipe): View
    {
        return view('recipes.edit', [
            'recipe' => $recipe->load('items'),
            'ingredients' => Ingredient::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        $old = $recipe->only(['yield_quantity', 'instructions']);

        DB::transaction(function () use ($request, $recipe) {
            $recipe->update($request->safe()->only(['yield_quantity', 'instructions']));

            $recipe->items()->delete();
            foreach ($request->input('ingredient_id') as $index => $ingredientId) {
                $recipe->items()->create([
                    'ingredient_id' => $ingredientId,
                    'quantity' => $request->input('quantity')[$index],
                ]);
            }
        });

        $this->audit->log('update', 'recipes', $recipe, $old, $recipe->only(['yield_quantity', 'instructions']));

        return redirect()->route('recipes.show', $recipe)->with('status', 'Recette mise à jour.');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $this->audit->log('delete', 'recipes', $recipe, $recipe->only(['product_id', 'yield_quantity']), null);
        $recipe->delete();

        return redirect()->route('recipes.index')->with('status', 'Recette supprimée.');
    }
}
