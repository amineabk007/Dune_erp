@php $employee ??= null; @endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">Nom</label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $employee->name ?? '') }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="position" class="form-label">Poste</label>
        <input id="position" name="position" type="text" class="form-control @error('position') is-invalid @enderror" value="{{ old('position', $employee->position ?? '') }}" required>
        @error('position') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="phone" class="form-label">Téléphone</label>
        <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $employee->phone ?? '') }}">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $employee->email ?? '') }}">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="hire_date" class="form-label">Date d'embauche</label>
        <input id="hire_date" name="hire_date" type="date" class="form-control @error('hire_date') is-invalid @enderror" value="{{ old('hire_date', optional($employee?->hire_date)->format('Y-m-d')) }}" required>
        @error('hire_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="salary" class="form-label">Salaire mensuel (DH)</label>
        <input id="salary" name="salary" type="number" step="0.01" min="0" class="form-control @error('salary') is-invalid @enderror" value="{{ old('salary', $employee->salary ?? '') }}">
        @error('salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="user_id" class="form-label">Compte utilisateur lié (optionnel)</label>
    <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
        <option value="">—</option>
        @foreach ($users as $user)
            <option value="{{ $user->id }}" {{ (string) old('user_id', $employee->user_id ?? '') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
        @endforeach
    </select>
    @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
