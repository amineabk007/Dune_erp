@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="card">
        <div class="card-body">
            <h2 class="h5">Bienvenue, {{ $user->name }}</h2>
            <p class="text-muted">
                Rôle(s) : {{ $roles->implode(', ') ?: 'aucun' }}
            </p>
            <p class="mb-0">
                Ce tableau de bord opérationnel (CA, tickets, occupation, alertes stock) sera activé
                au fil des prochaines phases, une fois les modules commande / caisse / stock en place.
            </p>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">Permissions accordées</div>
        <div class="card-body">
            @if ($permissions->isEmpty())
                <p class="text-muted mb-0">Aucune permission accordée pour le moment.</p>
            @else
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($permissions as $permission)
                        <span class="badge text-bg-light border">{{ $permission }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
