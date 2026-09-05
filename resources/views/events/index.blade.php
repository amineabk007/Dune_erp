@extends('layouts.app')

@section('title', 'Événements')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Événements</h2>
        <a href="{{ route('events.create') }}" class="btn btn-primary btn-sm">Nouvel événement</a>
    </div>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Date</th>
                <th>Client</th>
                <th>Statut</th>
                <th class="text-end">Montant</th>
                <th class="text-end">Solde dû</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($events as $event)
                <tr>
                    <td><a href="{{ route('events.show', $event) }}">{{ $event->name }}</a></td>
                    <td>{{ $event->event_date->format('d/m/Y H:i') }}</td>
                    <td>{{ $event->customer->name ?? '—' }}</td>
                    <td>
                        @php
                            $badgeClass = match ($event->status) {
                                'confirmed' => 'text-bg-info',
                                'completed' => 'text-bg-success',
                                'cancelled' => 'text-bg-danger',
                                default => 'text-bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }} badge-status">{{ $event->status }}</span>
                    </td>
                    <td class="text-end">{{ number_format($event->total_amount, 2) }} DH</td>
                    <td class="text-end">{{ number_format($event->balanceDue(), 2) }} DH</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">Aucun événement.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $events->links() }}
@endsection
