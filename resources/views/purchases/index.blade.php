@extends('layouts.app')

@section('title', 'Achats')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Commandes d'achat</h2>
        <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm">Nouvelle commande</a>
    </div>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Réf.</th>
                <th>Fournisseur</th>
                <th>Date</th>
                <th>Statut</th>
                <th class="text-end">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchases as $purchase)
                <tr>
                    <td><a href="{{ route('purchases.show', $purchase) }}">{{ $purchase->reference ?? '#'.$purchase->id }}</a></td>
                    <td>{{ $purchase->supplier->name }}</td>
                    <td>{{ $purchase->created_at->format('d/m/Y') }}</td>
                    <td>
                        @php
                            $badgeClass = match ($purchase->status) {
                                'received' => 'text-bg-success',
                                'cancelled' => 'text-bg-danger',
                                'ordered' => 'text-bg-info',
                                default => 'text-bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }} badge-status">{{ $purchase->status }}</span>
                    </td>
                    <td class="text-end">{{ number_format($purchase->total_cost, 2) }} DH</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">Aucune commande.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $purchases->links() }}
@endsection
