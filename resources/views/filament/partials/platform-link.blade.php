@if (\Filament\Facades\Filament::getCurrentPanel()?->getId() === 'admin' && auth()->user() instanceof \App\Models\User && auth()->user()->is_super_admin)
    <x-filament::button
        tag="a"
        :href="route('filament.platform.resources.restaurants.index')"
        color="gray"
        icon="heroicon-o-building-storefront"
        size="sm"
    >
        Plataforma
    </x-filament::button>
@endif
