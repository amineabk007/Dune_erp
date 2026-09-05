@extends('layouts.app')

@section('title', 'Personnel')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Personnel</h2>
        <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">Nouvel employé</a>
    </div>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Poste</th>
                <th>Téléphone</th>
                <th>Compte utilisateur</th>
                <th></th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $employee)
                <tr>
                    <td>{{ $employee->name }}</td>
                    <td>{{ $employee->position }}</td>
                    <td>{{ $employee->phone ?? '—' }}</td>
                    <td>{{ $employee->user->name ?? '—' }}</td>
                    <td>
                        @unless ($employee->is_active)
                            <span class="badge text-bg-secondary badge-status">inactif</span>
                        @endunless
                    </td>
                    <td class="text-end">
                        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
                        <form method="POST" action="{{ route('employees.toggle-active', $employee) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-{{ $employee->is_active ? 'danger' : 'success' }} btn-sm">
                                {{ $employee->is_active ? 'Désactiver' : 'Réactiver' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Aucun employé.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $employees->links() }}
@endsection
