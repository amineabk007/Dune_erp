@extends('layouts.app')

@section('title', 'Dépenses')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Dépenses</h2>
        <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">Nouvelle dépense</a>
    </div>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Date</th>
                <th>Catégorie</th>
                <th>Description</th>
                <th>Fournisseur</th>
                <th class="text-end">Montant</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($expenses as $expense)
                <tr>
                    <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                    <td class="text-capitalize">{{ $expense->category }}</td>
                    <td>{{ $expense->description }}</td>
                    <td>{{ $expense->supplier->name ?? '—' }}</td>
                    <td class="text-end">{{ number_format($expense->amount, 2) }} DH</td>
                    <td class="text-end">
                        <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Aucune dépense.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $expenses->links() }}
@endsection
