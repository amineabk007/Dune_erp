@extends('layouts.app')

@section('title', 'Nouveau client')

@section('content')
    <div class="card" style="max-width: 500px;">
        <div class="card-body">
            <form method="POST" action="{{ route('customers.store') }}">
                @csrf
                @include('customers._form', ['customer' => null])
                <button type="submit" class="btn btn-primary">Créer</button>
                <a href="{{ route('customers.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
