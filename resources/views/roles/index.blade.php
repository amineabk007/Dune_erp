@extends('layouts.app')

@section('title', 'Rôles & permissions')

@section('content')
    <p class="text-muted">
        Le rôle <strong>admin</strong> conserve toujours un accès global et n'est pas modifiable ici.
    </p>

    <div class="accordion" id="rolesAccordion">
        @foreach ($roles as $role)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#role-panel-{{ $role->id }}">
                        <span class="text-capitalize">{{ $role->name }}</span>
                        @if ($role->name === 'admin')
                            <span class="badge text-bg-dark ms-2">accès global</span>
                        @else
                            <span class="badge text-bg-light border ms-2">{{ $role->permissions->count() }} permissions</span>
                        @endif
                    </button>
                </h2>
                <div id="role-panel-{{ $role->id }}"
                     class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                     data-bs-parent="#rolesAccordion">
                    <div class="accordion-body">
                        @if ($role->name !== 'admin')
                            <form method="POST" action="{{ route('roles.update', $role) }}">
                                @csrf
                                @method('PATCH')
                                @php $rolePermissions = $role->permissions->pluck('name')->all(); @endphp

                                <div class="row row-cols-2 row-cols-md-4 g-3">
                                    @foreach ($permissions as $module => $modulePermissions)
                                        <div class="col">
                                            <div class="fw-semibold text-uppercase small text-muted mb-1">{{ $module }}</div>
                                            @foreach ($modulePermissions as $permission)
                                                <div class="form-check">
                                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                                           id="perm-{{ $role->id }}-{{ $permission->id }}"
                                                           class="form-check-input"
                                                           {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
                                                    <label for="perm-{{ $role->id }}-{{ $permission->id }}" class="form-check-label small">
                                                        {{ $permission->name }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm mt-3">Enregistrer</button>
                            </form>
                        @else
                            <span class="text-muted">Toutes les permissions.</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
