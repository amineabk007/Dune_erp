@extends('layouts.app')

@section('title', 'Zones')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Zones</h2>
        <a href="{{ route('zones.create') }}" class="btn btn-primary btn-sm">Nouvelle zone</a>
    </div>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Description</th>
                <th>Tables</th>
                <th>Statut</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($zones as $zone)
                <tr>
                    <td>{{ $zone->name }}</td>
                    <td>{{ $zone->description ?? '—' }}</td>
                    <td>{{ $zone->tables_count }}</td>
                    <td>
                        @if ($zone->is_active)
                            <span class="badge text-bg-success badge-status">active</span>
                        @else
                            <span class="badge text-bg-secondary badge-status">inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('zones.edit', $zone) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
                        <form method="POST" action="{{ route('zones.destroy', $zone) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Supprimer cette zone ?')">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $zones->links() }}
@endsection
