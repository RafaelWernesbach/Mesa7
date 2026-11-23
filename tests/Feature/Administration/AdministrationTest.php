<?php

use App\Filament\Pages\EditRestaurantAppearance;
use App\Filament\Pages\ManageRestaurantUsers;
use App\Filament\Platform\Resources\Restaurants\Pages\CreateRestaurant;
use App\Filament\Platform\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Platform\Resources\Restaurants\Pages\ListRestaurants;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->restaurant = Restaurant::factory()->create();
    $this->owner = User::factory()->create();
    $this->restaurant->users()->attach($this->owner, ['role' => 'owner']);
    $this->actingAs($this->owner);
    Filament::setCurrentPanel('admin');
    Filament::bootCurrentPanel();
    Filament::setTenant($this->restaurant);
});

it('lets an owner create and link users without changing existing credentials', function () {
    $existing = User::factory()->create(['email' => 'existing@example.com']);
    $password = $existing->password;

    Livewire::test(ManageRestaurantUsers::class)
        ->callAction(TestAction::make('createUser')->table(), data: [
            'name' => 'Nova pessoa', 'email' => 'new@example.com', 'password' => 'password123', 'role' => 'kitchen',
        ])
        ->assertHasNoActionErrors()
        ->callAction(TestAction::make('linkUser')->table(), data: ['email' => $existing->email, 'role' => 'manager'])
        ->assertHasNoActionErrors();

    $created = User::query()->where('email', 'new@example.com')->firstOrFail();
    expect(Hash::check('password123', $created->password))->toBeTrue()
        ->and($existing->fresh()->password)->toBe($password);
    $this->assertDatabaseHas('restaurant_user', ['restaurant_id' => $this->restaurant->id, 'user_id' => $created->id, 'role' => 'kitchen']);
    $this->assertDatabaseHas('restaurant_user', ['restaurant_id' => $this->restaurant->id, 'user_id' => $existing->id, 'role' => 'manager']);
});

it('limits the user list to the tenant and prevents removing the last owner', function () {
    $other = Restaurant::factory()->create();
    $outsider = User::factory()->create();
    $other->users()->attach($outsider, ['role' => 'owner']);

    Livewire::test(ManageRestaurantUsers::class)
        ->assertCanSeeTableRecords([$this->owner])
        ->assertCanNotSeeTableRecords([$outsider])
        ->callAction(TestAction::make('removeUser')->table($this->owner))
        ->assertNotified('O estabelecimento deve manter pelo menos um proprietário.');
    $this->assertDatabaseHas('restaurant_user', ['restaurant_id' => $this->restaurant->id, 'user_id' => $this->owner->id, 'role' => 'owner']);
});

it('changes or removes a member without affecting other establishments', function () {
    $member = User::factory()->create();
    $other = Restaurant::factory()->create();
    $this->restaurant->users()->attach($member, ['role' => 'manager']);
    $other->users()->attach($member, ['role' => 'owner']);

    Livewire::test(ManageRestaurantUsers::class)
        ->callAction(TestAction::make('changeRole')->table($member), data: ['role' => 'kitchen'])
        ->assertHasNoActionErrors();
    $this->assertDatabaseHas('restaurant_user', ['restaurant_id' => $this->restaurant->id, 'user_id' => $member->id, 'role' => 'kitchen']);
    Livewire::test(ManageRestaurantUsers::class)->callAction(TestAction::make('removeUser')->table($member));
    $this->assertDatabaseMissing('restaurant_user', ['restaurant_id' => $this->restaurant->id, 'user_id' => $member->id]);
    $this->assertDatabaseHas('restaurant_user', ['restaurant_id' => $other->id, 'user_id' => $member->id, 'role' => 'owner']);
});

it('rejects invalid roles and prevents demoting the last owner', function () {
    Livewire::test(ManageRestaurantUsers::class)
        ->callAction(TestAction::make('changeRole')->table($this->owner), data: ['role' => 'manager'])
        ->assertNotified('O estabelecimento deve manter pelo menos um proprietário.');
    Livewire::test(ManageRestaurantUsers::class)
        ->callAction(TestAction::make('createUser')->table(), data: [
            'name' => 'Teste', 'email' => 'invalid@example.com', 'password' => 'password123', 'role' => 'superadmin',
        ])->assertHasActionErrors();
    $this->assertDatabaseMissing('users', ['email' => 'invalid@example.com']);
    $this->assertDatabaseHas('restaurant_user', ['restaurant_id' => $this->restaurant->id, 'user_id' => $this->owner->id, 'role' => 'owner']);
});

