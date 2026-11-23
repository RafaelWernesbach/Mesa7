<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#F7F4EF">
        <title>Carrinho | {{ $restaurant->name }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="public-menu" style="--menu-accent: {{ preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $restaurant->primary_color) ? $restaurant->primary_color : '#E36A3C' }}">
        <header class="menu-header">
            <div class="menu-header__inner">
                <a class="restaurant-brand" href="{{ route('public.menu.table', [$restaurant->slug, $table->uuid]) }}">
                    <span class="restaurant-brand__mark" aria-hidden="true">{{ mb_substr($restaurant->name, 0, 1) }}</span>
                    <span class="restaurant-brand__name">{{ $restaurant->name }}</span>
                </a>
                <p class="table-context"><span class="table-context__dot"></span>{{ $table->name }}</p>
            </div>
        </header>

        <main class="order-page">
            <p class="order-page__eyebrow">Pedido presencial · {{ $table->name }}</p>
            <h1>Seu carrinho.</h1>
            <p class="order-page__intro">Confira quantidades e valores antes de enviar para o estabelecimento.</p>

            @if (session('status'))
                <p class="order-notice" role="status">{{ session('status') }}</p>
            @endif

            @if ($errors->has('cart'))
                <p class="order-error" role="alert">{{ $errors->first('cart') }}</p>
            @endif

            @if ($cartItems === [])
                <section class="menu-empty">
                    <span class="menu-empty__mark" aria-hidden="true">+</span>
                    <h2>Seu carrinho está vazio.</h2>
                    <p>Volte ao cardápio e adicione os produtos que deseja pedir.</p>
                    <a class="button" href="{{ route('public.menu.table', [$restaurant->slug, $table->uuid]) }}">Voltar ao cardápio</a>
                </section>
            @else
                <ul class="cart-list">
                    @foreach ($cartItems as $item)
                        <li class="cart-item">
                            <div>
                                <h2 class="cart-item__name">{{ $item['product']->name }}</h2>
                                <p class="cart-item__price">{{ $item['quantity'] }} × {{ \App\Support\Money::formatBrazilian((string) $item['product']->price) }}</p>
                                @unless ($item['available'])
                                    <p class="order-error" role="alert">Este produto ficou indisponível. Remova-o para continuar.</p>
                                @endunless
                            </div>
                            <strong>{{ \App\Support\Money::formatBrazilian($item['lineTotal']) }}</strong>
                            <div class="cart-item__actions">
                                <form method="POST" action="{{ route('public.orders.cart.items.update', ['restaurant' => $restaurant->slug, 'uuid' => $table->uuid, 'product' => $item['product']->getKey()]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <label class="sr-only" for="quantidade-carrinho-{{ $item['product']->getKey() }}">Quantidade de {{ $item['product']->name }}</label>
                                    <input id="quantidade-carrinho-{{ $item['product']->getKey() }}" class="quantity-input" type="number" name="quantity" min="1" max="99" value="{{ $item['quantity'] }}">
                                    <button class="text-button" type="submit">Atualizar</button>
                                </form>
                                <form method="POST" action="{{ route('public.orders.cart.items.destroy', ['restaurant' => $restaurant->slug, 'uuid' => $table->uuid, 'product' => $item['product']->getKey()]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-button" type="submit">Remover</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="cart-summary"><span>Total</span><span>{{ \App\Support\Money::formatBrazilian($subtotal) }}</span></div>

                <form class="checkout-form" method="POST" action="{{ route('public.orders.store', ['restaurant' => $restaurant->slug, 'uuid' => $table->uuid]) }}">
                    @csrf
                    <label for="order-notes">Observação para o estabelecimento (opcional)</label>
                    <textarea id="order-notes" name="notes" maxlength="1000" aria-describedby="order-notes-help">{{ old('notes') }}</textarea>
                    <p id="order-notes-help">O pedido será enviado para {{ $table->name }}. O pagamento é feito diretamente no estabelecimento.</p>
                    <button class="button" type="submit" @disabled(! $canConfirm)>Confirmar pedido</button>
                </form>
            @endif
        </main>

        <footer class="menu-footer"><span>{{ $restaurant->name }}</span><span>Cardápio digital <i aria-hidden="true">·</i> Mesa7</span></footer>
    </body>
</html>
