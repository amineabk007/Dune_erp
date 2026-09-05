@extends('layouts.app')

@section('title', 'Nouvelle réservation')

@section('content')
    <div class="card" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST" action="{{ route('reservations.store') }}">
                @csrf
                @include('reservations._form', ['reservation' => null, 'customers' => $customers, 'tables' => $tables])
                <button type="submit" class="btn btn-primary">Créer</button>
                <a href="{{ route('reservations.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