it('prevents the sole owner from deleting their account', function () {
    Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasErrors(['password']);
    $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    $this->assertDatabaseHas('restaurant_user', ['restaurant_id' => $this->restaurant->id, 'user_id' => $this->owner->id, 'role' => 'owner']);
});

it('denies managers access to user management and appearance settings', function () {
    $manager = User::factory()->create();
    $this->restaurant->users()->attach($manager, ['role' => 'manager']);
    $this->actingAs($manager);

    Livewire::test(ManageRestaurantUsers::class)->assertForbidden();
    Livewire::test(EditRestaurantAppearance::class)->assertForbidden();
});

it('saves appearance only for the active tenant', function () {
    $other = Restaurant::factory()->create();
    Livewire::test(EditRestaurantAppearance::class)
        ->fillForm(['name' => 'Nome atualizado', 'primary_color' => '#112233', 'secondary_color' => '#AABBCC'])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($this->restaurant->fresh()->name)->toBe('Nome atualizado')
        ->and($this->restaurant->fresh()->primary_color)->toBe('#112233')
        ->and($other->fresh()->primary_color)->not->toBe('#112233');
    $this->get(route('public.menu.show', $this->restaurant->slug))
        ->assertSee('--menu-accent: #112233')->assertSee('--menu-sage: #AABBCC');
});

it('applies each tenant logo and colors to its admin panel without affecting another tenant', function () {
    $this->restaurant->update([
        'logo_path' => 'restaurants/first/logo.png',
        'primary_color' => '#112233',
        'secondary_color' => '#AABBCC',
    ]);
    $other = Restaurant::factory()->create(['logo_path' => 'restaurants/second/logo.png', 'primary_color' => '#EE1133']);
    $other->users()->attach($this->owner, ['role' => 'owner']);
    $firstColor = Color::hex('#112233')[600];
    $otherColor = Color::hex('#EE1133')[600];

    $this->get(route('filament.admin.resources.orders.index', ['tenant' => $this->restaurant->slug]))
        ->assertOk()
        ->assertSee(Storage::disk('public')->url('restaurants/first/logo.png'))
        ->assertSee('--primary-600: '.$firstColor.' !important')
        ->assertSee('border-bottom: 2px solid #AABBCC')
        ->assertDontSee(Storage::disk('public')->url('restaurants/second/logo.png'));

    $this->get(route('filament.admin.resources.orders.index', ['tenant' => $other->slug]))
        ->assertOk()
        ->assertSee(Storage::disk('public')->url('restaurants/second/logo.png'))
        ->assertSee('--primary-600: '.$otherColor.' !important')
        ->assertDontSee('--primary-600: '.$firstColor.' !important')
        ->assertDontSee('border-bottom: 2px solid #AABBCC');
});

it('shows the tenant name with its logo and the tenant selector beside global search', function () {
    $this->restaurant->update(['name' => 'Casa da Praça', 'logo_path' => 'restaurants/first/logo.png']);
    $other = Restaurant::factory()->create(['name' => 'Outra casa']);
    $other->users()->attach($this->owner, ['role' => 'manager']);
    $foreign = Restaurant::factory()->create(['name' => 'Casa sem acesso']);

    $this->get(route('filament.admin.resources.orders.index', ['tenant' => $this->restaurant->slug]))
        ->assertOk()
        ->assertSeeInOrder(['mesa7-tenant-brand__logo', 'mesa7-tenant-brand__name', 'Casa da Praça'])
        ->assertSeeInOrder(['mesa7-tenant-switcher', 'Outra casa', 'fi-global-search-ctn'])
        ->assertDontSee($foreign->name);
});

it('uses the default admin appearance when a tenant has no logo or colors', function () {
    $this->get(route('filament.admin.resources.orders.index', ['tenant' => $this->restaurant->slug]))
        ->assertOk()
        ->assertSee($this->restaurant->name)
        ->assertDontSee('border-bottom: 2px solid #AABBCC');
});

it('uploads and displays a restaurant logo', function () {
    Storage::fake('public');
    $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', true);
    $file = UploadedFile::fake()->createWithContent('logo.png', $image);

    Livewire::test(EditRestaurantAppearance::class)->fillForm(['logo_path' => $file])->call('save')->assertHasNoFormErrors();
    $path = $this->restaurant->fresh()->logo_path;
    expect($path)->toStartWith('restaurants/'.$this->restaurant->id.'/logo/');
    Storage::disk('public')->assertExists($path);
    $this->get(route('public.menu.show', $this->restaurant->slug))->assertSee(Storage::disk('public')->url($path));
});

