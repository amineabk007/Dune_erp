@php $event ??= null; @endphp

<div class="mb-3">
    <label for="name" class="form-label">Nom de l'événement</label>
    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $event->name ?? '') }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="event_date" class="form-label">Date et heure</label>
        <input id="event_date" name="event_date" type="datetime-local" class="form-control @error('event_date') is-invalid @enderror" value="{{ old('event_date', optional($event?->event_date)->format('Y-m-d\TH:i')) }}" required>
        @error('event_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="guest_count" class="form-label">Nombre d'invités</label>
        <input id="guest_count" name="guest_count" type="number" min="1" class="form-control @error('guest_count') is-invalid @enderror" value="{{ old('guest_count', $event->guest_count ?? '') }}">
        @error('guest_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="customer_id" class="form-label">Client (optionnel)</label>
        <select id="customer_id" name="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" {{ (string) old('customer_id', $event->customer_id ?? '') === (string) $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
            @endforeach
        </select>
        @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="total_amount" class="form-label">Montant total du devis (DH)</label>
        <input id="total_amount" name="total_amount" type="number" step="0.01" min="0" class="form-control @error('total_amount') is-invalid @enderror" value="{{ old('total_amount', $event->total_amount ?? '') }}" required>
        @error('total_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $event->description ?? '') }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
