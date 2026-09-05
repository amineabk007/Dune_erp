@extends('layouts.app')

@section('title', $event->name)

@section('content')
    @php
        $badgeClass = match ($event->status) {
            'confirmed' => 'text-bg-info',
            'completed' => 'text-bg-success',
            'cancelled' => 'text-bg-danger',
            default => 'text-bg-secondary',
        };
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="h5">{{ $event->name }} <span class="badge {{ $badgeClass }} badge-status">{{ $event->status }}</span></h2>
                    <p class="mb-1">{{ $event->event_date->format('d/m/Y H:i') }} &middot; {{ $event->guest_count ?? '—' }} invités</p>
                    <p class="mb-1">Client : {{ $event->customer->name ?? '—' }}</p>
                    <p class="mb-1">Organisé par {{ $event->createdBy->name }}</p>
                    @if ($event->description)
                        <p class="mb-0 text-muted">{{ $event->description }}</p>
                    @endif
                    @if ($event->status === 'cancelled')
                        <p class="mb-0 text-danger">Annulé : {{ $event->cancel_reason }}</p>
                    @endif
                </div>
                <div class="text-end">
                    <div class="text-muted small">Montant total</div>
                    <div class="fs-5 fw-bold">{{ number_format($event->total_amount, 2) }} DH</div>
                    <div class="text-muted small mt-2">Solde dû</div>
                    <div class="fs-6 fw-bold {{ (float) $event->balanceDue() > 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($event->balanceDue(), 2) }} DH
                    </div>
                </div>
            </div>

            @if (! in_array($event->status, ['completed', 'cancelled']))
                <div class="d-flex gap-2 mt-3">
                    <a href="{{ route('events.edit', $event) }}" class="btn btn-outline-secondary btn-sm">Modifier</a>
                    @if ($event->status === 'pending')
                        <form method="POST" action="{{ route('events.transition', [$event, 'confirmed']) }}">
                            @csrf
                            <button type="submit" class="btn btn-info btn-sm">Confirmer</button>
                        </form>
                    @endif
                    @if ($event->status === 'confirmed')
                        <form method="POST" action="{{ route('events.transition', [$event, 'completed']) }}">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">Marquer terminé</button>
                        </form>
                    @endif
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#cancel-event-form">Annuler</button>
                </div>

                <div class="collapse mt-3" id="cancel-event-form">
                    <form method="POST" action="{{ route('events.transition', [$event, 'cancelled']) }}" class="row g-2" style="max-width: 500px;">
                        @csrf
                        <div class="col-8">
                            <input type="text" name="reason" class="form-control form-control-sm @error('reason') is-invalid @enderror" placeholder="Motif d'annulation" required>
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-danger btn-sm">Confirmer l'annulation</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        @if (! in_array($event->status, ['cancelled']) && (float) $event->balanceDue() > 0)
            <div class="col-md-5">
                <div class="card mb-3">
                    <div class="card-header">Enregistrer un paiement</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('events.payments.store', $event) }}" class="row g-2">
                            @csrf
                            <div class="col-6">
                                <select name="type" class="form-select form-select-sm">
                                    <option value="deposit">Acompte</option>
                                    <option value="balance">Solde</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="method" class="form-select form-select-sm">
                                    <option value="cash">Espèces</option>
                                    <option value="card">Carte</option>
                                    <option value="transfer">Virement</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                            <div class="col-8">
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" placeholder="Montant" required>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-primary btn-sm">Encaisser</button>
                            </div>
                            @error('payment') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="{{ (float) $event->balanceDue() > 0 && $event->status !== 'cancelled' ? 'col-md-7' : 'col-12' }}">
            <div class="card mb-3">
                <div class="card-header">Historique des paiements</div>
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Méthode</th>
                            <th class="text-end">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($event->payments as $payment)
                            <tr>
                                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-capitalize">{{ $payment->type }}</td>
                                <td class="text-capitalize">{{ $payment->method }}</td>
                                <td class="text-end">{{ number_format($payment->amount, 2) }} DH</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">Aucun paiement enregistré.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
