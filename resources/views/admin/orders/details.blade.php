<div class="space-y-6 text-sm text-gray-950 dark:text-white">
    <dl class="grid gap-3 sm:grid-cols-2">
        <div><dt class="font-semibold">Mesa</dt><dd>{{ $order->restaurantTable?->name ?? 'Mesa removida' }}</dd></div>
        <div><dt class="font-semibold">Recebido em</dt><dd>{{ $order->placed_at->format('d/m/Y H:i') }}</dd></div>
        <div><dt class="font-semibold">Status</dt><dd>{{ $order->status->label() }}</dd></div>
        <div><dt class="font-semibold">Total</dt><dd>{{ \App\Support\Money::formatBrazilian((string) $order->total) }}</dd></div>
    </dl>

    @if ($order->notes)
        <section>
            <h3 class="mb-2 font-semibold">Observações</h3>
            <p class="whitespace-pre-line">{{ $order->notes }}</p>
        </section>
    @endif

    <section>
        <h3 class="mb-2 font-semibold">Itens do pedido</h3>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach ($order->items as $item)
                <li class="flex justify-between gap-4 py-2">
                    <span>{{ $item->quantity }} × {{ $item->product_name }}</span>
                    <span>{{ \App\Support\Money::formatBrazilian((string) $item->line_total) }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section>
        <h3 class="mb-2 font-semibold">Histórico</h3>
        <ol class="space-y-2">
            @foreach ($order->statusHistories as $history)
                <li>{{ $history->status->label() }} · {{ $history->changed_at->format('d/m/Y H:i') }} · {{ $history->changedBy?->name ?? 'Sistema' }}</li>
            @endforeach
        </ol>
    </section>
</div>
