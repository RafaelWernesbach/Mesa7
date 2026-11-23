<?php

namespace App\Filament\Pages;

use App\Models\Restaurant;
use App\Models\User;
use App\Support\PublicUploadPreview;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** @property-read Schema $form */
class EditRestaurantAppearance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?string $navigationLabel = 'Aparência';

    protected static ?string $title = 'Aparência do estabelecimento';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected string $view = 'filament.pages.edit-restaurant-appearance';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

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
        $this->form->fill(Filament::getTenant()->only(['name', 'logo_path', 'primary_color', 'secondary_color']));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nome do estabelecimento')->required()->maxLength(255),
            FileUpload::make('logo_path')->label('Logo')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->disk('public')->directory(fn (): string => 'restaurants/'.Filament::getTenant()->getKey().'/logo')
                ->visibility('public')->maxSize(2048)->preventFilePathTampering()
                ->getUploadedFileUsing(fn (FileUpload $component, string $file, string|array|null $storedFileNames): ?array => PublicUploadPreview::fileInformation($component, $file, $storedFileNames)),
            ColorPicker::make('primary_color')->label('Cor principal')->hex()->nullable(),
            ColorPicker::make('secondary_color')->label('Cor secundária')->hex()->nullable(),
        ])->model(Filament::getTenant())->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->form->getState();
        Filament::getTenant()->update($data);
        Notification::make()->title('Aparência salva')->success()->send();
    }
}
