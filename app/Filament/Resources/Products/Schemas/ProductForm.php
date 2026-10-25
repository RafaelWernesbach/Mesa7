<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

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
                Select::make('category_id')
                    ->label('Categoria')
                    ->relationship(
                        name: 'category',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->where('restaurant_id', Filament::getTenant()->getKey())
                            ->orderBy('sort_order')
                            ->orderBy('name'),
                    )
                    ->rules([
                        Rule::exists('categories', 'id')
                            ->where('restaurant_id', Filament::getTenant()->getKey()),
                    ])
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->placeholder('Sem categoria'),
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
