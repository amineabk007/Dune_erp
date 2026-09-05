@extends('layouts.app')

@section('title', 'Catégories')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Catégories</h2>
        <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm">Nouvelle catégorie</a>
    </div>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Type</th>
                <th>Produits</th>
                <th>Statut</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td><span class="badge text-bg-secondary badge-status">{{ $category->type }}</span></td>
                    <td>{{ $category->products_count }}</td>
                    <td>
                        @if ($category->is_active)
                            <span class="badge text-bg-success badge-status">active</span>
                        @else
                            <span class="badge text-bg-secondary badge-status">inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
                        <form method="POST" action="{{ route('categories.destroy', $category) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Supprimer cette catégorie ?')">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $categories->links() }}
@endsection
