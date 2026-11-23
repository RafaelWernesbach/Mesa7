# Estrutura Técnica Atual

Este documento descreve os caminhos MVC do MVP presencial: catálogo, cardápio público por mesa, carrinho, pedidos e operação. A visão completa está em [MESA7_ARCHITECTURE.md](MESA7_ARCHITECTURE.md), e o modelo relacional está em [diagramas/MESA7_ERD.mmd](diagramas/MESA7_ERD.mmd).

## Escopo implementado

- painel administrativo Filament multi-tenant;
- CRUD de categorias, produtos e mesas;
- geração de UUID e QR Code por mesa;
- cardápio Blade público por estabelecimento e por mesa;
- carrinho de sessão vinculado ao tenant e à mesa;
- criação transacional e acompanhamento público de pedidos;
- fila administrativa Filament com ações para atualizar status e histórico;
- validação de disponibilidade, tenant e valores no servidor.

## Visão do fluxo

```mermaid
flowchart LR
    Admin[Usuário owner ou manager] --> Panel[Painel Filament do tenant]
    Panel --> Categories[CategoryResource]
    Panel --> Products[ProductResource]
    Panel --> Tables[RestaurantTableResource]
    Products --> DB[(Banco de dados)]
    Categories --> DB
    Tables --> DB
    Tables --> QR[Página HTML imprimível do QR]
    QR --> PublicRoute[GET /r/{slug}/cardapio/{uuid}]
    PublicRoute --> Controller[PublicMenuController]
    Controller --> DB
    Controller --> Blade[resources/views/public/menu.blade.php]
    Blade --> Customer[Cliente consulta o cardápio]
    Customer --> Cart[Carrinho da sessão]
    Cart --> PublicOrder[PublicOrderController]
    PublicOrder --> CreateOrder[CreateOrder Action]
    CreateOrder --> DB
    PublicOrder --> Tracking[Acompanhamento público]
    Operator[Owner, manager ou attendant] --> Panel
    Panel --> Orders[OrderResource]
    Orders --> Transition[TransitionOrderStatus Action]
    Transition --> DB
```

## Componentes e responsabilidades

| Camada | Localização | Responsabilidade |
| --- | --- | --- |
| Rotas públicas | `routes/web.php` | Declara cardápio geral, rota UUID por mesa e rota legada por código. |
| Controller | `app/Http/Controllers/PublicMenuController.php` | Resolve o estabelecimento/mesa, filtra menu público e escolhe os dados para a view. |
| Controller | `app/Http/Controllers/PublicOrderController.php` | Gerencia carrinho, confirmação do pedido e acompanhamento público. |
| Actions | `app/Actions/Orders/` | Cria pedidos com preço calculado no servidor e aplica transições autorizadas. |
| Suporte | `app/Support/PublicTableCart.php`, `app/Support/Money.php` | Isola o carrinho por mesa e faz cálculos monetários sem float. |
| Views públicas | `resources/views/public/` | Renderizam o cardápio e cada produto com Blade. |
| CSS público | `resources/css/public-menu.css` | Estiliza a experiência pública, importada por `resources/css/app.css`. |
| Recursos Filament | `app/Filament/Resources/` | Formulários, listagens e ações administrativas por tenant. |
| Policies | `app/Policies/` | Restringem catálogo e mesas a `owner`/`manager`; pedidos podem ser operados por `owner`, `manager`, `attendant` e `kitchen`. |
| Models | `app/Models/` | Definem relações Eloquent, casts, snapshots e histórico de status. |
| Migrations | `database/migrations/` | Definem estrutura, índices e integridade referencial. |
| Seeders | `database/seeders/` | Criam tenants e contas de demonstração, categorias, produtos e mesas. |

### Recursos administrativos

