<div class="mb-3">
    <label for="category_id" class="form-label">Catégorie</label>
    <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
        <option value="">— Choisir —</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" {{ (int) old('category_id', $product->category_id ?? 0) === $category->id ? 'selected' : '' }}>
                {{ $category->name }} ({{ $category->type }})
            </option>
        @endforeach
    </select>
    @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="sku" class="form-label">SKU</label>
    <input id="sku" name="sku" type="text" class="form-control @error('sku') is-invalid @enderror"
           value="{{ old('sku', $product->sku ?? '') }}" required>
    @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="name" class="form-label">Nom</label>
    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $product->name ?? '') }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $product->description ?? '') }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col mb-3">
        <label for="price" class="form-label">Prix de vente (DH)</label>
        <input id="price" name="price" type="number" step="0.01" min="0" class="form-control @error('price') is-invalid @enderror"
               value="{{ old('price', $product->price ?? '') }}" required>
        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col mb-3">
        <label for="tax_rate" class="form-label">Taux de taxe (%)</label>
        <input id="tax_rate" name="tax_rate" type="number" step="0.01" min="0" max="100" class="form-control @error('tax_rate') is-invalid @enderror"
               value="{{ old('tax_rate', $product->tax_rate ?? 20) }}" required>
        @error('tax_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3 form-check">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
           {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
    <label for="is_active" class="form-check-label">Produit actif</label>
</div>
