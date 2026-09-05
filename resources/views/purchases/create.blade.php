@extends('layouts.app')

@section('title', 'Nouvelle commande d\'achat')

@section('content')
    <div class="card" style="max-width: 900px;">
        <div class="card-body">
            <form method="POST" action="{{ route('purchases.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="supplier_id" class="form-label">Fournisseur</label>
                        <select id="supplier_id" name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                            <option value="">— Choisir —</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ (string) old('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="reference" class="form-label">Référence (optionnel)</label>
                        <input id="reference" name="reference" type="text" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}">
                        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <label class="form-label">Lignes de commande</label>
                <div id="purchase-lines">
                    <div class="row g-2 mb-2 purchase-line">
                        <div class="col-5">
                            <select name="ingredient_id[]" class="form-select form-select-sm" required>
                                <option value="">— Ingrédient —</option>
                                @foreach ($ingredients as $ingredient)
                                    <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-3">
                            <input type="number" step="0.001" min="0.001" name="quantity[]" class="form-control form-control-sm" placeholder="Quantité" required>
                        </div>
                        <div class="col-3">
                            <input type="number" step="0.0001" min="0" name="unit_cost[]" class="form-control form-control-sm" placeholder="Coût unitaire" required>
                        </div>
                        <div class="col-1">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-row">&times;</button>
                        </div>
                    </div>
                </div>
                <button type="button" id="add-purchase-line" class="btn btn-outline-secondary btn-sm mb-3">+ Ajouter une ligne</button>
                @error('ingredient_id') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                <template id="purchase-line-template">
                    <div class="row g-2 mb-2 purchase-line">
                        <div class="col-5">
                            <select name="ingredient_id[]" class="form-select form-select-sm" required>
                                <option value="">— Ingrédient —</option>
                                @foreach ($ingredients as $ingredient)
                                    <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-3">
                            <input type="number" step="0.001" min="0.001" name="quantity[]" class="form-control form-control-sm" placeholder="Quantité" required>
                        </div>
                        <div class="col-3">
                            <input type="number" step="0.0001" min="0" name="unit_cost[]" class="form-control form-control-sm" placeholder="Coût unitaire" required>
                        </div>
                        <div class="col-1">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-row">&times;</button>
                        </div>
                    </div>
                </template>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Créer la commande</button>
                <a href="{{ route('purchases.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('add-purchase-line').addEventListener('click', function () {
        const template = document.getElementById('purchase-line-template');
        document.getElementById('purchase-lines').appendChild(template.content.cloneNode(true));
    });
    document.getElementById('purchase-lines').addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('.purchase-line').remove();
        }
    });
</script>
@endpush
