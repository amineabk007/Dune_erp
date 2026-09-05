@extends('layouts.app')

@section('title', 'Modifier le produit')

@section('content')
    <div class="card" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST" action="{{ route('products.update', $product) }}">
                @csrf
                @method('PUT')
                @include('products._form', ['product' => $product, 'categories' => $categories])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('products.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
