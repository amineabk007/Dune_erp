@extends('layouts.app')

@section('title', 'Commande ' . ($purchase->reference ?? '#'.$purchase->id))

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="h5">{{ $purchase->reference ?? '#'.$purchase->id }}</h2>
                    <p class="mb-1">Fournisseur : <a href="{{ route('suppliers.show', $purchase->supplier) }}">{{ $purchase->supplier->name }}</a></p>
                    <p class="mb-1">Créée par {{ $purchase->user->name }} le {{ $purchase->created_at->format('d/m/Y H:i') }}</p>
                    @if ($purchase->received_at)
                        <p class="mb-1">Réceptionnée par {{ $purchase->receivedBy->name ?? '—' }} le {{ $purchase->received_at->format('d/m/Y H:i') }}</p>
                    @endif
                    @if ($purchase->notes)
                        <p class="mb-0 text-muted">{{ $purchase->notes }}</p>
                    @endif
                </div>
                <div class="text-end">
                    @php
                        $badgeClass = match ($purchase->status) {
                            'received' => 'text-bg-success',
                            'cancelled' => 'text-bg-danger',
                            'ordered' => 'text-bg-info',
                            default => 'text-bg-secondary',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }} badge-status mb-2">{{ $purchase->status }}</span>
                    <div class="fs-5 fw-bold">{{ number_format($purchase->total_cost, 2) }} DH</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Lignes de commande</div>
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Ingrédient</th>
                    <th class="text-end">Quantité</th>
                    <th class="text-end">Coût unitaire</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->lines as $line)
                    <tr>
                        <td>{{ $line->ingredient->name }}</td>
                        <td class="text-end">{{ number_format($line->quantity, 3) }} {{ $line->ingredient->unit }}</td>
                        <td class="text-end">{{ number_format($line->unit_cost, 4) }} DH</td>
                        <td class="text-end">{{ number_format($line->line_total, 2) }} DH</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total</th>
                    <th class="text-end">{{ number_format($purchase->total_cost, 2) }} DH</th>
                </tr>
            </tfoot>
        </table>
    </div>

    @if (in_array($purchase->status, ['draft', 'ordered']))
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('purchases.receive', $purchase) }}" onsubmit="return confirm('Confirmer la réception ? Le stock des ingrédients sera mis à jour.');">
                @csrf
                <button type="submit" class="btn btn-success">Réceptionner</button>
            </form>

            <button type="button" class="btn btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#cancel-form">Annuler la commande</button>
        </div>

        <div class="collapse mt-3" id="cancel-form">
            <div class="card card-body" style="max-width: 500px;">
                <form method="POST" action="{{ route('purchases.cancel', $purchase) }}">
                    @csrf
                    <div class="mb-2">
                        <label for="reason" class="form-label">Motif d'annulation</label>
                        <input id="reason" name="reason" type="text" class="form-control form-control-sm @error('reason') is-invalid @enderror" required>
                        @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm">Confirmer l'annulation</button>
                </form>
            </div>
        </div>
    @endif
@endsection
