@extends('layouts.app')

@section('title', 'Nouveau fournisseur')

@section('content')
    <div class="card" style="max-width: 700px;">
        <div class="card-body">
            <form method="POST" action="{{ route('suppliers.store') }}">
                @csrf
                @include('suppliers._form', ['supplier' => null])
                <button type="submit" class="btn btn-primary">Créer</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
