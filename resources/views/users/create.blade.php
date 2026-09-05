@extends('layouts.app')

@section('title', 'Nouvel utilisateur')

@section('content')
    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                @include('users._form', ['user' => null])
                <button type="submit" class="btn btn-primary">Créer</button>
                <a href="{{ route('users.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
