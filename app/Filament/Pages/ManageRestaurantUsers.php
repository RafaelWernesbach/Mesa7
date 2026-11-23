<?php

namespace App\Filament\Pages;

use App\Models\Restaurant;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ManageRestaurantUsers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Usuários';

    protected static ?string $title = 'Usuários do estabelecimento';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected string $view = 'filament.pages.manage-restaurant-users';

    private const ROLES = [
        'owner' => 'Proprietário',
        'manager' => 'Gerente',
        'attendant' => 'Atendente',
        'kitchen' => 'Cozinha',
    ];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $tenant = Filament::getTenant();

        return $user instanceof User && $tenant instanceof Restaurant && $tenant->is_active
            && $user->restaurants()->whereKey($tenant->getKey())->wherePivot('role', 'owner')->exists();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function table(Table $table): Table
    {
        $tenant = Filament::getTenant();

        return $table->query(User::query()->whereHas('restaurants', fn ($query) => $query->whereKey($tenant->getKey()))
            ->addSelect(['membership_role' => DB::table('restaurant_user')->select('role')
                ->whereColumn('user_id', 'users.id')->where('restaurant_id', $tenant->getKey())]))
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('role')->label('Papel')->state(fn (User $record): string => self::ROLES[$record->membership_role ?? ''] ?? 'Desconhecido'),
            ])
            ->headerActions([
                Action::make('createUser')->label('Cadastrar usuário')->schema([
                    TextInput::make('name')->label('Nome')->required()->maxLength(255),
                    TextInput::make('email')->label('E-mail')->email()->required()->maxLength(255)->unique('users', 'email'),
                    TextInput::make('password')->label('Senha inicial')->password()->required()->rules([Password::defaults()]),
                    self::roleField(),
                ])->action(function (array $data): void {
                    abort_unless(static::canAccess(), 403);
                    DB::transaction(function () use ($data): void {
                        $user = User::query()->create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
                        self::tenant()->users()->attach($user, ['role' => $data['role']]);
                    });
                    Notification::make()->title('Usuário cadastrado')->success()->send();
                }),
                Action::make('linkUser')->label('Vincular usuário existente')->schema([
                    TextInput::make('email')->label('E-mail')->email()->required()->maxLength(255),
                    self::roleField(),
                ])->action(function (array $data): void {
                    abort_unless(static::canAccess(), 403);
                    $user = User::query()->where('email', $data['email'])->first();
                    if (! $user || self::tenant()->users()->whereKey($user->getKey())->exists()) {
                        throw ValidationException::withMessages(['email' => 'Usuário não encontrado ou já vinculado ao estabelecimento.']);
                    }
                    self::tenant()->users()->attach($user, ['role' => $data['role']]);
                    Notification::make()->title('Usuário vinculado')->success()->send();
                }),
            ])
            ->recordActions([
                Action::make('changeRole')->label('Alterar papel')->schema([self::roleField()])
                    ->fillForm(fn (User $record): array => ['role' => $record->membership_role])
                    ->action(fn (User $record, array $data) => $this->changeMembership($record, $data['role'])),
                Action::make('removeUser')->label('Desvincular')->color('danger')->requiresConfirmation()
                    ->action(fn (User $record) => $this->changeMembership($record, null)),
            ]);
    }

    private static function roleField(): Select
    {
        return Select::make('role')->label('Papel')->options(self::ROLES)->required()->in(array_keys(self::ROLES));
    }

    private static function tenant(): Restaurant
    {
        $tenant = Filament::getTenant();
        abort_unless($tenant instanceof Restaurant, 403);

        return $tenant;
    }

    private static function roleFor(User $user): ?string
    {
        return DB::table('restaurant_user')->where('restaurant_id', self::tenant()->getKey())
            ->where('user_id', $user->getKey())->value('role');
    }

    private function changeMembership(User $record, ?string $role): void
    {
        abort_unless(static::canAccess(), 403);
        $changed = DB::transaction(function () use ($record, $role): bool {
            $tenant = Restaurant::query()->whereKey(Filament::getTenant()->getKey())->lockForUpdate()->firstOrFail();
            abort_unless(static::canAccess(), 403);
            $tenant->users()->whereKey($record->getKey())->firstOrFail();
            if (self::roleFor($record) === 'owner' && $role !== 'owner'
                && $tenant->users()->wherePivot('role', 'owner')->count() <= 1) {
                return false;
            }
            if ($role === null) {
                $tenant->users()->detach($record);
            } else {
                $tenant->users()->updateExistingPivot($record->getKey(), ['role' => $role]);
            }

            return true;
        });
        if (! $changed) {
            Notification::make()->title('O estabelecimento deve manter pelo menos um proprietário.')->danger()->send();

            return;
        }
        Notification::make()->title('Vínculo atualizado')->success()->send();
    }
}
