<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#F7F4EF">
    <title>QR Code {{ $table->name }} | {{ $restaurant->name }}</title>
    <style>
        :root { color-scheme: light; font-family: Arial, sans-serif; color: #1F1F1F; background: #F7F4EF; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; }
        .qr-page { width: min(100% - 2rem, 44rem); margin: 0 auto; padding: 2rem 0 3rem; text-align: center; }
        .qr-brand { display: inline-block; color: #E36A3C; font-size: 1rem; font-weight: 700; text-decoration: none; }
        .qr-caption { margin: 1.5rem 0 .4rem; color: #555; font-size: .8rem; }
        h1 { margin: 0; font-size: 2rem; line-height: 1.15; }
        .qr-restaurant { margin: .5rem 0 1.5rem; color: #555; }
        .qr-image { display: block; width: min(74vw, 20rem); height: auto; margin: 0 auto; background: #fff; }
        .qr-instruction { margin: 1.25rem auto .5rem; font-size: 1rem; }
        .qr-url { display: inline-block; max-width: 100%; color: #1F1F1F; font-size: .85rem; overflow-wrap: anywhere; }
        .qr-code { margin-top: .5rem; color: #666; font-size: .75rem; }
        .qr-actions { display: flex; justify-content: center; flex-wrap: wrap; gap: .75rem; margin-top: 2rem; }
        .qr-actions button, .qr-actions a { display: inline-flex; min-height: 2.75rem; align-items: center; justify-content: center; padding: .65rem 1rem; border: 1px solid #1F1F1F; border-radius: 4px; background: #1F1F1F; color: #fff; font-size: .9rem; text-decoration: none; cursor: pointer; }
        .qr-actions a { background: transparent; color: #1F1F1F; }
        @media print {
            @page { size: A4; margin: 15mm; }
            :root, body { background: #fff; }
            .qr-page { width: 100%; padding: 0; }
            .qr-actions { display: none; }
            .qr-image { width: 82mm; }
            .qr-url { font-size: 9pt; }
        }
    </style>
</head>
<body>
    <main class="qr-page">
        <a class="qr-brand" href="{{ route('public.menu.show', $restaurant->slug) }}">Mesa7</a>
        <p class="qr-caption">CARDÁPIO DIGITAL</p>
        <h1>{{ $table->name }}</h1>
        <p class="qr-restaurant">{{ $restaurant->name }}</p>
        <img class="qr-image" src="{{ $qrCodeDataUri }}" alt="QR Code para abrir o cardápio da {{ $table->name }}" width="320" height="320">
        <p class="qr-instruction">Aponte a câmera do celular para acessar o cardápio.</p>
        <a class="qr-url" href="{{ $menuUrl }}">{{ $menuUrl }}</a>
        <p class="qr-code">Código interno: {{ $table->code }}</p>
        <nav class="qr-actions" aria-label="Ações do QR Code">
            <button type="button" onclick="window.print()">Imprimir</button>
            <a href="{{ route('filament.admin.resources.restaurant-tables.index', ['tenant' => $restaurant->slug]) }}">Voltar às mesas</a>
        </nav>
    </main>
</body>
</html>
