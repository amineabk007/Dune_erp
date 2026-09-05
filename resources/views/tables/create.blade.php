@extends('layouts.app')

@section('title', 'Nouvelle table')

@section('content')
    <div class="card" style="max-width: 500px;">
        <div class="card-body">
            <form method="POST" action="{{ route('tables.store') }}">
                @csrf
                @include('tables._form', ['table' => null, 'zones' => $zones])
                <button type="submit" class="btn btn-primary">Créer</button>
                <a href="{{ route('tables.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
