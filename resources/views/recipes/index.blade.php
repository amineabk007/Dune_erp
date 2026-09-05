@extends('layouts.app')

@section('title', 'Recettes')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Recettes / fiches techniques</h2>
        <a href="{{ route('recipes.create') }}" class="btn btn-primary btn-sm">Nouvelle recette</a>
    </div>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Produit</th>
                <th>Rendement</th>
                <th>Coût matière / portion</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($recipes as $recipe)
                <tr>
                    <td><a href="{{ route('recipes.show', $recipe) }}">{{ $recipe->product->name }}</a></td>
                    <td>{{ $recipe->yield_quantity }}</td>
                    <td>{{ number_format($recipe->foodCostPerUnit(), 2) }} DH</td>
                    <td class="text-end">
                        <a href="{{ route('recipes.edit', $recipe) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
                        <form method="POST" action="{{ route('recipes.destroy', $recipe) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Supprimer cette recette ?')">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $recipes->links() }}
@endsection
