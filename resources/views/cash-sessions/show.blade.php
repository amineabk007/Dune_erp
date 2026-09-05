@extends('layouts.app')

@section('title', 'Session de caisse')

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1">Ouverte le {{ $session->opened_at->format('d/m/Y H:i') }} par {{ $session->openedBy->name }}</p>
                    <p class="mb-1">Fond initial : <strong>{{ number_format($session->opening_cash, 2) }} DH</strong></p>
                    @if ($session->status === 'closed')
                        <p class="mb-1">Clôturée le {{ $session->closed_at->format('d/m/Y H:i') }} par {{ $session->closedBy->name }}</p>
                        <p class="mb-1">Attendu : {{ number_format($session->expected_cash, 2) }} DH — Compté : {{ number_format($session->counted_cash, 2) }} DH</p>
                        <p class="mb-0">Écart :
                            <span class="badge {{ (float) $session->difference === 0.0 ? 'text-bg-success' : 'text-bg-warning' }}">
                                {{ number_format($session->difference, 2) }} DH
                            </span>
                        </p>
                    @endif
                </div>
                <div>
                    <a href="{{ route('cash-sessions.report', $session) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                        Rapport imprimable
                    </a>
                    @if ($session->status === 'open')
                        <span class="badge text-bg-success badge-status">ouverte</span>
                        @can('cash.close')
                            <a href="{{ route('cash-sessions.close-form', $session) }}" class="btn btn-outline-danger btn-sm ms-2">Clôturer</a>
                        @endcan
                    @else
                        <span class="badge text-bg-secondary badge-status">fermée</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($session->status === 'open')
        @can('cash.movement')
            <div class="card mb-4">
                <div class="card-header">Nouveau mouvement de caisse</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('cash-sessions.movements.store', $session) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-auto">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="cash_in">Entrée</option>
                                <option value="cash_out">Sortie</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label">Montant (DH)</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-auto">
                            <label class="form-label">Motif</label>
                            <input type="text" name="reason" class="form-control" required>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Mouvements de caisse</div>
                <ul class="list-group list-group-flush">
                    @forelse ($session->movements as $movement)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $movement->reason }} <span class="text-muted small">({{ $movement->user->name }})</span></span>
                            <span class="{{ $movement->type === 'cash_in' ? 'text-success' : 'text-danger' }}">
                                {{ $movement->type === 'cash_in' ? '+' : '-' }}{{ number_format($movement->amount, 2) }} DH
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Aucun mouvement.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Paiements de la session</div>
                <ul class="list-group list-group-flush">
                    @forelse ($session->payments as $payment)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>
                                Commande {{ $payment->order->order_number }} — {{ $payment->method }}
                                @if ($payment->refunded)
                                    <span class="badge text-bg-danger badge-status">remboursé</span>
                                @endif
                            </span>
                            <span>{{ number_format($payment->amount, 2) }} DH</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Aucun paiement.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <a href="{{ route('cash-sessions.index') }}" class="btn btn-link ps-0">&larr; Historique des sessions</a>
@endsection
