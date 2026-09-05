@extends('layouts.app')

@section('title', 'Nouveau produit')

@section('content')
    <div class="card" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
                @csrf
                @include('products._form', ['product' => null, 'categories' => $categories])
                <button type="submit" class="btn btn-primary">Créer</button>
                <a href="{{ route('products.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
