<?php

use App\Models\Restaurant;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated restaurant members are redirected to the order queue', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->create();
    $restaurant->users()->attach($user, ['role' => 'owner']);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertRedirect(route('filament.admin.resources.orders.index', ['tenant' => $restaurant->slug]));
});

test('the public home sends administrators to the panel login', function () {
    $this->get(route('home'))->assertRedirect('/admin/login');
});
