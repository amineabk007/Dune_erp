@extends('layouts.app')

@section('title', 'Mouvements de stock')

@section('content')
    <h2 class="h5 mb-3">Historique des mouvements de stock</h2>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Tous types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Date</th>
                <th>Ingrédient</th>
                <th>Type</th>
                <th>Quantité</th>
                <th>Motif / réf.</th>
                <th>Utilisateur</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($movements as $movement)
                <tr>
                    <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                    <td><a href="{{ route('ingredients.show', $movement->ingredient) }}">{{ $movement->ingredient->name }}</a></td>
                    <td><span class="badge text-bg-secondary badge-status">{{ $movement->type }}</span></td>
                    <td class="{{ $movement->quantity >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $movement->quantity >= 0 ? '+' : '' }}{{ number_format($movement->quantity, 3) }}
                    </td>
                    <td>{{ $movement->reason ?? $movement->reference ?? '—' }}</td>
                    <td>{{ $movement->user->name ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $movements->links() }}
@endsection
