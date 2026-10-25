<?php

namespace App\Filament\Resources\RestaurantTables\Schemas;

use App\Models\RestaurantTable;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class RestaurantTableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome da mesa')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->label('Código interno')
                    ->helperText('Identificação curta para uso da equipe, por exemplo M01.')
                    ->required()
                    ->maxLength(50)
                    ->rules(fn (?RestaurantTable $record): array => [
                        Rule::unique('restaurant_tables', 'code')
                            ->where('restaurant_id', Filament::getTenant()->getKey())
                            ->ignore($record?->getKey()),
                    ]),
                Toggle::make('is_active')
                    ->label('Mesa ativa')
                    ->default(true),
            ]);
    }
}
