<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CategoryController extends Controller implements HasMiddleware
{
    public function __construct(private readonly AuditService $audit) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:categories.manage'),
        ];
    }

    public function index(): View
    {
        $categories = Category::withCount('products')->orderBy('name')->paginate(20);

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        $this->audit->log('create', 'categories', $category, null, $category->only(['name', 'type', 'is_active']));

        return redirect()->route('categories.index')->with('status', 'Catégorie créée.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $old = $category->only(['name', 'type', 'is_active']);
        $category->update($request->validated());

        $this->audit->log('update', 'categories', $category, $old, $category->only(['name', 'type', 'is_active']));

        return redirect()->route('categories.index')->with('status', 'Catégorie mise à jour.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'Impossible de supprimer une catégorie qui contient des produits.']);
        }

        $this->audit->log('delete', 'categories', $category, $category->only(['name', 'type', 'is_active']), null);
        $category->delete();

        return redirect()->route('categories.index')->with('status', 'Catégorie supprimée.');
    }
}
