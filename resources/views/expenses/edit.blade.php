@extends('layouts.app')

@section('title', 'Modifier la dépense')

@section('content')
    <div class="card" style="max-width: 700px;">
        <div class="card-body">
            <form method="POST" action="{{ route('expenses.update', $expense) }}">
                @csrf
                @method('PUT')
                @include('expenses._form', ['expense' => $expense])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('expenses.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
