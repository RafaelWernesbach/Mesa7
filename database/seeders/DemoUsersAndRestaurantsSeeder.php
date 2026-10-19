<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUsersAndRestaurantsSeeder extends Seeder
{
    public function run(): void
    {
        $userAttributes = [
            ['name' => 'Test User', 'email' => 'test@example.com'],
            ['name' => 'Ana Martins', 'email' => 'ana@mesa7.demo'],
            ['name' => 'Bruno Souza', 'email' => 'bruno@mesa7.demo'],
            ['name' => 'Carla Oliveira', 'email' => 'carla@mesa7.demo'],
        ];
        $users = [];

        foreach ($userAttributes as $attributes) {
            $users[$attributes['email']] = User::query()->firstOrCreate(
                ['email' => $attributes['email']],
                [
                    'name' => $attributes['name'],
                    'password' => 'password',
                ],
            );

            if ($users[$attributes['email']]->email_verified_at === null) {
                $users[$attributes['email']]->forceFill(['email_verified_at' => now()])->save();
            }
        }

        $restaurantAttributes = [
            ['name' => 'Mesa7 Demo', 'slug' => 'mesa7-demo'],
            ['name' => 'Cantina da Praça', 'slug' => 'cantina-da-praca'],
        ];
        $restaurants = [];

        foreach ($restaurantAttributes as $attributes) {
            $restaurants[$attributes['slug']] = Restaurant::query()->firstOrCreate(
                ['slug' => $attributes['slug']],
                ['name' => $attributes['name']],
            );
        }

        $memberships = [
            'mesa7-demo' => [
                'test@example.com' => 'owner',
                'ana@mesa7.demo' => 'manager',
                'bruno@mesa7.demo' => 'kitchen',
            ],
            'cantina-da-praca' => [
                'test@example.com' => 'manager',
                'ana@mesa7.demo' => 'owner',
                'carla@mesa7.demo' => 'kitchen',
            ],
        ];

        foreach ($memberships as $restaurantSlug => $restaurantUsers) {
            $restaurant = $restaurants[$restaurantSlug];
            $userIdsWithRoles = [];

            foreach ($restaurantUsers as $email => $role) {
                $userIdsWithRoles[$users[$email]->getKey()] = ['role' => $role];
            }

            $restaurant->users()->syncWithoutDetaching($userIdsWithRoles);
        }
    }
}
