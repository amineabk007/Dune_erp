@extends('layouts.app')

@section('title', $ingredient->name)

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h5">{{ $ingredient->name }}</h2>
            <p class="mb-1">Stock actuel : <strong>{{ number_format($ingredient->current_stock, 3) }} {{ $ingredient->unit }}</strong>
                @if ($ingredient->isLowStock())
                    <span class="badge text-bg-warning badge-status">stock bas</span>
                @endif
            </p>
            <p class="mb-1">Stock minimum : {{ number_format($ingredient->minimum_stock, 3) }} {{ $ingredient->unit }}</p>
            <p class="mb-0">Coût unitaire : {{ number_format($ingredient->unit_cost, 4) }} DH / {{ $ingredient->unit }}</p>
        </div>
    </div>

    <div class="row">
        @can('stock.adjust')
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">Mouvement de stock</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('ingredients.adjust', $ingredient) }}" class="row g-2">
                            @csrf
                            <div class="col-6">
                                <select name="type" class="form-select form-select-sm">
                                    <option value="adjustment">Ajustement</option>
                                    <option value="waste">Casse</option>
                                    <option value="return">Retour</option>
                                    <option value="transfer">Transfert</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="direction" class="form-select form-select-sm">
                                    <option value="in">Entrée (+)</option>
                                    <option value="out">Sortie (-)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <input type="number" step="0.001" min="0.001" name="quantity" class="form-control form-control-sm" placeholder="Quantité" required>
                            </div>
                            <div class="col-6">
                                <input type="text" name="reason" class="form-control form-control-sm" placeholder="Motif" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-outline-primary btn-sm">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
        @can('stock.inventory')
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">Inventaire physique</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('ingredients.inventory', $ingredient) }}" class="row g-2">
                            @csrf
                            <div class="col-8">
                                <input type="number" step="0.001" min="0" name="counted_quantity" class="form-control form-control-sm" placeholder="Quantité comptée" required>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-outline-primary btn-sm">Corriger</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    </div>

    <div class="card">
        <div class="card-header">Historique des mouvements</div>
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Quantité</th>
                    <th>Motif</th>
                    <th>Utilisateur</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="badge text-bg-secondary badge-status">{{ $movement->type }}</span></td>
                        <td class="{{ $movement->quantity >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $movement->quantity >= 0 ? '+' : '' }}{{ number_format($movement->quantity, 3) }}
                        </td>
                        <td>{{ $movement->reason ?? $movement->reference ?? '—' }}</td>
                        <td>{{ $movement->user->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Aucun mouvement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $movements->links() }}
@endsection
