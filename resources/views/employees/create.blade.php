@extends('layouts.app')

@section('title', 'Nouvel employé')

@section('content')
    <div class="card" style="max-width: 700px;">
        <div class="card-body">
            <form method="POST" action="{{ route('employees.store') }}">
                @csrf
                @include('employees._form', ['employee' => null])
                <button type="submit" class="btn btn-primary">Créer</button>
                <a href="{{ route('employees.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
