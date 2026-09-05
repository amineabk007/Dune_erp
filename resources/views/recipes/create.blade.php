@extends('layouts.app')

@section('title', 'Nouvelle recette')

@section('content')
    <div class="card" style="max-width: 700px;">
        <div class="card-body">
            <form method="POST" action="{{ route('recipes.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="product_id" class="form-label">Produit</label>
                    <select id="product_id" name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                        <option value="">— Choisir —</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" {{ (string) old('product_id') === (string) $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                        @endforeach
                    </select>
                    @error('product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="yield_quantity" class="form-label">Rendement (nombre de portions produites)</label>
                    <input id="yield_quantity" name="yield_quantity" type="number" step="0.01" min="0.01" class="form-control @error('yield_quantity') is-invalid @enderror" value="{{ old('yield_quantity', 1) }}" required>
                    @error('yield_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <label class="form-label">Ingrédients</label>
                @include('recipes._ingredient_rows', ['recipe' => null])
                @error('ingredient_id') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                <div class="mb-3">
                    <label for="instructions" class="form-label">Instructions</label>
                    <textarea id="instructions" name="instructions" class="form-control" rows="3">{{ old('instructions') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Créer</button>
                <a href="{{ route('recipes.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
