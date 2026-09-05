<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #2b241d; background: #f7f3ee; padding: 24px;">
    <div style="max-width: 480px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 24px; border: 1px solid #e5ded4;">
        <h1 style="font-size: 20px; margin-top: 0; color: #b45309;">⚠ Alerte stock bas</h1>
        <p>L'ingrédient suivant vient de passer sous son seuil minimum :</p>
        <ul style="padding-left: 18px;">
            <li>Ingrédient : <strong>{{ $ingredient->name }}</strong></li>
            <li>Stock actuel : {{ number_format($ingredient->current_stock, 3) }} {{ $ingredient->unit }}</li>
            <li>Stock minimum : {{ number_format($ingredient->minimum_stock, 3) }} {{ $ingredient->unit }}</li>
        </ul>
        <p>Pensez à passer une commande d'achat auprès d'un fournisseur.</p>
        <p style="color: #8a7f6e; font-size: 12px; margin-top: 24px;">
            Cet e-mail est envoyé automatiquement par Dune ERP, merci de ne pas y répondre.
        </p>
    </div>
</body>
</html>
