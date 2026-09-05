@extends('layouts.app')

@section('title', 'Modifier la zone')

@section('content')
    <div class="card" style="max-width: 500px;">
        <div class="card-body">
            <form method="POST" action="{{ route('zones.update', $zone) }}">
                @csrf
                @method('PUT')
                @include('zones._form', ['zone' => $zone])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('zones.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
