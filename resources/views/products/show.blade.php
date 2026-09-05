@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="card mb-4">
        <div class="card-body d-flex gap-3">
            @if ($product->photo_url)
                <img src="{{ $product->photo_url }}" alt="{{ $product->name }}" style="width: 120px; height: 120px; object-fit: cover;" class="rounded border flex-shrink-0">
            @endif
            <div>
                <h2 class="h5">{{ $product->name }}</h2>
                <p class="text-muted mb-1">SKU : {{ $product->sku }} — Catégorie : {{ $product->category->name }}</p>
                <p class="mb-1">Prix actuel : <strong>{{ number_format($product->price, 2) }} DH</strong> (taxe {{ $product->tax_rate }}%)</p>
                <p class="mb-0">
                    @if ($product->is_active)
                        <span class="badge text-bg-success badge-status">actif</span>
                    @else
                        <span class="badge text-bg-secondary badge-status">inactif</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Historique des prix</div>
        <div class="card-body">
            @if ($product->priceHistories->isEmpty())
                <p class="text-muted mb-0">Aucun changement de prix enregistré.</p>
            @else
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Ancien prix</th>
                            <th>Nouveau prix</th>
                            <th>Modifié par</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($product->priceHistories as $history)
                            <tr>
                                <td>{{ $history->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ number_format($history->old_price, 2) }} DH</td>
                                <td>{{ number_format($history->new_price, 2) }} DH</td>
                                <td>{{ $history->changedBy?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <a href="{{ route('products.index') }}" class="btn btn-link mt-3 ps-0">&larr; Retour aux produits</a>
@endsection
