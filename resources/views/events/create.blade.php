@extends('layouts.app')

@section('title', 'Nouvel événement')

@section('content')
    <div class="card" style="max-width: 700px;">
        <div class="card-body">
            <form method="POST" action="{{ route('events.store') }}">
                @csrf
                @include('events._form', ['event' => null])
                <button type="submit" class="btn btn-primary">Créer</button>
                <a href="{{ route('events.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