- `ProductResource`: nome, categoria opcional, descrição, preço, imagem e disponibilidade; lista preço, categoria e disponibilidade.
- `CategoryResource`: nome, slug, descrição, ordem no menu e estado ativo.
- `RestaurantTableResource`: nome, código interno e estado ativo; ação **QR Code** abre uma nova aba HTML independente.
- `RestaurantTableQrCodeController`: autoriza owner/manager, busca a mesa dentro do estabelecimento e gera a imagem SVG do endereço público.
- `resources/views/admin/restaurant-tables/qr-code.blade.php`: exibe estabelecimento, mesa, código interno, URL, imagem QR e ação de impressão pelo navegador.
- `OrderResource`: lista pedidos do tenant, mostra detalhes e oferece as transições permitidas.
- `OrderPolicy`: autoriza a consulta e operação de pedidos conforme o tenant selecionado e o papel do usuário.

Os três recursos declaram `restaurant` como relacionamento de posse do tenant. O Filament filtra seus registros pelo estabelecimento selecionado; políticas validam adicionalmente papel e propriedade ao acessar ou alterar um registro.

## Rotas e fluxo do cardápio

| Método e caminho | Nome Laravel | Uso |
| --- | --- | --- |
| `GET /r/{restaurant:slug}` | `public.menu.show` | Cardápio geral de estabelecimento ativo. |
| `GET /r/{restaurant:slug}/cardapio/{uuid}` | `public.menu.table` | Cardápio contextual de uma mesa ativa, usado pelos novos QR Codes. |
| `GET /r/{restaurant:slug}/mesa/{code}` | `public.menu.table-code` | Compatibilidade com links antigos por código interno. |
| `GET /r/{restaurant:slug}/cardapio/{uuid}/carrinho` | `public.orders.cart` | Exibe e revisa o carrinho vinculado à mesa. |
| `POST /r/{restaurant:slug}/cardapio/{uuid}/carrinho/itens` | `public.orders.cart.items.store` | Adiciona um produto disponível ao carrinho. |
| `PATCH /r/{restaurant:slug}/cardapio/{uuid}/carrinho/itens/{product}` | `public.orders.cart.items.update` | Atualiza a quantidade de um item. |
| `DELETE /r/{restaurant:slug}/cardapio/{uuid}/carrinho/itens/{product}` | `public.orders.cart.items.destroy` | Remove um item do carrinho. |
| `POST /r/{restaurant:slug}/cardapio/{uuid}/pedido` | `public.orders.store` | Confirma e grava o pedido com limitação de requisições. |
| `GET /r/{restaurant:slug}/pedido/{publicId}` | `public.orders.track` | Acompanha o pedido sem autenticação. |

O carrinho não aceita preço do navegador como fonte confiável. `CreateOrder` recarrega produtos pela relação do estabelecimento, valida disponibilidade, calcula valores inteiros em centavos, grava snapshots e cria o status inicial no histórico em uma transação. A equipe atualiza o pedido pela fila do Filament; cada transição permitida grava o ator e o horário.

Para a rota UUID, `PublicMenuController` busca a mesa pela relação `$restaurant->tables()`, pelo UUID e com `is_active = true`. A URL não permite resolver uma mesa pertencente a outro estabelecimento. Estabelecimento ou mesa inexistente/inativo retorna `404`.

O controller carrega categorias ativas que possuem produtos disponíveis e os produtos disponíveis dessas categorias. Produtos sem categoria são carregados separadamente para **Outros**. O Blade recebe os dados e não executa regras de negócio.

## Modelo de dados usado por essas funcionalidades

| Tabela | Campos relevantes | Restrições e relações |
| --- | --- | --- |
| `restaurants` | `id`, `name`, `slug`, `is_active` | `slug` único; um estabelecimento possui usuários, categorias, produtos e mesas. |
| `restaurant_user` | `restaurant_id`, `user_id`, `role` | Uma associação por par estabelecimento/usuário; `attendant` opera pedidos sem acesso ao catálogo. |
| `categories` | `restaurant_id`, `name`, `slug`, `description`, `sort_order`, `is_active` | Pertence a um estabelecimento; `slug` único por estabelecimento. |
| `products` | `restaurant_id`, `category_id`, `name`, `description`, `price`, `image_path`, `is_available` | Pertence a um estabelecimento; categoria opcional; excluir categoria define `category_id` como nulo. |
| `restaurant_tables` | `restaurant_id`, `name`, `code`, `uuid`, `is_active` | `code` único por estabelecimento; `uuid` único globalmente e usado na URL pública. |
| `orders` | `restaurant_id`, `restaurant_table_id`, `public_id`, `source`, `status`, `notes`, valores e horários | Pertence ao tenant; neste MVP, `source` é `table`; `public_id` não é sequencial. |
| `order_items` | `order_id`, `product_id`, `product_name`, `unit_price`, `quantity`, `line_total`, `notes` | Mantém cópias históricas do nome e preço; referência ao produto pode ficar nula. |
| `order_status_histories` | `order_id`, `changed_by_user_id`, `status`, `changed_at` | Registra status inicial e cada transição; ator pode ser nulo para a criação pública. |

