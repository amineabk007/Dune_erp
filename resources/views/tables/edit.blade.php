@extends('layouts.app')

@section('title', 'Modifier la table')

@section('content')
    <div class="card" style="max-width: 500px;">
        <div class="card-body">
            <form method="POST" action="{{ route('tables.update', $table) }}">
                @csrf
                @method('PUT')
                @include('tables._form', ['table' => $table, 'zones' => $zones])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('tables.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
