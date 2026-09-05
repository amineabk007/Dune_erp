@extends('layouts.app')

@section('title', 'Utilisateurs')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Utilisateurs</h2>
        @can('create', App\Models\User::class)
            <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">Nouvel utilisateur</a>
        @endcan
    </div>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Nom</th>
                <th>E-mail</th>
                <th>Rôles</th>
                <th>Statut</th>
                <th>Dernière connexion</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @foreach ($user->roles as $role)
                            <span class="badge text-bg-secondary badge-status">{{ $role->name }}</span>
                        @endforeach
                    </td>
                    <td>
                        @if ($user->is_active)
                            <span class="badge text-bg-success badge-status">actif</span>
                        @else
                            <span class="badge text-bg-danger badge-status">désactivé</span>
                        @endif
                    </td>
                    <td>{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="text-end">
                        @can('update', $user)
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
                        @endcan
                        @can('deactivate', $user)
                            <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-{{ $user->is_active ? 'danger' : 'success' }} btn-sm"
                                        onclick="return confirm('Confirmer ?')">
                                    {{ $user->is_active ? 'Désactiver' : 'Réactiver' }}
                                </button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $users->links() }}
@endsection
