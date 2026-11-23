<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#F7F4EF">
        <meta name="description" content="Confira o cardápio de {{ $restaurant->name }}.">
        <title>Cardápio | {{ $restaurant->name }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="public-menu" @if ($table) data-cart-key="mesa7.cart.{{ $restaurant->getKey() }}.{{ $table->uuid }}" @endif style="--menu-accent: {{ $primaryColor }}; --menu-sage: {{ $restaurant->menuSecondaryColor() }}">
        <header class="menu-header">
            <div class="menu-header__inner">
                <a class="restaurant-brand" href="{{ route('public.menu.show', $restaurant->slug) }}" aria-label="Cardápio de {{ $restaurant->name }}">
                    @if ($restaurant->logo_path)
                        <img class="restaurant-brand__logo" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($restaurant->logo_path) }}" alt="Logo de {{ $restaurant->name }}">
                    @else
                        <span class="restaurant-brand__mark" aria-hidden="true">{{ mb_substr($restaurant->name, 0, 1) }}</span>
                    @endif
                    <span class="restaurant-brand__name">{{ $restaurant->name }}</span>
                </a>

                @if ($table)
                    <div class="menu-header__actions">
                        <p class="table-context"><span class="table-context__dot"></span>{{ $table->name }}</p>
                        <a class="cart-link" href="{{ route('public.orders.cart', ['restaurant' => $restaurant->slug, 'uuid' => $table->uuid]) }}">
                            Carrinho <span class="cart-link__count" data-cart-count aria-label="{{ $cartCount }} itens">{{ $cartCount }}</span>
                        </a>
                    </div>
                @elseif ($restaurant->phone)
                    <a class="header-contact" href="tel:{{ preg_replace('/[^0-9+]/', '', $restaurant->phone) }}">{{ $restaurant->phone }}</a>
                @endif
            </div>
        </header>

        <main>
            @if ($table)
                <p class="cart-feedback" data-cart-feedback role="status" aria-live="polite">{{ $errors->first() ?: session('status') }}</p>
            @endif
            <section class="menu-intro" aria-labelledby="menu-title">
                <div class="menu-intro__copy">
                    <p class="eyebrow">{{ $table ? 'CARDÁPIO DA MESA' : 'FEITO NA CASA' }}</p>
                    <h1 id="menu-title">Nosso<br><span>cardápio.</span></h1>
                    @if ($restaurant->description)
                        <p class="menu-intro__description">{{ $restaurant->description }}</p>
                    @else
                        <p class="menu-intro__description">Escolhas da casa, preparadas com cuidado e servidas para aproveitar cada momento.</p>
                    @endif
                </div>
                <div class="menu-intro__aside" aria-hidden="true">
                    <span class="menu-intro__number">07</span>
                    <span class="menu-intro__rule"></span>
                    <span class="menu-intro__aside-label">BOM DE<br>COMPARTILHAR</span>
                </div>
            </section>

            @if ($table && request()->routeIs('public.menu.table'))
                <section class="table-orders" aria-labelledby="table-orders-title" data-table-orders-url="{{ route('public.orders.index', [$restaurant->slug, $table->uuid]) }}">
                    <div class="table-orders__heading">
                        <div>
                            <p class="eyebrow">{{ $table->name }}</p>
                            <h2 id="table-orders-title">Pedidos da mesa</h2>
                        </div>
                        <p data-orders-message>Os status são atualizados automaticamente.</p>
                    </div>
                    <div data-orders-list>
                    @forelse ($tableOrders as $order)
                        <a class="table-orders__item" href="{{ route('public.orders.track', [$restaurant->slug, $order->public_id]) }}" data-order-id="{{ $order->public_id }}">
                            <span class="table-orders__details">
                                <strong>{{ $order->public_id }}</strong>
                                <span>{{ $order->placed_at->format('d/m/Y H:i') }}</span>
                            </span>
                            <span class="table-orders__status">{{ $order->status->label() }}</span>
                            <span class="table-orders__action">Acompanhar <span aria-hidden="true">→</span></span>
                        </a>
                    @empty
                        <p class="table-orders__empty">Ainda não há pedidos para esta mesa.</p>
                    @endforelse
                    </div>
                </section>
            @endif

            @if ($categories->isNotEmpty() || $uncategorizedProducts->isNotEmpty())
                <nav class="category-nav" aria-label="Categorias do cardápio">
                    @foreach ($categories as $category)
                        <a href="#categoria-{{ $category->slug }}">{{ $category->name }}</a>
                    @endforeach
                    @if ($uncategorizedProducts->isNotEmpty())
                        <a href="#categoria-outros">Outros</a>
                    @endif
                </nav>

                <div class="menu-content">
                    @foreach ($categories as $category)
                        <section class="menu-section" id="categoria-{{ $category->slug }}" aria-labelledby="titulo-{{ $category->slug }}">
                            <div class="menu-section__heading">
                                <span class="section-index">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <h2 id="titulo-{{ $category->slug }}">{{ $category->name }}</h2>
                                <span class="menu-section__count">{{ str_pad((string) $category->products->count(), 2, '0', STR_PAD_LEFT) }} ITENS</span>
                            </div>
                            <div class="product-list">
                                @foreach ($category->products as $product)
                                    @include('public.partials.product', ['product' => $product])
                                @endforeach
                            </div>
                        </section>
                    @endforeach

                    @if ($uncategorizedProducts->isNotEmpty())
                        <section class="menu-section" id="categoria-outros" aria-labelledby="titulo-outros">
                            <div class="menu-section__heading">
                                <span class="section-index">{{ str_pad((string) ($categories->count() + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <h2 id="titulo-outros">Outros</h2>
                                <span class="menu-section__count">{{ str_pad((string) $uncategorizedProducts->count(), 2, '0', STR_PAD_LEFT) }} ITENS</span>
                            </div>
                            <div class="product-list">
                                @foreach ($uncategorizedProducts as $product)
                                    @include('public.partials.product', ['product' => $product])
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            @else
                <section class="menu-empty" aria-live="polite">
                    <span class="menu-empty__mark" aria-hidden="true">+</span>
                    <h2>Estamos preparando o cardápio.</h2>
                    <p>Volte em breve para conferir as opções da casa.</p>
                </section>
            @endif
        </main>

        <footer class="menu-footer">
            <span>{{ $restaurant->name }}</span>
            <span>Cardápio digital <i aria-hidden="true">·</i> Mesa7</span>
        </footer>
    </body>
</html>
