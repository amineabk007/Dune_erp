<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Dune ERP') }} — @yield('title')</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Courier New", monospace;
            max-width: 380px;
            margin: 0 auto;
            padding: 16px;
            color: #111;
        }
        h1 { font-size: 1.1rem; text-align: center; margin: 0 0 4px; text-transform: uppercase; }
        .subtitle { text-align: center; font-size: 0.75rem; margin-bottom: 12px; color: #444; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        td, th { padding: 2px 0; }
        .right { text-align: right; }
        hr { border: none; border-top: 1px dashed #999; margin: 8px 0; }
        .total-row td { font-weight: bold; font-size: 1rem; }
        .no-print { text-align: center; margin-top: 16px; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 8px; }
        }
    </style>
</head>
<body>
    @yield('content')
    <div class="no-print">
        <button onclick="window.print()">Imprimer</button>
    </div>
</body>
</html>
