@extends('layouts.print')

@section('title', 'Addition '.$order->order_number)

@section('content')
    <h1>Dune Rooftop</h1>
    <p class="subtitle">
        Addition {{ $order->order_number }}<br>
        {{ $order->created_at->format('d/m/Y H:i') }}<br>
        {{ $order->table->name ?? 'Vente directe' }}
        @if ($order->customer) — {{ $order->customer->name }} @endif
    </p>

    <hr>
    <table>
        @foreach ($order->items->where('status', '!=', 'cancelled') as $item)
            <tr>
                <td>{{ $item->quantity }} × {{ $item->product_name }}</td>
                <td class="right">{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
    </table>
    <hr>
    <table>
        <tr><td>Sous-total</td><td class="right">{{ number_format($order->subtotal, 2) }} DH</td></tr>
        @if ($order->discount_amount > 0)
            <tr><td>Remise</td><td class="right">-{{ number_format($order->discount_amount, 2) }} DH</td></tr>
        @endif
        <tr><td>Taxe</td><td class="right">{{ number_format($order->tax_amount, 2) }} DH</td></tr>
        <tr class="total-row"><td>Total</td><td class="right">{{ number_format($order->total, 2) }} DH</td></tr>
    </table>

    @if ($order->payments->isNotEmpty())
        <hr>
        <p class="subtitle" style="text-align:left;">Paiements</p>
        <table>
            @foreach ($order->payments as $payment)
                <tr>
                    <td>{{ $payment->method }} @if ($payment->refunded) (remboursé) @endif</td>
                    <td class="right">{{ number_format($payment->amount, 2) }} DH</td>
                </tr>
            @endforeach
        </table>
    @endif

    <hr>
    <p class="subtitle">Merci de votre visite — Dune Rooftop Marrakech</p>
@endsection
