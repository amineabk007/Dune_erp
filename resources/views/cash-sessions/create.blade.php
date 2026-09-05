@extends('layouts.app')

@section('title', 'Ouvrir la caisse')

@section('content')
    <div class="card" style="max-width: 420px;">
        <div class="card-body">
            <form method="POST" action="{{ route('cash-sessions.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="opening_cash" class="form-label">Fond de caisse initial (DH)</label>
                    <input id="opening_cash" name="opening_cash" type="number" step="0.01" min="0"
                           class="form-control @error('opening_cash') is-invalid @enderror"
                           value="{{ old('opening_cash', 0) }}" required autofocus>
                    @error('opening_cash') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-primary">Ouvrir la caisse</button>
            </form>
        </div>
    </div>
@endsection
