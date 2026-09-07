@extends('layouts.app')

@section('title', 'Déclarer une perte (plat)')

@section('content')
    <div class="card" style="max-width: 480px;">
        <div class="card-header">Déclarer une perte de plat préparé</div>
        <div class="card-body">
            <p class="text-muted small">
                Pour un plat tombé, brûlé, ou retourné en cuisine — les ingrédients
                de la recette sont automatiquement décomptés du stock et le coût
                matière perdu est calculé.
            </p>

            <form method="POST" action="{{ route('dish-waste.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Plat</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">— Choisir —</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Quantité</label>
                    <input type="number" step="0.01" min="0.01" name="quantity" class="form-control"
                           value="{{ old('quantity', 1) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Motif</label>
                    <input type="text" name="reason" class="form-control" placeholder="Ex. tombé, brûlé, erreur commande…"
                           value="{{ old('reason') }}" required>
                </div>

                <button type="submit" class="btn btn-danger">Déclarer la perte</button>
            </form>
        </div>
    </div>
@endsection
