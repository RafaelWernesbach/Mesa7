@php
    $tenant = \Filament\Facades\Filament::getTenant();
    $isAdmin = \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'admin';
    $hasPrimary = $tenant instanceof \App\Models\Restaurant && preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $tenant->primary_color);
    $hasSecondary = $tenant instanceof \App\Models\Restaurant && preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $tenant->secondary_color);
@endphp

@if ($isAdmin && ($hasPrimary || $hasSecondary))
    <style>
        @if ($hasPrimary)
            :root {
                @foreach (\Filament\Support\Colors\Color::hex($tenant->primary_color) as $shade => $color)
                    --primary-{{ $shade }}: {{ $color }} !important;
                @endforeach
            }
        @endif
        @if ($hasSecondary)
            .fi-topbar, .fi-sidebar-header { border-bottom: 2px solid {{ $tenant->secondary_color }}; }
        @endif
    </style>
@endif
