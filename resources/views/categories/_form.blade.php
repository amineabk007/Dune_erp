<div class="mb-3">
    <label for="name" class="form-label">Nom</label>
    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $category->name ?? '') }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="type" class="form-label">Type</label>
    <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
        @foreach (\App\Models\Category::TYPES as $type)
            <option value="{{ $type }}" {{ old('type', $category->type ?? 'food') === $type ? 'selected' : '' }}>{{ $type }}</option>
        @endforeach
    </select>
    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3 form-check">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
           {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
    <label for="is_active" class="form-check-label">Catégorie active</label>
</div>
