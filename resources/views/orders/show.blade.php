@extends('layouts.app')

@section('title', 'Commande '.$order->order_number)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h5 mb-1">{{ $order->order_number }}</h2>
            <p class="text-muted mb-0">
                {{ $order->table->name ?? 'Vente directe' }}
                @if ($order->customer) — {{ $order->customer->name }} @endif
                — servi par {{ $order->server->name }}
            </p>
        </div>
        <a href="{{ route('orders.receipt', $order) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
            Imprimer l'addition
        </a>
    </div>

    @livewire('order-builder', ['order' => $order], key('order-builder-'.$order->id))

    <a href="{{ route('orders.index') }}" class="btn btn-link ps-0">&larr; Retour aux commandes</a>
@endsection
