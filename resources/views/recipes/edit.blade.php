@extends('layouts.app')

@section('title', 'Modifier la recette')

@section('content')
    <div class="card" style="max-width: 700px;">
        <div class="card-body">
            <p class="text-muted">Produit : <strong>{{ $recipe->product->name }}</strong></p>
            <form method="POST" action="{{ route('recipes.update', $recipe) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="yield_quantity" class="form-label">Rendement (nombre de portions produites)</label>
                    <input id="yield_quantity" name="yield_quantity" type="number" step="0.01" min="0.01" class="form-control @error('yield_quantity') is-invalid @enderror" value="{{ old('yield_quantity', $recipe->yield_quantity) }}" required>
                    @error('yield_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <label class="form-label">Ingrédients</label>
                @include('recipes._ingredient_rows', ['recipe' => $recipe])
                @error('ingredient_id') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                <div class="mb-3">
                    <label for="instructions" class="form-label">Instructions</label>
                    <textarea id="instructions" name="instructions" class="form-control" rows="3">{{ old('instructions', $recipe->instructions) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('recipes.show', $recipe) }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
