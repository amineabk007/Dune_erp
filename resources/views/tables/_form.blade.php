<div class="mb-3">
    <label for="zone_id" class="form-label">Zone</label>
    <select id="zone_id" name="zone_id" class="form-select @error('zone_id') is-invalid @enderror" required>
        <option value="">— Choisir —</option>
        @foreach ($zones as $zone)
            <option value="{{ $zone->id }}" {{ (int) old('zone_id', $table->zone_id ?? 0) === $zone->id ? 'selected' : '' }}>
                {{ $zone->name }}
            </option>
        @endforeach
    </select>
    @error('zone_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="name" class="form-label">Nom / numéro</label>
    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $table->name ?? '') }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="capacity" class="form-label">Capacité</label>
    <input id="capacity" name="capacity" type="number" min="1" max="100" class="form-control @error('capacity') is-invalid @enderror"
           value="{{ old('capacity', $table->capacity ?? '') }}" required>
    @error('capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

@if ($table ?? null)
    <div class="mb-3">
        <label for="status" class="form-label">Statut</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
            @foreach (\App\Models\RestaurantTable::STATUSES as $status)
                <option value="{{ $status }}" {{ old('status', $table->status) === $status ? 'selected' : '' }}>{{ $status }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
@endif
