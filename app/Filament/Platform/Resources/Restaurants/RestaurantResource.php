<?php

namespace App\Filament\Platform\Resources\Restaurants;

use App\Filament\Platform\Resources\Restaurants\Pages\CreateRestaurant;
use App\Filament\Platform\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Platform\Resources\Restaurants\Pages\ListRestaurants;
use App\Models\Restaurant;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $modelLabel = 'estabelecimento';

    protected static ?string $pluralModelLabel = 'estabelecimentos';

    protected static ?string $navigationLabel = 'Estabelecimentos';

    private static function isPlatformAdmin(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->is_super_admin;
    }

    public static function canViewAny(): bool
    {
        return self::isPlatformAdmin();
    }

    public static function canCreate(): bool
    {
        return self::isPlatformAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return self::isPlatformAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nome')->required()->maxLength(255),
            TextInput::make('slug')->label('Identificador na URL')->required()->maxLength(255)
                ->alphaDash()->unique(ignoreRecord: true),
            Toggle::make('is_active')->label('Estabelecimento ativo')->helperText('Ao desativar, o cardápio e o painel do estabelecimento ficam indisponíveis.')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nome')->searchable()->sortable(),
            TextColumn::make('slug')->label('Identificador')->searchable(),
            TextColumn::make('users_count')->counts('users')->label('Usuários'),
            IconColumn::make('is_active')->label('Ativo')->boolean(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurants::route('/'),
            'create' => CreateRestaurant::route('/create'),
            'edit' => EditRestaurant::route('/{record}/edit'),
        ];
    }
}
