@extends('layouts.app')

@section('title', 'Rapports')

@section('content')
    <form method="GET" class="row g-2 mb-4 align-items-end">
        <div class="col-auto">
            <label for="from" class="form-label small mb-0">Du</label>
            <input type="date" id="from" name="from" class="form-control form-control-sm" value="{{ $from->format('Y-m-d') }}">
        </div>
        <div class="col-auto">
            <label for="to" class="form-label small mb-0">Au</label>
            <input type="date" id="to" name="to" class="form-control form-control-sm" value="{{ $to->format('Y-m-d') }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Chiffre d'affaires encaissé</div>
                    <div class="fs-4 fw-bold">{{ number_format($sales['revenue'], 2) }} DH</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Commandes payées</div>
                    <div class="fs-4 fw-bold">{{ $sales['orders_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Ticket moyen</div>
                    <div class="fs-4 fw-bold">{{ number_format($sales['average_ticket'], 2) }} DH</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Résultat net (approx.)</div>
                    <div class="fs-4 fw-bold {{ $pnl['net_result'] < 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($pnl['net_result'], 2) }} DH
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Ventes par mode de paiement</div>
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse ($sales['by_method'] as $method => $amount)
                            <tr>
                                <td class="text-capitalize">{{ $method }}</td>
                                <td class="text-end">{{ number_format($amount, 2) }} DH</td>
                            </tr>
                        @empty
                            <tr><td class="text-muted">Aucun paiement sur la période.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Dépenses par catégorie</div>
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse ($expenses['by_category'] as $category => $amount)
                            <tr>
                                <td class="text-capitalize">{{ $category }}</td>
                                <td class="text-end">{{ number_format($amount, 2) }} DH</td>
                            </tr>
                        @empty
                            <tr><td class="text-muted">Aucune dépense sur la période.</td></tr>
                        @endforelse
                        <tr>
                            <td>Achats réceptionnés</td>
                            <td class="text-end">{{ number_format($expenses['purchases_received'], 2) }} DH</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Produits les plus vendus</div>
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th class="text-end">Quantité vendue</th>
                    <th class="text-end">Chiffre d'affaires</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($topProducts as $product)
                    <tr>
                        <td>{{ $product->product_name }}</td>
                        <td class="text-end">{{ number_format($product->total_quantity, 2) }}</td>
                        <td class="text-end">{{ number_format($product->total_revenue, 2) }} DH</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-muted">Aucune vente sur la période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
