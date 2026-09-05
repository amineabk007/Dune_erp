@extends('layouts.app')

@section('title', 'Nouvelle commande')

@section('content')
    <div class="card" style="max-width: 500px;">
        <div class="card-body">
            <form method="POST" action="{{ route('orders.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="table_id" class="form-label">Table (laisser vide pour une vente directe)</label>
                    <select id="table_id" name="table_id" class="form-select @error('table_id') is-invalid @enderror">
                        <option value="">— Vente directe —</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}" {{ (string) old('table_id') === (string) $table->id ? 'selected' : '' }}>
                                {{ $table->zone->name }} — {{ $table->name }} ({{ $table->capacity }} pers.)
                            </option>
                        @endforeach
                    </select>
                    @error('table_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Créer la commande</button>
                <a href="{{ route('orders.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection
