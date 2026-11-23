@if (\Filament\Facades\Filament::getCurrentPanel()?->getId() === 'admin' && \Filament\Facades\Filament::getTenant())
    @livewire(\App\Livewire\AdminTenantSwitcher::class)
@endif
