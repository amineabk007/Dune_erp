@extends('layouts.app')

@section('title', 'Clôturer la caisse')

@section('content')
    <div class="card" style="max-width: 480px;">
        <div class="card-body">
            <p class="text-muted">Ouverte le {{ $session->opened_at->format('d/m/Y H:i') }}, fond initial {{ number_format($session->opening_cash, 2) }} DH.</p>
            <form method="POST" action="{{ route('cash-sessions.close', $session) }}">
                @csrf
                <div class="mb-3">
                    <label for="counted_cash" class="form-label">Montant compté en caisse (DH)</label>
                    <input id="counted_cash" name="counted_cash" type="number" step="0.01" min="0"
                           class="form-control @error('counted_cash') is-invalid @enderror"
                           value="{{ old('counted_cash') }}" required autofocus>
                    @error('counted_cash') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes (optionnel)</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Confirmer la clôture de la caisse ?')">
                    Clôturer la caisse
                </button>
                <a href="{{ route('cash-sessions.show', $session) }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
