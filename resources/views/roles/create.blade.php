@extends('layouts.app')

@section('title', 'Nouveau rôle')

@section('content')
    <form method="POST" action="{{ route('roles.store') }}">
        @csrf

        <div class="mb-3" style="max-width: 400px;">
            <label for="name" class="form-label">Nom du rôle</label>
            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}" placeholder="ex. voiturier" required autofocus>
            <div class="form-text">Minuscules, sans espaces ni accents (utilisé tel quel dans le code).</div>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <label class="form-label">Permissions initiales</label>
        <div class="row row-cols-2 row-cols-md-4 g-3 mb-3">
            @foreach ($permissions as $module => $modulePermissions)
                <div class="col">
                    <div class="fw-semibold text-uppercase small text-muted mb-1">{{ $module }}</div>
                    @foreach ($modulePermissions as $permission)
                        <div class="form-check">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                   id="perm-new-{{ $permission->id }}"
                                   class="form-check-input"
                                   {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}>
                            <label for="perm-new-{{ $permission->id }}" class="form-check-label small">
                                {{ $permission->name }}
                            </label>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-primary">Créer le rôle</button>
        <a href="{{ route('roles.index') }}" class="btn btn-link">Annuler</a>
    </form>
@endsection
