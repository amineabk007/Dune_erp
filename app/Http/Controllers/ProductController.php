<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ProductController extends Controller implements HasMiddleware
{
    public function __construct(private readonly AuditService $audit) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:products.view', only: ['index', 'show']),
            new Middleware('permission:products.create', only: ['create', 'store']),
            new Middleware('permission:products.update', only: ['edit', 'update']),
            new Middleware('permission:products.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $products = Product::with('category')
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->string('q').'%')
                    ->orWhere('sku', 'like', '%'.$request->string('q').'%');
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('products.create', ['categories' => Category::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        $this->audit->log('create', 'products', $product, null, $product->only(['sku', 'name', 'price', 'tax_rate']));

        return redirect()->route('products.index')->with('status', 'Produit créé.');
    }

    public function show(Product $product): View
    {
        return view('products.show', [
            'product' => $product->load('category', 'priceHistories.changedBy'),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $old = $product->only(['category_id', 'sku', 'name', 'description', 'price', 'tax_rate', 'is_active']);
        $oldPrice = $product->price;

        $product->update($request->validated());

        if ((string) $oldPrice !== (string) $product->price) {
            $product->priceHistories()->create([
                'changed_by' => auth()->id(),
                'old_price' => $oldPrice,
                'new_price' => $product->price,
            ]);
        }

        $this->audit->log(
            'update',
            'products',
            $product,
            $old,
            $product->only(['category_id', 'sku', 'name', 'description', 'price', 'tax_rate', 'is_active'])
        );

        return redirect()->route('products.index')->with('status', 'Produit mis à jour.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->audit->log('delete', 'products', $product, $product->only(['sku', 'name', 'price']), null);
        $product->delete();

        return redirect()->route('products.index')->with('status', 'Produit supprimé.');
    }
}
