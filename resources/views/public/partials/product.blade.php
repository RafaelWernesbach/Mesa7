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
    <span class="product-item__price">R$ {{ number_format((float) $product->price, 2, ',', '.') }}</span>
</article>
