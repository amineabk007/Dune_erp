@php $supplier ??= null; @endphp

<div class="mb-3">
    <label for="name" class="form-label">Nom</label>
    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $supplier->name ?? '') }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="contact_name" class="form-label">Nom du contact</label>
    <input id="contact_name" name="contact_name" type="text" class="form-control @error('contact_name') is-invalid @enderror" value="{{ old('contact_name', $supplier->contact_name ?? '') }}">
    @error('contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="phone" class="form-label">Téléphone</label>
        <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $supplier->phone ?? '') }}">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $supplier->email ?? '') }}">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="address" class="form-label">Adresse</label>
    <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $supplier->address ?? '') }}</textarea>
    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="notes" class="form-label">Notes</label>
    <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $supplier->notes ?? '') }}</textarea>
    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

@if ($supplier)
    <div class="mb-3 form-check">
        <input type="hidden" name="is_active" value="0">
        <input id="is_active" name="is_active" type="checkbox" value="1" class="form-check-input" {{ old('is_active', $supplier->is_active) ? 'checked' : '' }}>
        <label for="is_active" class="form-check-label">Actif</label>
    </div>
@endif
