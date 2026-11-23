<?php

namespace App\Filament\Platform\Resources\Restaurants\Pages;

use App\Filament\Platform\Resources\Restaurants\RestaurantResource;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditRestaurant extends EditRecord
{
    protected static string $resource = RestaurantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignOwner')->label('Vincular proprietário')->schema([
                TextInput::make('email')->label('E-mail do usuário existente')->email()->required(),
            ])->action(function (array $data): void {
                abort_unless(RestaurantResource::canEdit($this->getRecord()), 403);
                $user = User::query()->where('email', $data['email'])->first();
                if (! $user) {
                    throw ValidationException::withMessages(['email' => 'Usuário não encontrado.']);
                }
                $restaurant = $this->getRecord();
                abort_unless($restaurant instanceof Restaurant, 404);
                $restaurant->users()->syncWithoutDetaching([$user->getKey() => ['role' => 'owner']]);
                Notification::make()->title('Proprietário vinculado')->success()->send();
            }),
        ];
    }
}
