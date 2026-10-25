<?php

namespace App\Filament\Resources\RestaurantTables\Tables;

use App\Models\RestaurantTable;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RestaurantTablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Mesa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Ativa')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Ativas')
                    ->falseLabel('Inativas'),
            ])
            ->recordActions([
                Action::make('qrCode')
                    ->label('QR Code')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->visible(fn (RestaurantTable $record): bool => $record->is_active)
                    ->url(fn (RestaurantTable $record): string => route('admin.restaurant-tables.qr-code', [
                        'restaurant' => $record->restaurant->slug,
                        'table' => $record->getKey(),
                    ]), shouldOpenInNewTab: true),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
