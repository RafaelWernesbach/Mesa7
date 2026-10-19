<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password', 'email_verified_at' => now()],
        );

        $restaurant = Restaurant::query()->firstOrCreate(
            ['slug' => 'mesa7-demo'],
            ['name' => 'Mesa7 Demo'],
        );

        $restaurant->users()->syncWithoutDetaching([$user->id => ['role' => 'owner']]);

        $products = [
            ['name' => 'Hambúrguer da Casa', 'description' => 'Pão brioche, burger artesanal de 180 g, queijo prato, cebola caramelizada e molho da casa.', 'price' => '34.90'],
            ['name' => 'Cheeseburger Clássico', 'description' => 'Pão brioche, burger artesanal de 160 g, queijo cheddar, picles e molho especial.', 'price' => '29.90'],
            ['name' => 'Duplo Bacon', 'description' => 'Dois smash burgers, queijo cheddar, bacon crocante e maionese defumada no pão brioche.', 'price' => '39.90'],
            ['name' => 'Burger de Frango Crocante', 'description' => 'Filé de frango empanado, alface, tomate e maionese de ervas no pão macio.', 'price' => '31.90'],
            ['name' => 'Burger Vegetariano', 'description' => 'Burger de grão-de-bico, queijo muçarela, rúcula, tomate e molho de iogurte.', 'price' => '32.90'],
            ['name' => 'X-Salada', 'description' => 'Burger artesanal, queijo prato, alface, tomate e maionese da casa.', 'price' => '27.90'],
            ['name' => 'Hot Dog Completo', 'description' => 'Pão, duas salsichas, purê de batata, milho, vinagrete, batata palha e molhos.', 'price' => '24.90'],
            ['name' => 'Wrap de Frango', 'description' => 'Tortilha recheada com frango grelhado, alface, tomate, queijo e molho Caesar.', 'price' => '28.90'],
            ['name' => 'Batata Frita', 'description' => 'Porção de batatas fritas crocantes, servida com ketchup da casa.', 'price' => '16.90'],
            ['name' => 'Batata com Cheddar e Bacon', 'description' => 'Batata frita coberta com cheddar cremoso e bacon crocante.', 'price' => '25.90'],
            ['name' => 'Anéis de Cebola', 'description' => 'Porção de anéis de cebola empanados e crocantes.', 'price' => '19.90'],
            ['name' => 'Nuggets de Frango', 'description' => 'Oito unidades de nuggets de frango com molho barbecue.', 'price' => '21.90'],
            ['name' => 'Salada Caesar', 'description' => 'Alface romana, frango grelhado, croutons, parmesão e molho Caesar.', 'price' => '27.90'],
            ['name' => 'Refrigerante Lata', 'description' => 'Lata de refrigerante tradicional, 350 ml.', 'price' => '7.00'],
            ['name' => 'Refrigerante Zero Lata', 'description' => 'Lata de refrigerante sem açúcar, 350 ml.', 'price' => '7.00'],
            ['name' => 'Suco Natural de Laranja', 'description' => 'Suco de laranja natural, preparado na hora, 300 ml.', 'price' => '11.90'],
            ['name' => 'Água Mineral', 'description' => 'Garrafa de água mineral sem gás, 500 ml.', 'price' => '5.00'],
            ['name' => 'Milk-shake de Chocolate', 'description' => 'Milk-shake cremoso de chocolate, 400 ml.', 'price' => '17.90'],
            ['name' => 'Brownie com Calda', 'description' => 'Brownie de chocolate servido com calda de chocolate.', 'price' => '16.90'],
            ['name' => 'Cheesecake de Frutas Vermelhas', 'description' => 'Fatia de cheesecake com cobertura artesanal de frutas vermelhas.', 'price' => '18.90'],
        ];

        foreach ($products as $product) {
            $restaurant->products()->updateOrCreate(
                ['name' => $product['name']],
                [
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'image_path' => null,
                    'is_available' => true,
                ],
            );
        }
    }
}
