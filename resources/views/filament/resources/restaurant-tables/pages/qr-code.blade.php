<x-filament-panels::page>
    <div class="table-qr" id="table-qr-print">
        <div class="table-qr__brand">Mesa7</div>
        <h1>{{ $this->getTable()->name }}</h1>
        <p class="table-qr__restaurant">{{ $this->getTable()->restaurant->name }}</p>
        <div class="table-qr__image" role="img" aria-label="QR Code do cardápio para {{ $this->getTable()->name }}">
            {!! $this->getQrCodeSvg() !!}
        </div>
        <p class="table-qr__instruction">Aponte a câmera do celular para acessar o cardápio.</p>
        <a class="table-qr__url" href="{{ $this->getPublicMenuUrl() }}">{{ $this->getPublicMenuUrl() }}</a>
        <div class="table-qr__actions">
            <x-filament::button icon="heroicon-o-printer" onclick="window.print()">
                Imprimir QR Code
            </x-filament::button>
            <x-filament::button color="gray" tag="a" :href="\App\Filament\Resources\RestaurantTables\RestaurantTableResource::getUrl('index')">
                Voltar às mesas
            </x-filament::button>
        </div>
    </div>

    <style>
        .table-qr { max-width: 32rem; margin: 0 auto; padding: 2rem; text-align: center; background: #fff; color: #1f1f1f; }
        .table-qr__brand { color: #e36a3c; font-size: 1rem; font-weight: 700; }
        .table-qr h1 { margin: .5rem 0 0; font-size: 2rem; font-weight: 700; }
        .table-qr__restaurant { margin: .25rem 0 1.25rem; color: #555; }
        .table-qr__image { width: min(100%, 20rem); margin: 0 auto; }
        .table-qr__image svg { display: block; width: 100%; height: auto; }
        .table-qr__instruction { margin: 1rem 0 .5rem; }
        .table-qr__url { display: block; overflow-wrap: anywhere; color: #1f1f1f; font-size: .8rem; }
        .table-qr__actions { display: flex; justify-content: center; gap: .75rem; margin-top: 1.5rem; }
        @media print {
            body * { visibility: hidden !important; }
            #table-qr-print, #table-qr-print * { visibility: visible !important; }
            #table-qr-print { position: fixed; inset: 0; width: 100%; max-width: none; padding: 1rem; }
            .table-qr__actions { display: none !important; }
            .table-qr__image { width: 18rem; }
        }
    </style>
</x-filament-panels::page>
