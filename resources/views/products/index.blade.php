@extends('layouts.app')

@section('title', 'Produits')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Produits</h2>
        @can('products.create')
            <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">Nouveau produit</a>
        @endcan
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Rechercher (nom, SKU)" value="{{ request('q') }}">
        </div>
        <div class="col-auto">
            <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Toutes les catégories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary btn-sm">Filtrer</button>
        </div>
    </form>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Nom</th>
                <th>Catégorie</th>
                <th>Prix</th>
                <th>Taxe</th>
                <th>Statut</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                    <td>{{ $product->category->name }}</td>
                    <td>{{ number_format($product->price, 2) }} DH</td>
                    <td>{{ rtrim(rtrim(number_format($product->tax_rate, 2), '0'), '.') }}%</td>
                    <td>
                        @if ($product->is_active)
                            <span class="badge text-bg-success badge-status">actif</span>
                        @else
                            <span class="badge text-bg-secondary badge-status">inactif</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('products.update')
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
                        @endcan
                        @can('products.delete')
                            <form method="POST" action="{{ route('products.destroy', $product) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Supprimer ce produit ?')">Supprimer</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $products->links() }}
@endsection
