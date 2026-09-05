@extends('layouts.app')

@section('title', 'Caisse déjà ouverte')

@section('content')
    <div class="alert alert-info" style="max-width: 500px;">
        Une session de caisse est déjà ouverte depuis le
        {{ $session->opened_at->format('d/m/Y H:i') }} par {{ $session->openedBy->name }}.
    </div>
    <a href="{{ route('cash-sessions.show', $session) }}" class="btn btn-primary">Voir la session en cours</a>
@endsection
