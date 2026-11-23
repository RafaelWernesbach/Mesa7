<article class="product-item">
    @if ($product->image_path)
        <img class="product-item__image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy">
    @else
        <span class="product-item__marker" aria-hidden="true"></span>
    @endif
    <div class="product-item__body">
        <h3>{{ $product->name }}</h3>
        @if ($product->description)
            <p>{{ $product->description }}</p>
        @endif
    </div>
    <span class="product-item__price">{{ \App\Support\Money::formatBrazilian((string) $product->price) }}</span>
    @if ($table)
        <form class="product-item__actions" data-add-to-cart data-product-name="{{ $product->name }}" data-price-cents="{{ \App\Support\Money::toCents((string) $product->price) }}" method="POST" action="{{ route('public.orders.cart.items.store', ['restaurant' => $restaurant->slug, 'uuid' => $table->uuid]) }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->getKey() }}">
            <label class="sr-only" for="quantidade-produto-{{ $product->getKey() }}">Quantidade de {{ $product->name }}</label>
            <input id="quantidade-produto-{{ $product->getKey() }}" class="quantity-input" type="number" name="quantity" min="1" max="99" value="1">
            <button class="button button--small" type="submit">Adicionar</button>
        </form>
        <p class="product-item__feedback" data-product-feedback role="status" aria-live="polite"></p>
    @endif
</article>
