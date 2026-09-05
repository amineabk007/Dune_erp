@extends('layouts.app')

@section('title', 'Recette — '.$recipe->product->name)

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="h5">{{ $recipe->product->name }}</h2>
                    <p class="text-muted mb-1">Rendement : {{ $recipe->yield_quantity }} portion(s)</p>
                    <p class="mb-0">Coût matière par portion : <strong>{{ number_format($recipe->foodCostPerUnit(), 2) }} DH</strong>
                        (prix de vente : {{ number_format($recipe->product->price, 2) }} DH)</p>
                </div>
                <a href="{{ route('recipes.edit', $recipe) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Ingrédients</div>
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>Ingrédient</th><th>Quantité</th><th>Coût</th></tr>
            </thead>
            <tbody>
                @foreach ($recipe->items as $item)
                    <tr>
                        <td>{{ $item->ingredient->name }}</td>
                        <td>{{ $item->quantity }} {{ $item->ingredient->unit }}</td>
                        <td>{{ number_format($item->quantity * $item->ingredient->unit_cost, 3) }} DH</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($recipe->instructions)
        <div class="card mb-3">
            <div class="card-header">Instructions</div>
            <div class="card-body">{{ $recipe->instructions }}</div>
        </div>
    @endif

    <a href="{{ route('recipes.index') }}" class="btn btn-link ps-0">&larr; Retour aux recettes</a>
@endsection
