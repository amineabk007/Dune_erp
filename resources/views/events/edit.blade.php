@extends('layouts.app')

@section('title', 'Modifier ' . $event->name)

@section('content')
    <div class="card" style="max-width: 700px;">
        <div class="card-body">
            <form method="POST" action="{{ route('events.update', $event) }}">
                @csrf
                @method('PUT')
                @include('events._form', ['event' => $event])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('events.show', $event) }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
