<?php

namespace App\Livewire;

use Filament\Livewire\Topbar;
use Illuminate\Contracts\View\View;

class AdminTenantSwitcher extends Topbar
{
    public function render(): View
    {
        return view('filament.partials.tenant-switcher');
    }
}
