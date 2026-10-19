<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class DemoProductsSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            'mesa7-demo' => [
                ['Hambúrguer da Casa', 'Pão brioche, burger artesanal de 180 g, queijo prato, cebola caramelizada e molho da casa.', '34.90'],
                ['Cheeseburger Clássico', 'Pão brioche, burger artesanal de 160 g, queijo cheddar, picles e molho especial.', '29.90'],
                ['Duplo Bacon', 'Dois smash burgers, queijo cheddar, bacon crocante e maionese defumada no pão brioche.', '39.90'],
                ['Burger de Frango Crocante', 'Filé de frango empanado, alface, tomate e maionese de ervas no pão macio.', '31.90'],
                ['Burger Vegetariano', 'Burger de grão-de-bico, queijo muçarela, rúcula, tomate e molho de iogurte.', '32.90'],
                ['X-Salada', 'Burger artesanal, queijo prato, alface, tomate e maionese da casa.', '27.90'],
                ['Hot Dog Completo', 'Pão, duas salsichas, purê de batata, milho, vinagrete, batata palha e molhos.', '24.90'],
                ['Wrap de Frango', 'Tortilha recheada com frango grelhado, alface, tomate, queijo e molho Caesar.', '28.90'],
                ['Batata Frita', 'Porção de batatas fritas crocantes, servida com ketchup da casa.', '16.90'],
                ['Batata com Cheddar e Bacon', 'Batata frita coberta com cheddar cremoso e bacon crocante.', '25.90'],
                ['Anéis de Cebola', 'Porção de anéis de cebola empanados e crocantes.', '19.90'],
                ['Nuggets de Frango', 'Oito unidades de nuggets de frango com molho barbecue.', '21.90'],
                ['Salada Caesar', 'Alface romana, frango grelhado, croutons, parmesão e molho Caesar.', '27.90'],
                ['Refrigerante Lata', 'Lata de refrigerante tradicional, 350 ml.', '7.00'],
                ['Refrigerante Zero Lata', 'Lata de refrigerante sem açúcar, 350 ml.', '7.00'],
                ['Suco Natural de Laranja', 'Suco de laranja natural, preparado na hora, 300 ml.', '11.90'],
                ['Água Mineral', 'Garrafa de água mineral sem gás, 500 ml.', '5.00'],
                ['Milk-shake de Chocolate', 'Milk-shake cremoso de chocolate, 400 ml.', '17.90'],
                ['Brownie com Calda', 'Brownie de chocolate servido com calda de chocolate.', '16.90'],
                ['Cheesecake de Frutas Vermelhas', 'Fatia de cheesecake com cobertura artesanal de frutas vermelhas.', '18.90'],
            ],
            'cantina-da-praca' => [
                ['Feijoada Completa', 'Feijoada da casa com arroz, couve refogada, farofa, laranja e torresmo.', '42.90'],
                ['Frango à Parmegiana', 'Filé de frango empanado, molho de tomate, muçarela, arroz e fritas.', '36.90'],
                ['Filé à Parmegiana', 'Filé bovino empanado, molho de tomate, muçarela, arroz e fritas.', '44.90'],
                ['Strogonoff de Frango', 'Strogonoff cremoso de frango com arroz branco e batata palha.', '32.90'],
                ['Strogonoff de Carne', 'Strogonoff de carne com arroz branco e batata palha.', '37.90'],
                ['Bife Acebolado', 'Bife grelhado com cebola, arroz, feijão, farofa e salada.', '34.90'],
                ['Picadinho da Casa', 'Picadinho bovino ao molho com arroz, feijão, farofa e ovo.', '35.90'],
                ['Lasanha à Bolonhesa', 'Lasanha de massa fresca com molho bolonhesa e queijo gratinado.', '33.90'],
                ['Nhoque ao Sugo', 'Nhoque de batata servido com molho de tomate da casa e parmesão.', '29.90'],
                ['Espaguete à Bolonhesa', 'Espaguete com molho bolonhesa cozido lentamente.', '31.90'],
                ['Penne ao Molho Branco', 'Penne com molho branco, parmesão e toque de noz-moscada.', '29.90'],
                ['Risoto de Cogumelos', 'Arroz arbóreo cremoso com cogumelos frescos e parmesão.', '38.90'],
                ['Omelete da Horta', 'Omelete de três ovos com queijo, tomate, cebola e ervas.', '24.90'],
                ['Salada Tropical', 'Mix de folhas, manga, tomate-cereja, cenoura e molho cítrico.', '25.90'],
                ['Arroz Branco', 'Porção individual de arroz branco soltinho.', '8.00'],
                ['Feijão Caseiro', 'Porção individual de feijão carioca temperado na casa.', '8.00'],
                ['Farofa da Casa', 'Farofa de mandioca tostada com cebola e manteiga.', '7.00'],
                ['Suco de Maracujá', 'Suco de maracujá preparado na hora, 300 ml.', '10.90'],
                ['Pudim de Leite', 'Fatia de pudim de leite condensado com calda de caramelo.', '12.90'],
                ['Brigadeiro de Colher', 'Brigadeiro cremoso de chocolate com granulado.', '9.90'],
            ],
        ];

        foreach ($menus as $slug => $products) {
            $restaurant = Restaurant::query()->where('slug', $slug)->firstOrFail();

            foreach ($products as [$name, $description, $price]) {
                $restaurant->products()->updateOrCreate(
                    ['name' => $name],
                    [
                        'description' => $description,
                        'price' => $price,
                        'image_path' => null,
                        'is_available' => true,
                    ],
                );
            }
        }
    }
}
