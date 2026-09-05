@extends('layouts.app')

@section('title', $supplier->name)

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="h5">{{ $supplier->name }}
                        @unless ($supplier->is_active)
                            <span class="badge text-bg-secondary badge-status">inactif</span>
                        @endunless
                    </h2>
                    <p class="mb-1 text-muted">{{ $supplier->contact_name }}</p>
                    <p class="mb-1">{{ $supplier->phone }} @if ($supplier->phone && $supplier->email) &middot; @endif {{ $supplier->email }}</p>
                    @if ($supplier->address)
                        <p class="mb-0">{{ $supplier->address }}</p>
                    @endif
                </div>
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            Historique des commandes
            @can('purchases.manage')
                <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm">Nouvelle commande</a>
            @endcan
        </div>
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Réf.</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th class="text-end">Montant</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchases as $purchase)
                    <tr>
                        <td><a href="{{ route('purchases.show', $purchase) }}">{{ $purchase->reference ?? '#'.$purchase->id }}</a></td>
                        <td>{{ $purchase->created_at->format('d/m/Y') }}</td>
                        <td><span class="badge text-bg-secondary badge-status">{{ $purchase->status }}</span></td>
                        <td class="text-end">{{ number_format($purchase->total_cost, 2) }} DH</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">Aucune commande.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $purchases->links() }}
@endsection
