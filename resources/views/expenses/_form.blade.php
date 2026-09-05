@php $expense ??= null; @endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="category" class="form-label">Catégorie</label>
        <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" required>
            @foreach (\App\Models\Expense::CATEGORIES as $category)
                <option value="{{ $category }}" {{ old('category', $expense->category ?? '') === $category ? 'selected' : '' }}>{{ $category }}</option>
            @endforeach
        </select>
        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="amount" class="form-label">Montant (DH)</label>
        <input id="amount" name="amount" type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $expense->amount ?? '') }}" required>
        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <input id="description" name="description" type="text" class="form-control @error('description') is-invalid @enderror" value="{{ old('description', $expense->description ?? '') }}" required>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="expense_date" class="form-label">Date</label>
        <input id="expense_date" name="expense_date" type="date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ old('expense_date', optional($expense?->expense_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
        @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="paid_via" class="form-label">Payé via</label>
        <select id="paid_via" name="paid_via" class="form-select @error('paid_via') is-invalid @enderror" required>
            @foreach (\App\Models\Expense::PAID_VIA as $method)
                <option value="{{ $method }}" {{ old('paid_via', $expense->paid_via ?? 'bank') === $method ? 'selected' : '' }}>{{ $method }}</option>
            @endforeach
        </select>
        @error('paid_via') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="supplier_id" class="form-label">Fournisseur (optionnel)</label>
    <select id="supplier_id" name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
        <option value="">—</option>
        @foreach ($suppliers as $supplier)
            <option value="{{ $supplier->id }}" {{ (string) old('supplier_id', $expense->supplier_id ?? '') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
        @endforeach
    </select>
    @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
