@extends('layouts.app')

@section('title', 'Fournisseurs')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Fournisseurs</h2>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm">Nouveau fournisseur</a>
    </div>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Contact</th>
                <th>Téléphone</th>
                <th>Email</th>
                <th></th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($suppliers as $supplier)
                <tr>
                    <td><a href="{{ route('suppliers.show', $supplier) }}">{{ $supplier->name }}</a></td>
                    <td>{{ $supplier->contact_name ?? '—' }}</td>
                    <td>{{ $supplier->phone ?? '—' }}</td>
                    <td>{{ $supplier->email ?? '—' }}</td>
                    <td>
                        @unless ($supplier->is_active)
                            <span class="badge text-bg-secondary badge-status">inactif</span>
                        @endunless
                    </td>
                    <td class="text-end">
                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Aucun fournisseur.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $suppliers->links() }}
@endsection
