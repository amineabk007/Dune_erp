<div class="mb-3">
    <label for="name" class="form-label">Nom</label>
    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $zone->name ?? '') }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <input id="description" name="description" type="text" class="form-control @error('description') is-invalid @enderror"
           value="{{ old('description', $zone->description ?? '') }}">
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3 form-check">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
           {{ old('is_active', $zone->is_active ?? true) ? 'checked' : '' }}>
    <label for="is_active" class="form-check-label">Zone active</label>
</div>
