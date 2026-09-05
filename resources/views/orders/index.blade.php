@extends('layouts.app')

@section('title', 'Commandes')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Commandes</h2>
        @can('orders.create')
            <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm">Nouvelle commande</a>
        @endcan
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Commandes actives</option>
                @foreach (\App\Models\Order::STATUSES as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>N°</th>
                <th>Table</th>
                <th>Serveur</th>
                <th>Statut</th>
                <th>Total</th>
                <th>Payé</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->table->name ?? 'Vente directe' }}</td>
                    <td>{{ $order->server->name }}</td>
                    <td><span class="badge text-bg-secondary badge-status">{{ $order->status }}</span></td>
                    <td>{{ number_format($order->total, 2) }} DH</td>
                    <td>{{ number_format($order->amount_paid, 2) }} DH</td>
                    <td class="text-end">
                        <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-secondary btn-sm">Ouvrir</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $orders->links() }}
@endsection
