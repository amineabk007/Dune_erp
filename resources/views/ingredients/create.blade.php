@extends('layouts.app')

@section('title', 'Nouvel ingrédient')

@section('content')
    <div class="card" style="max-width: 500px;">
        <div class="card-body">
            <form method="POST" action="{{ route('ingredients.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Nom</label>
                    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="unit" class="form-label">Unité</label>
                    <input id="unit" name="unit" type="text" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', 'kg') }}" required>
                    @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="current_stock" class="form-label">Stock initial</label>
                    <input id="current_stock" name="current_stock" type="number" step="0.001" min="0" class="form-control @error('current_stock') is-invalid @enderror" value="{{ old('current_stock', 0) }}" required>
                    @error('current_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="minimum_stock" class="form-label">Stock minimum (alerte)</label>
                    <input id="minimum_stock" name="minimum_stock" type="number" step="0.001" min="0" class="form-control @error('minimum_stock') is-invalid @enderror" value="{{ old('minimum_stock', 0) }}" required>
                    @error('minimum_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="unit_cost" class="form-label">Coût unitaire (DH)</label>
                    <input id="unit_cost" name="unit_cost" type="number" step="0.0001" min="0" class="form-control @error('unit_cost') is-invalid @enderror" value="{{ old('unit_cost', 0) }}" required>
                    @error('unit_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-primary">Créer</button>
                <a href="{{ route('ingredients.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
