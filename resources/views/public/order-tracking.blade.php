<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#F7F4EF">
        <meta name="description" content="Acompanhe o pedido {{ $order->public_id }} de {{ $restaurant->name }}.">
        @if (in_array($order->status, [\App\Enums\OrderStatus::New, \App\Enums\OrderStatus::Preparing, \App\Enums\OrderStatus::Ready], true))
            <meta http-equiv="refresh" content="15">
        @endif
        <title>Pedido {{ $order->public_id }} | {{ $restaurant->name }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="public-menu" style="--menu-accent: {{ preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $restaurant->primary_color) ? $restaurant->primary_color : '#E36A3C' }}">
        <header class="menu-header">
            <div class="menu-header__inner">
                <a class="restaurant-brand" href="{{ route('public.menu.show', $restaurant->slug) }}">
                    <span class="restaurant-brand__mark" aria-hidden="true">{{ mb_substr($restaurant->name, 0, 1) }}</span>
                    <span class="restaurant-brand__name">{{ $restaurant->name }}</span>
                </a>
                <p class="table-context"><span class="table-context__dot"></span>{{ $order->restaurantTable?->name ?? 'Pedido presencial' }}</p>
            </div>
        </header>

        <main class="order-page">
            <p class="order-page__eyebrow">Acompanhamento do pedido · {{ $order->restaurantTable?->name ?? 'Mesa' }}</p>
            <h1>Pedido recebido.</h1>
            <p class="order-page__intro">Guarde este código para consultar o andamento do atendimento.</p>
            <p class="order-tracking__code">{{ $order->public_id }}</p>

            @if (session('order_placed'))
                <p class="order-notice" role="status">Seu pedido foi enviado ao estabelecimento.</p>
            @endif

            <p class="order-status"><span class="order-status__dot" aria-hidden="true"></span>Status: {{ $order->status->label() }}</p>

            <ol class="order-timeline" aria-label="Histórico do pedido">
                @foreach ($order->statusHistories as $history)
                    <li @class(['is-current' => $loop->last])>
                        <strong>{{ $history->status->label() }}</strong><br>
                        {{ $history->changed_at->format('d/m/Y H:i') }}
                    </li>
                @endforeach
            </ol>

            <section class="order-items">
                <h2>Itens</h2>
                @foreach ($order->items as $item)
                    <div class="order-items__row">
                        <span>{{ $item->quantity }} × {{ $item->product_name }}</span>
                        <span>{{ \App\Support\Money::formatBrazilian((string) $item->line_total) }}</span>
                    </div>
                @endforeach
                <div class="cart-summary"><span>Total</span><span>{{ \App\Support\Money::formatBrazilian((string) $order->total) }}</span></div>
            </section>

            @if ($order->status === \App\Enums\OrderStatus::Completed)
                <p class="order-notice" role="status">Atendimento finalizado. Obrigado pela visita.</p>
            @elseif ($order->status === \App\Enums\OrderStatus::Cancelled)
                <p class="order-error" role="status">Este pedido foi cancelado. Fale com a equipe do estabelecimento se precisar de ajuda.</p>
            @else
                <p class="order-page__intro">Esta página atualiza o status automaticamente a cada 15 segundos.</p>
            @endif

            <a class="order-back" href="{{ $order->restaurantTable ? route('public.menu.table', [$restaurant->slug, $order->restaurantTable->uuid]) : route('public.menu.show', $restaurant->slug) }}">Voltar ao cardápio</a>
        </main>

        <footer class="menu-footer"><span>{{ $restaurant->name }}</span><span>Cardápio digital <i aria-hidden="true">·</i> Mesa7</span></footer>
    </body>
</html>
