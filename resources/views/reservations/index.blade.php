@extends('layouts.app')

@section('title', 'Réservations')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Réservations</h2>
        @can('reservations.create')
            <a href="{{ route('reservations.create') }}" class="btn btn-primary btn-sm">Nouvelle réservation</a>
        @endcan
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date', now()->toDateString()) }}" onchange="this.form.submit()">
        </div>
    </form>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Heure</th>
                <th>Client</th>
                <th>Personnes</th>
                <th>Tables</th>
                <th>Statut</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reservations as $reservation)
                <tr>
                    <td>{{ $reservation->reserved_at->format('H:i') }}</td>
                    <td>{{ $reservation->customer->name }}</td>
                    <td>{{ $reservation->guests }}</td>
                    <td>{{ $reservation->tables->pluck('name')->join(', ') }}</td>
                    <td><span class="badge text-bg-secondary badge-status">{{ $reservation->status }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('reservations.show', $reservation) }}" class="btn btn-outline-secondary btn-sm">Détails</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $reservations->links() }}
@endsection
