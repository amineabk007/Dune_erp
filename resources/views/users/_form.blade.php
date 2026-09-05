@php
    $userRoles = $user?->roles->pluck('name')->all() ?? [];
@endphp

<div class="mb-3">
    <label for="name" class="form-label">Nom</label>
    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $user->name ?? '') }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">E-mail</label>
    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
           value="{{ old('email', $user->email ?? '') }}" required>
    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="phone" class="form-label">Téléphone</label>
    <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror"
           value="{{ old('phone', $user->phone ?? '') }}">
    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="password" class="form-label">Mot de passe {{ $user ? '(laisser vide pour ne pas changer)' : '' }}</label>
    <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror"
           {{ $user ? '' : 'required' }}>
    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control">
</div>

<div class="mb-3">
    <label class="form-label d-block">Rôles</label>
    @foreach ($roles as $role)
        <div class="form-check form-check-inline">
            <input type="checkbox" name="roles[]" value="{{ $role->name }}" id="role-{{ $role->id }}"
                   class="form-check-input"
                   {{ in_array($role->name, old('roles', $userRoles)) ? 'checked' : '' }}>
            <label for="role-{{ $role->id }}" class="form-check-label">{{ $role->name }}</label>
        </div>
    @endforeach
    @error('roles') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

@if ($user && $user->isNot(auth()->user()))
    <div class="mb-3 form-check">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
               {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
        <label for="is_active" class="form-check-label">Compte actif</label>
    </div>
@endif
