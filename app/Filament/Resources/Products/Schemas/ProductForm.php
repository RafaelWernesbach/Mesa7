<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descrição')
                    ->rows(4)
                    ->maxLength(5000),
                TextInput::make('price')
                    ->label('Preço')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->maxValue(99999999.99)
                    ->step('0.01')
                    ->prefix('R$'),
                FileUpload::make('image_path')
                    ->label('Imagem')
                    ->image()
                    ->disk('public')
                    ->directory(fn (): string => 'products/'.Filament::getTenant()->getKey())
                    ->visibility('public')
                    ->maxSize(2048),
                Toggle::make('is_available')
                    ->label('Disponível para venda')
                    ->default(true),
            ]);
    }
}
