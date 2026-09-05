@extends('layouts.app')

@section('title', 'Ingrédients')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Ingrédients</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('stock-movements.alerts') }}" class="btn btn-outline-warning btn-sm">Alertes stock bas</a>
            <a href="{{ route('stock-movements.index') }}" class="btn btn-outline-secondary btn-sm">Historique mouvements</a>
            @can('stock.adjust')
                <a href="{{ route('ingredients.create') }}" class="btn btn-primary btn-sm">Nouvel ingrédient</a>
            @endcan
        </div>
    </div>

    <form method="GET" class="mb-3">
        <div class="form-check">
            <input type="checkbox" name="low_stock" value="1" class="form-check-input" id="low_stock"
                   {{ request('low_stock') ? 'checked' : '' }} onchange="this.form.submit()">
            <label for="low_stock" class="form-check-label">Afficher uniquement le stock bas</label>
        </div>
    </form>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Unité</th>
                <th>Stock actuel</th>
                <th>Stock minimum</th>
                <th>Coût unitaire</th>
                <th></th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ingredients as $ingredient)
                <tr>
                    <td><a href="{{ route('ingredients.show', $ingredient) }}">{{ $ingredient->name }}</a></td>
                    <td>{{ $ingredient->unit }}</td>
                    <td>{{ number_format($ingredient->current_stock, 3) }}</td>
                    <td>{{ number_format($ingredient->minimum_stock, 3) }}</td>
                    <td>{{ number_format($ingredient->unit_cost, 4) }} DH</td>
                    <td>
                        @if ($ingredient->isLowStock())
                            <span class="badge text-bg-warning badge-status">stock bas</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('stock.adjust')
                            <a href="{{ route('ingredients.edit', $ingredient) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
                        @endcan
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $ingredients->links() }}
@endsection
