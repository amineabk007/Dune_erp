@extends('layouts.app')

@section('title', 'Modifier la réservation')

@section('content')
    <div class="card" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST" action="{{ route('reservations.update', $reservation) }}">
                @csrf
                @method('PUT')
                @include('reservations._form', ['reservation' => $reservation, 'tables' => $tables])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('reservations.show', $reservation) }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
