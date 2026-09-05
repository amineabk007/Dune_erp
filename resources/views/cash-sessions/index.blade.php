@extends('layouts.app')

@section('title', 'Sessions de caisse')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Sessions de caisse</h2>
        @can('cash.open')
            <a href="{{ route('cash-sessions.create') }}" class="btn btn-primary btn-sm">Ouvrir la caisse</a>
        @endcan
    </div>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Ouverture</th>
                <th>Ouverte par</th>
                <th>Fond initial</th>
                <th>Clôture</th>
                <th>Écart</th>
                <th>Statut</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sessions as $session)
                <tr>
                    <td>{{ $session->opened_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $session->openedBy->name }}</td>
                    <td>{{ number_format($session->opening_cash, 2) }} DH</td>
                    <td>{{ $session->closed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>
                        @if (! is_null($session->difference))
                            <span class="badge {{ (float) $session->difference === 0.0 ? 'text-bg-success' : 'text-bg-warning' }}">
                                {{ number_format($session->difference, 2) }} DH
                            </span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if ($session->status === 'open')
                            <span class="badge text-bg-success badge-status">ouverte</span>
                        @else
                            <span class="badge text-bg-secondary badge-status">fermée</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('cash-sessions.show', $session) }}" class="btn btn-outline-secondary btn-sm">Détails</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $sessions->links() }}
@endsection
