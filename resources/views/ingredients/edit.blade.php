@extends('layouts.app')

@section('title', 'Modifier l\'ingrédient')

@section('content')
    <div class="card" style="max-width: 500px;">
        <div class="card-body">
            <form method="POST" action="{{ route('ingredients.update', $ingredient) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="name" class="form-label">Nom</label>
                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $ingredient->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="unit" class="form-label">Unité</label>
                    <input id="unit" name="unit" type="text" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', $ingredient->unit) }}" required>
                    @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Stock actuel</label>
                    <input type="text" class="form-control" value="{{ number_format($ingredient->current_stock, 3) }}" disabled>
                    <div class="form-text">Le stock ne se modifie que via un mouvement (ajustement, inventaire...).</div>
                </div>
                <div class="mb-3">
                    <label for="minimum_stock" class="form-label">Stock minimum (alerte)</label>
                    <input id="minimum_stock" name="minimum_stock" type="number" step="0.001" min="0" class="form-control @error('minimum_stock') is-invalid @enderror" value="{{ old('minimum_stock', $ingredient->minimum_stock) }}" required>
                    @error('minimum_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="unit_cost" class="form-label">Coût unitaire (DH)</label>
                    <input id="unit_cost" name="unit_cost" type="number" step="0.0001" min="0" class="form-control @error('unit_cost') is-invalid @enderror" value="{{ old('unit_cost', $ingredient->unit_cost) }}" required>
                    @error('unit_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3 form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input" {{ old('is_active', $ingredient->is_active) ? 'checked' : '' }}>
                    <label for="is_active" class="form-check-label">Ingrédient actif</label>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('ingredients.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
