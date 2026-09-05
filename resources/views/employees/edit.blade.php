@extends('layouts.app')

@section('title', 'Modifier ' . $employee->name)

@section('content')
    <div class="card" style="max-width: 700px;">
        <div class="card-body">
            <form method="POST" action="{{ route('employees.update', $employee) }}">
                @csrf
                @method('PUT')
                @include('employees._form', ['employee' => $employee])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('employees.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
