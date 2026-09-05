@extends('layouts.app')

@section('title', 'Modifier l\'utilisateur')

@section('content')
    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')
                @include('users._form', ['user' => $user])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('users.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
