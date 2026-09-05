@extends('layouts.app')

@section('title', 'Modifier le client')

@section('content')
    <div class="card" style="max-width: 500px;">
        <div class="card-body">
            <form method="POST" action="{{ route('customers.update', $customer) }}">
                @csrf
                @method('PUT')
                @include('customers._form', ['customer' => $customer])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('customers.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
