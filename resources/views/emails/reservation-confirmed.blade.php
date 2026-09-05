<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #2b241d; background: #f7f3ee; padding: 24px;">
    <div style="max-width: 480px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 24px; border: 1px solid #e5ded4;">
        <h1 style="font-size: 20px; margin-top: 0;">Dune Rooftop Marrakech</h1>
        <p>Bonjour {{ $reservation->customer->name }},</p>
        <p>Votre réservation est <strong>confirmée</strong> :</p>
        <ul style="padding-left: 18px;">
            <li>Date et heure : <strong>{{ $reservation->reserved_at->format('d/m/Y à H:i') }}</strong></li>
            <li>Nombre de personnes : {{ $reservation->guests }}</li>
            @if ($reservation->notes)
                <li>Notes : {{ $reservation->notes }}</li>
            @endif
        </ul>
        <p>Nous avons hâte de vous accueillir.</p>
        <p style="color: #8a7f6e; font-size: 12px; margin-top: 24px;">
            Cet e-mail est envoyé automatiquement, merci de ne pas y répondre.
        </p>
    </div>
</body>
</html>