it('returns a same-origin preview for the saved logo in the appearance form', function () {
    config(['app.url' => 'http://localhost:8000']);
    Storage::fake('public', ['url' => 'http://localhost:8000/storage']);
    $path = 'restaurants/'.$this->restaurant->id.'/logo/test.png';
    Storage::disk('public')->put($path, 'example');
    $this->restaurant->update(['logo_path' => $path]);

    $component = Livewire::test(EditRestaurantAppearance::class)->instance();
    $field = $component->form->getComponent('logo_path');
    $files = $field->getUploadedFiles();

    expect(collect($files)->first()['url'])->toBe('/storage/'.$path);
});

it('rejects a logo path belonging to another establishment', function () {
    Storage::fake('public');
    $other = Restaurant::factory()->create();
    $path = 'restaurants/'.$other->id.'/logo/other.png';
    Storage::disk('public')->put($path, 'other tenant');

    Livewire::test(EditRestaurantAppearance::class)
        ->fillForm(['logo_path' => [$path]])
        ->call('save')
        ->assertHasFormErrors();
    expect($this->restaurant->fresh()->logo_path)->toBeNull();
});

it('restricts the platform panel and tenant operations to super admins', function () {
    $platform = Filament::getPanel('platform');
    expect($this->owner->canAccessPanel($platform))->toBeFalse();
    $this->get('/plataforma/restaurants')->assertForbidden();

    $super = User::factory()->create();
    $super->forceFill(['is_super_admin' => true])->save();
    $this->actingAs($super);
    Filament::setCurrentPanel('platform');
    Filament::bootCurrentPanel();
    expect($super->canAccessPanel($platform))->toBeTrue();

    Livewire::test(ListRestaurants::class)->assertCanSeeTableRecords([$this->restaurant]);
    Livewire::test(CreateRestaurant::class)->fillForm(['name' => 'Outra casa', 'slug' => 'outra-casa', 'is_active' => true])
        ->call('create')->assertHasNoFormErrors();
    $restaurant = Restaurant::query()->where('slug', 'outra-casa')->firstOrFail();
    Livewire::test(EditRestaurant::class, ['record' => $restaurant->getKey()])
        ->callAction('assignOwner', data: ['email' => $this->owner->email])->assertHasNoActionErrors();
    $this->assertDatabaseHas('restaurant_user', ['restaurant_id' => $restaurant->id, 'user_id' => $this->owner->id, 'role' => 'owner']);
});

it('shows the platform link in the admin header only to super admins', function () {
    $adminUrl = route('filament.admin.resources.orders.index', ['tenant' => $this->restaurant->slug]);
    $platformUrl = route('filament.platform.resources.restaurants.index');

    $this->get($adminUrl)->assertOk()->assertDontSee($platformUrl);

    $this->owner->forceFill(['is_super_admin' => true])->save();
    $this->get($adminUrl)->assertOk()->assertSee($platformUrl)->assertSee('Plataforma');
});

it('shows a return link on the platform only when an active tenant is accessible', function () {
    $this->owner->forceFill(['is_super_admin' => true])->save();
    $platformUrl = route('filament.platform.resources.restaurants.index');
    $adminUrl = route('filament.admin.tenant');
    Filament::setCurrentPanel('platform');
    Filament::getPanel('platform')->boot();

    $this->get($platformUrl)->assertOk()->assertSee($adminUrl)->assertSee('Painel do estabelecimento');
    $this->get($adminUrl)->assertRedirect(route('filament.admin.home', ['tenant' => $this->restaurant->slug]));

    $this->restaurant->update(['is_active' => false]);
    $this->get($platformUrl)->assertOk()->assertDontSee('Painel do estabelecimento');
});

it('removes inactive establishments from access and the public menu', function () {
    $this->restaurant->update(['is_active' => false]);
    expect($this->owner->canAccessPanel(Filament::getPanel('admin')))->toBeFalse()
        ->and($this->owner->canAccessTenant($this->restaurant->fresh()))->toBeFalse()
        ->and($this->owner->getTenants(Filament::getPanel('admin')))->toHaveCount(0);
    $this->get(route('public.menu.show', $this->restaurant->slug))->assertNotFound();
});
