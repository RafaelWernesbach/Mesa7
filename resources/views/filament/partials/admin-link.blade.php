@if (\Filament\Facades\Filament::getCurrentPanel()?->getId() === 'platform' && auth()->user() instanceof \App\Models\User && auth()->user()->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))
    <x-filament::button
        tag="a"
        :href="route('filament.admin.tenant')"
        color="gray"
        icon="heroicon-o-arrow-left"
        size="sm"
    >
        Painel do estabelecimento
    </x-filament::button>
@endif
