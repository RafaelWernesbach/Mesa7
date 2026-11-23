<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Actions\Orders\TransitionOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\View\View;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'asc')
            ->columns([
                TextColumn::make('public_id')
                    ->label('Pedido')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('restaurantTable.name')
                    ->label('Mesa')
                    ->sortable(),
                TextColumn::make('placed_at')
                    ->label('Recebido em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (OrderStatus $state): string => $state->color()),
                TextColumn::make('items_count')
                    ->label('Itens')
                    ->counts('items'),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('BRL')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(OrderStatus::cases())
                        ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => $status->label()])
                        ->all()),
            ])
            ->recordActions([
                Action::make('details')
                    ->label('Detalhes')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Order $record): string => 'Pedido '.$record->public_id)
                    ->modalContent(fn (Order $record): View => view('admin.orders.details', [
                        'order' => $record->load(['items', 'statusHistories.changedBy']),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),
                self::transitionAction('start', 'Iniciar preparo', OrderStatus::Preparing, [OrderStatus::New], 'info'),
                self::transitionAction('markReady', 'Marcar como pronto', OrderStatus::Ready, [OrderStatus::Preparing], 'success'),
                self::transitionAction('complete', 'Finalizar atendimento', OrderStatus::Completed, [OrderStatus::Ready], 'gray'),
                self::transitionAction('cancel', 'Cancelar pedido', OrderStatus::Cancelled, [OrderStatus::New, OrderStatus::Preparing, OrderStatus::Ready], 'danger', true),
            ]);
    }

    /**
     * @param  list<OrderStatus>  $visibleStatuses
     */
    private static function transitionAction(
        string $name,
        string $label,
        OrderStatus $nextStatus,
        array $visibleStatuses,
        string $color,
        bool $requiresConfirmation = false,
    ): Action {
        $action = Action::make($name)
            ->label($label)
            ->color($color)
            ->visible(fn (Order $record): bool => in_array(
                OrderStatus::tryFrom((string) $record->getRawOriginal('status')),
                $visibleStatuses,
                true,
            ))
            ->action(function (Order $record) use ($nextStatus): void {
                $user = Filament::auth()->user();
                abort_unless($user instanceof User, 403);

                app(TransitionOrderStatus::class)->execute($record, $nextStatus, $user);

                Notification::make()
                    ->title('Status do pedido atualizado.')
                    ->success()
                    ->send();
            });

        return $requiresConfirmation ? $action->requiresConfirmation() : $action;
    }
}
