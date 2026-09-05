<div id="ingredient-rows">
    @php $existing = $recipe->items ?? collect(); @endphp
    @forelse ($existing as $item)
        <div class="row g-2 mb-2 ingredient-row">
            <div class="col-6">
                <select name="ingredient_id[]" class="form-select form-select-sm" required>
                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->id }}" {{ $item->ingredient_id === $ingredient->id ? 'selected' : '' }}>
                            {{ $ingredient->name }} ({{ $ingredient->unit }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-4">
                <input type="number" step="0.001" min="0.001" name="quantity[]" class="form-control form-control-sm" value="{{ $item->quantity }}" required>
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-outline-danger btn-sm remove-row">&times;</button>
            </div>
        </div>
    @empty
        <div class="row g-2 mb-2 ingredient-row">
            <div class="col-6">
                <select name="ingredient_id[]" class="form-select form-select-sm" required>
                    <option value="">— Ingrédient —</option>
                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-4">
                <input type="number" step="0.001" min="0.001" name="quantity[]" class="form-control form-control-sm" placeholder="Quantité" required>
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-outline-danger btn-sm remove-row">&times;</button>
            </div>
        </div>
    @endforelse
</div>
<button type="button" id="add-ingredient-row" class="btn btn-outline-secondary btn-sm mb-3">+ Ajouter un ingrédient</button>

<template id="ingredient-row-template">
    <div class="row g-2 mb-2 ingredient-row">
        <div class="col-6">
            <select name="ingredient_id[]" class="form-select form-select-sm" required>
                <option value="">— Ingrédient —</option>
                @foreach ($ingredients as $ingredient)
                    <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-4">
            <input type="number" step="0.001" min="0.001" name="quantity[]" class="form-control form-control-sm" placeholder="Quantité" required>
        </div>
        <div class="col-2">
            <button type="button" class="btn btn-outline-danger btn-sm remove-row">&times;</button>
        </div>
    </div>
</template>

@push('scripts')
<script>
    document.getElementById('add-ingredient-row').addEventListener('click', function () {
        const template = document.getElementById('ingredient-row-template');
        document.getElementById('ingredient-rows').appendChild(template.content.cloneNode(true));
    });
    document.getElementById('ingredient-rows').addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('.ingredient-row').remove();
        }
    });
</script>
@endpush
