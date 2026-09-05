@php
    $selectedTables = $reservation?->tables->pluck('id')->all() ?? [];
@endphp

@if (! $reservation)
    <div class="mb-3">
        <label for="customer_id" class="form-label">Client</label>
        <select id="customer_id" name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
            <option value="">— Choisir —</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                    {{ $customer->name }} @if ($customer->phone) ({{ $customer->phone }}) @endif
                </option>
            @endforeach
        </select>
        @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
@endif

<div class="mb-3">
    <label for="reserved_at" class="form-label">Date et heure</label>
    <input id="reserved_at" name="reserved_at" type="datetime-local" class="form-control @error('reserved_at') is-invalid @enderror"
           value="{{ old('reserved_at', $reservation?->reserved_at->format('Y-m-d\TH:i')) }}" required>
    @error('reserved_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="guests" class="form-label">Nombre de personnes</label>
    <input id="guests" name="guests" type="number" min="1" max="100" class="form-control @error('guests') is-invalid @enderror"
           value="{{ old('guests', $reservation?->guests ?? 2) }}" required>
    @error('guests') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label d-block">Table(s)</label>
    @foreach ($tables as $table)
        <div class="form-check form-check-inline">
            <input type="checkbox" name="table_ids[]" value="{{ $table->id }}" id="table-{{ $table->id }}"
                   class="form-check-input"
                   {{ in_array($table->id, old('table_ids', $selectedTables)) ? 'checked' : '' }}>
            <label for="table-{{ $table->id }}" class="form-check-label">{{ $table->zone->name }} — {{ $table->name }}</label>
        </div>
    @endforeach
    @error('table_ids') <div class="text-danger small">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="notes" class="form-label">Notes</label>
    <textarea id="notes" name="notes" class="form-control" rows="2">{{ old('notes', $reservation?->notes) }}</textarea>
</div>
