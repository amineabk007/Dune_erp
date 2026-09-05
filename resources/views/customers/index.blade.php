@extends('layouts.app')

@section('title', 'Clients')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Clients</h2>
        <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">Nouveau client</a>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Rechercher (nom, tél, e-mail)" value="{{ request('q') }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary btn-sm">Rechercher</button>
        </div>
    </form>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Téléphone</th>
                <th>E-mail</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($customers as $customer)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->phone ?? '—' }}</td>
                    <td>{{ $customer->email ?? '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Supprimer ce client ?')">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $customers->links() }}
@endsection
