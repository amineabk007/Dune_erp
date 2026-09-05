@extends('layouts.app')

@section('title', 'Nouvelle catégorie')

@section('content')
    <div class="card" style="max-width: 500px;">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.store') }}">
                @csrf
                @include('categories._form', ['category' => null])
                <button type="submit" class="btn btn-primary">Créer</button>
                <a href="{{ route('categories.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
