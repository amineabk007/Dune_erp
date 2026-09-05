@extends('layouts.print')

@section('title', 'Rapport de caisse #'.$session->id)

@section('content')
    <h1>Dune Rooftop</h1>
    <p class="subtitle">
        Rapport de caisse #{{ $session->id }}<br>
        Ouverte le {{ $session->opened_at->format('d/m/Y H:i') }} par {{ $session->openedBy->name }}
        @if ($session->status === 'closed')
            <br>Clôturée le {{ $session->closed_at->format('d/m/Y H:i') }} par {{ $session->closedBy->name }}
        @endif
    </p>

    <hr>
    <table>
        <tr><td>Fond initial</td><td class="right">{{ number_format($session->opening_cash, 2) }} DH</td></tr>
        @php
            $cashPayments = $session->payments->where('method', 'cash')->where('refunded', false)->sum('amount');
            $cardPayments = $session->payments->where('method', 'card')->where('refunded', false)->sum('amount');
            $otherPayments = $session->payments->whereIn('method', ['transfer', 'other'])->where('refunded', false)->sum('amount');
            $cashIn = $session->movements->where('type', 'cash_in')->sum('amount');
            $cashOut = $session->movements->where('type', 'cash_out')->sum('amount');
        @endphp
        <tr><td>Paiements espèces</td><td class="right">{{ number_format($cashPayments, 2) }} DH</td></tr>
        <tr><td>Paiements carte</td><td class="right">{{ number_format($cardPayments, 2) }} DH</td></tr>
        <tr><td>Autres paiements</td><td class="right">{{ number_format($otherPayments, 2) }} DH</td></tr>
        <tr><td>Entrées de caisse</td><td class="right">+{{ number_format($cashIn, 2) }} DH</td></tr>
        <tr><td>Sorties de caisse</td><td class="right">-{{ number_format($cashOut, 2) }} DH</td></tr>
    </table>
    <hr>

    @if ($session->status === 'closed')
        <table>
            <tr><td>Attendu (espèces)</td><td class="right">{{ number_format($session->expected_cash, 2) }} DH</td></tr>
            <tr><td>Compté</td><td class="right">{{ number_format($session->counted_cash, 2) }} DH</td></tr>
            <tr class="total-row"><td>Écart</td><td class="right">{{ number_format($session->difference, 2) }} DH</td></tr>
        </table>
        <hr>
    @endif

    <p class="subtitle" style="text-align:left;">Mouvements de caisse</p>
    <table>
        @forelse ($session->movements as $movement)
            <tr>
                <td>{{ $movement->type === 'cash_in' ? 'Entrée' : 'Sortie' }} — {{ $movement->reason }}</td>
                <td class="right">{{ number_format($movement->amount, 2) }} DH</td>
            </tr>
        @empty
            <tr><td colspan="2">Aucun mouvement.</td></tr>
        @endforelse
    </table>
@endsection
