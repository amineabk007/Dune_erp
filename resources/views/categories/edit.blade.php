@extends('layouts.app')

@section('title', 'Modifier la catégorie')

@section('content')
    <div class="card" style="max-width: 500px;">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.update', $category) }}">
                @csrf
                @method('PUT')
                @include('categories._form', ['category' => $category])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('categories.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
