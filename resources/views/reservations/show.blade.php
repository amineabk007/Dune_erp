@extends('layouts.app')

@section('title', 'Réservation')

@section('content')
    <div class="card mb-3" style="max-width: 600px;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="h5 mb-1">{{ $reservation->customer->name }}</h2>
                    <p class="text-muted mb-1">{{ $reservation->reserved_at->format('d/m/Y H:i') }} — {{ $reservation->guests }} personnes</p>
                    <p class="mb-1">Tables : {{ $reservation->tables->pluck('name')->join(', ') ?: '—' }}</p>
                    @if ($reservation->notes)
                        <p class="mb-0 text-muted">{{ $reservation->notes }}</p>
                    @endif
                </div>
                <span class="badge text-bg-secondary badge-status fs-6">{{ $reservation->status }}</span>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        @can('reservations.update')
            @if (in_array($reservation->status, ['pending', 'confirmed']))
                <a href="{{ route('reservations.edit', $reservation) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
            @endif
            @if ($reservation->status === 'pending')
                <form method="POST" action="{{ route('reservations.confirm', $reservation) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Confirmer</button>
                </form>
            @endif
            @if ($reservation->status === 'confirmed')
                <form method="POST" action="{{ route('reservations.seat', $reservation) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Installer (seated)</button>
                </form>
            @endif
            @if ($reservation->status === 'seated' && ! $reservation->order_id)
                <form method="POST" action="{{ route('reservations.create-order', $reservation) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Créer la commande</button>
                </form>
            @endif
            @if ($reservation->status === 'seated')
                <form method="POST" action="{{ route('reservations.complete', $reservation) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Terminer</button>
                </form>
            @endif
        @endcan
        @can('reservations.cancel')
            @if (in_array($reservation->status, ['pending', 'confirmed']))
                <form method="POST" action="{{ route('reservations.no-show', $reservation) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm">No-show</button>
                </form>
            @endif
            @if (in_array($reservation->status, ['pending', 'confirmed', 'seated']))
                <form method="POST" action="{{ route('reservations.cancel', $reservation) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Annuler cette réservation ?')">Annuler</button>
                </form>
            @endif
        @endcan
    </div>

    @if ($reservation->order)
        <p><a href="{{ route('orders.show', $reservation->order) }}" class="btn btn-outline-primary btn-sm">Voir la commande {{ $reservation->order->order_number }}</a></p>
    @endif

    <a href="{{ route('reservations.index') }}" class="btn btn-link ps-0">&larr; Retour aux réservations</a>
@endsection
