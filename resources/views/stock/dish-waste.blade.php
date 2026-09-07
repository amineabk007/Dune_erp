@extends('layouts.app')

@section('title', 'Déclarer une perte')

@section('content')
    <div class="card" style="max-width: 480px;">
        <div class="card-header">Déclarer une perte</div>
        <div class="card-body">
            <p class="text-muted small">
                Pour un plat déjà préparé (tombé, brûlé, retourné), les ingrédients
                de sa recette sont automatiquement décomptés du stock. Pour une
                matière première perdue directement (périmée, cassée, renversée),
                choisissez l'ingrédient concerné. Le coût perdu est calculé dans
                les deux cas et remonte dans le rapport Pertes.
            </p>

            <div class="btn-group mb-3 w-100" role="group">
                <input type="radio" class="btn-check" name="waste-mode-toggle" id="mode-dish" checked
                       onchange="document.getElementById('dish-fields').classList.remove('d-none'); document.getElementById('ingredient-fields').classList.add('d-none'); document.getElementById('type-input').value='dish';">
                <label class="btn btn-outline-secondary" for="mode-dish">Plat préparé</label>

                <input type="radio" class="btn-check" name="waste-mode-toggle" id="mode-ingredient"
                       onchange="document.getElementById('ingredient-fields').classList.remove('d-none'); document.getElementById('dish-fields').classList.add('d-none'); document.getElementById('type-input').value='ingredient';">
                <label class="btn btn-outline-secondary" for="mode-ingredient">Ingrédient brut</label>
            </div>

            <form method="POST" action="{{ route('dish-waste.store') }}">
                @csrf
                <input type="hidden" name="type" id="type-input" value="dish">

                <div id="dish-fields" class="mb-3">
                    <label class="form-label">Plat</label>
                    <select name="product_id" class="form-select">
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
                    @if ($products->isEmpty())
                        <div class="text-muted small mt-1">
                            Aucun plat n'a de recette configurée pour le moment.
                        </div>
                    @endif
                </div>

                <div id="ingredient-fields" class="mb-3 d-none">
                    <label class="form-label">Ingrédient</label>
                    <select name="ingredient_id" class="form-select">
                        <option value="">— Choisir —</option>
                        @foreach ($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}" @selected(old('ingredient_id') == $ingredient->id)>
                                {{ $ingredient->name }} ({{ $ingredient->unit }})
                            </option>
                        @endforeach
                    </select>
                    @error('ingredient_id')
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
                    <input type="text" name="reason" class="form-control" placeholder="Ex. tombé, brûlé, périmé…"
                           value="{{ old('reason') }}" required>
                </div>

                <button type="submit" class="btn btn-danger">Déclarer la perte</button>
            </form>
        </div>
    </div>
@endsection
