@extends('layouts.app')

@section('title', 'Nouvelle zone')

@section('content')
    <div class="card" style="max-width: 500px;">
        <div class="card-body">
            <form method="POST" action="{{ route('zones.store') }}">
                @csrf
                @include('zones._form', ['zone' => null])
                <button type="submit" class="btn btn-primary">Créer</button>
                <a href="{{ route('zones.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
