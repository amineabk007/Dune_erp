@extends('layouts.app')

@section('title', 'Tables')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Tables</h2>
        <a href="{{ route('tables.create') }}" class="btn btn-primary btn-sm">Nouvelle table</a>
    </div>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Table</th>
                <th>Zone</th>
                <th>Capacité</th>
                <th>Statut</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tables as $t)
                <tr>
                    <td>{{ $t->name }}</td>
                    <td>{{ $t->zone->name }}</td>
                    <td>{{ $t->capacity }}</td>
                    <td><span class="badge text-bg-secondary badge-status">{{ $t->status }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('tables.edit', $t) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
                        <form method="POST" action="{{ route('tables.destroy', $t) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Supprimer cette table ?')">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $tables->links() }}
@endsection
