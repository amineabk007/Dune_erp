@extends('layouts.app')

@section('title', 'Modifier ' . $supplier->name)

@section('content')
    <div class="card" style="max-width: 700px;">
        <div class="card-body">
            <form method="POST" action="{{ route('suppliers.update', $supplier) }}">
                @csrf
                @method('PUT')
                @include('suppliers._form', ['supplier' => $supplier])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
