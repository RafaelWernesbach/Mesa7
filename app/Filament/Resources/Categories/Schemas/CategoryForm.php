<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                        if (blank($get('slug'))) {
                            $set('slug', Str::slug($state ?? ''));
                        }
                    }),
                TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Identificador usado nas URLs e no cardápio.')
                    ->required()
                    ->maxLength(255)
                    ->rules(fn (?Category $record): array => [
                        Rule::unique('categories', 'slug')
                            ->where('restaurant_id', Filament::getTenant()->getKey())
                            ->ignore($record?->getKey()),
                    ]),
                Textarea::make('description')
                    ->label('Descrição')
                    ->rows(3)
                    ->maxLength(2000),
                TextInput::make('sort_order')
                    ->label('Ordem no cardápio')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Categoria ativa no cardápio')
                    ->default(true),
            ]);
    }
}