A migration do histórico também registra o status atual dos pedidos que já existiam antes dela, usando `placed_at` (ou `created_at`) como horário inicial e deixando o ator nulo.

Relações Eloquent principais:

```text
Restaurant hasMany Category, Product, RestaurantTable
Restaurant hasMany Order
Restaurant belongsToMany User (pivot restaurant_user, role)
Category belongsTo Restaurant; hasMany Product
Product belongsTo Restaurant; belongsTo Category (opcional)
RestaurantTable belongsTo Restaurant; hasMany Order
Order belongsTo Restaurant and RestaurantTable; hasMany OrderItem and OrderStatusHistory
OrderItem belongsTo Order and optionally Product
OrderStatusHistory belongsTo Order and optionally User
```

UUID é atribuído ao criar o model `RestaurantTable`; o seeder de mesas também o preenche explicitamente porque `DatabaseSeeder` desativa eventos de model. A migration faz backfill de registros existentes e impõe unicidade e obrigatoriedade.

## Imagens e assets

- Upload de produto: disco Laravel `public`, pasta `products/{restaurant_id}`, tamanho máximo 2 MB.
- Para servir imagens localmente, executar `php artisan storage:link`.
- O cardápio usa `@vite` para `resources/css/app.css` e `resources/js/app.js`; estilos próprios ficam em `resources/css/public-menu.css`.
- O SVG do QR é gerado no servidor e exibido na página administrativa, sem armazenar uma imagem duplicada em disco.

## Seeders de demonstração

`DatabaseSeeder` chama:

1. `DemoUsersAndRestaurantsSeeder`: usuários, estabelecimentos e associação com papéis;
2. `DemoProductsSeeder`: categorias e pelo menos 20 produtos por estabelecimento, com vínculos de categoria;
3. `DemoRestaurantTablesSeeder`: mesas de exemplo com códigos `M01`, `M02` e `M07`.

Os seeders são idempotentes para os dados demo; UUID existente de mesa é preservado.

## Testes e verificações

- `tests/Feature/Products/ProductResourceTest.php`: fluxo CRUD, validação, autorização e tenant.
- `tests/Feature/Categories/CategoryResourceTest.php`: CRUD e isolamento por tenant.
- `tests/Feature/RestaurantTables/RestaurantTableResourceTest.php`: CRUD, UUID, QR e autorização.
- `tests/Feature/PublicMenuTest.php`: produtos publicados, categorias, mesa por código/UUID, estado ativo e isolamento.
- `tests/Feature/Orders/PublicOrderFlowTest.php`: carrinho, preços calculados, snapshots, confirmação, validações e acompanhamento público.
- `tests/Feature/Orders/OrderOperationsTest.php`: transições, autorização, histórico e isolamento por tenant.
- `tests/Unit/MoneyTest.php`: conversões monetárias sem float e validação de formato.
- `tests/Unit/OrderStatusTransitionsTest.php`: matriz de transições válidas e estados terminais.
- `tests/Feature/Database/DemoSeedersTest.php`: vínculos e dados de demonstração.

Comandos recomendados:

```powershell
composer lint:check
vendor/bin/phpstan analyse --memory-limit=512M
php artisan test
composer coverage:orders
```

## Limites atuais

- Somente pedidos presenciais por mesa estão implementados; delivery, retirada e pagamento online estão fora do MVP.
- O QR identifica o contexto da mesa; não é autorização nem identificador público do pedido.
- O fluxo público não autentica clientes; o código público não sequencial do pedido é a referência de acompanhamento.
- A rota por código é mantida por compatibilidade; novos QR Codes devem usar UUID.
