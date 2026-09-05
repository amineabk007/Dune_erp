@extends('layouts.app')

@section('title', 'Alertes stock bas')

@section('content')
    <h2 class="h5 mb-3">Ingrédients en stock bas</h2>

    @if ($ingredients->isEmpty())
        <p class="text-muted">Aucune alerte : tous les stocks sont au-dessus du minimum.</p>
    @else
        <table class="table table-striped bg-white align-middle">
            <thead>
                <tr>
                    <th>Ingrédient</th>
                    <th>Stock actuel</th>
                    <th>Stock minimum</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ingredients as $ingredient)
                    <tr>
                        <td>{{ $ingredient->name }}</td>
                        <td class="text-danger">{{ number_format($ingredient->current_stock, 3) }} {{ $ingredient->unit }}</td>
                        <td>{{ number_format($ingredient->minimum_stock, 3) }} {{ $ingredient->unit }}</td>
                        <td class="text-end">
                            <a href="{{ route('ingredients.show', $ingredient) }}" class="btn btn-outline-primary btn-sm">Gérer</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
