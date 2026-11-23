# Mesa7 — Especificação de Produto e Arquitetura para Implementação

> Documento-base do produto e referência para implementação incremental.
> Projeto acadêmico — Laboratório de Engenharia de Software — 2026/2.

---

## 1. Visão geral

**Mesa7** é uma plataforma web para gestão de cardápio e pedidos de pequenos e médios estabelecimentos do setor gastronômico, como restaurantes, hamburguerias, lanchonetes e pizzarias.

A proposta central é organizar o pedido presencial iniciado no QR Code da mesa em um fluxo único, desde a escolha de produtos até a conclusão pela equipe.

O MVP presencial inclui:

1. o usuário autorizado mantém produtos, categorias e mesas do estabelecimento (tenant);
2. o cliente abre o cardápio público da mesa sem criar uma conta;
3. o cliente monta e revisa o carrinho, envia o pedido e recebe um código público para acompanhamento;
4. a equipe consulta os pedidos do tenant e registra as transições de preparo até a conclusão ou cancelamento.

Delivery, retirada, pagamento online e demais integrações permanecem fora deste MVP. Os dois documentos de requisitos em docs detalham a entrega.

Mensagem central do produto:

> **Seu restaurante. Sua marca. Seus pedidos. Seus clientes.**

Visão de longo prazo do produto (fora do MVP presencial):

> **Mesa + Delivery + Retirada → Um único sistema de pedidos.**

---

## 2. Problema

Pequenos estabelecimentos gastronômicos frequentemente recebem pedidos por canais diferentes, como atendimento presencial, mensagens, balcão e plataformas externas de delivery.

Isso pode gerar:

- pedidos não padronizados;
- dificuldade de centralizar a operação;
- retrabalho durante horários de pico;
- maior chance de erro;
- dependência de plataformas intermediárias;
- dificuldade de manter um canal digital próprio;
- falta de uma visão única do fluxo de pedidos.

O Mesa7 não pretende substituir todo o ecossistema de gestão de um restaurante. O MVP valida o fluxo de pedido presencial, o catálogo multi-tenant e o acompanhamento operacional básico.

---

## 3. Proposta de solução

Como visão de produto, o Mesa7 poderá oferecer:

- cardápio digital;
- pedidos por QR Code nas mesas;
- pedidos para retirada;
- pedidos para delivery;
- painel operacional para acompanhamento dos pedidos;
- gestão do cardápio pelo estabelecimento;
- arquitetura preparada para múltiplos restaurantes.

Quando esses canais futuros forem implementados, os pedidos poderão convergir para a mesma entidade e para fluxos operacionais compartilhados.

```text
Mesa via QR ────────┐
                    │
Delivery (futuro) ──┼──> Pedido ──> Painel operacional ──> Cozinha
                    │
Retirada (futuro) ──┘
```

O QR Code é apenas um canal de entrada.
O **núcleo do sistema é o pedido unificado**.

---

## 4. Objetivos do MVP presencial

### 4.1 Objetivo principal

Permitir que clientes façam pedidos presenciais pelo QR Code da mesa e que a equipe do estabelecimento acompanhe e atualize esses pedidos, usando um catálogo administrado no tenant.

### 4.2 Objetivos específicos

- autenticar o usuário administrativo;
- disponibilizar uma área administrativa Filament vinculada ao tenant atual;
- permitir listar, criar, editar e excluir produtos;
- permitir gerenciar categorias e mesas no tenant autorizado;
- validar os dados do produto e permitir imagem opcional;
- garantir que cada usuário acesse e altere somente produtos de restaurantes aos quais esteja vinculado;
- exibir somente restaurantes ativos, categorias ativas e produtos disponíveis;
- disponibilizar o cardápio público por slug e por UUID público da mesa;
- gerar e imprimir um QR Code com a rota pública da mesa;
- permitir adicionar, alterar e remover itens do carrinho da mesa;
- validar e recalcular valores no servidor, gravar snapshots dos itens e limpar o carrinho após o pedido;
- permitir acompanhar o pedido sem autenticação usando um identificador público não sequencial;
- disponibilizar fila administrativa, detalhes, transições válidas e histórico de status.

## 5. Fora do escopo do MVP presencial

Não implementar neste MVP presencial:

- pedidos para delivery ou retirada;
- pagamento online integrado ou coleta de dados de pagamento;
- rastreamento GPS, cálculo geográfico ou integração com entregadores;
- integração com marketplaces, impressoras térmicas ou emissão fiscal;
- interface administrativa para gerenciar domínios além do catálogo, mesas e operação de pedidos;
- cadastro/autoprovisionamento de restaurantes pela interface pública;
- gestão de usuários e papéis avançados dentro do tenant.

Também permanecem fora do escopo deste MVP, salvo decisão futura:

- pagamento online integrado;
- emissão fiscal;
- rastreamento GPS;
- integração com iFood ou outros marketplaces;
- integração com entregadores;
- estoque completo;
- financeiro/contabilidade;
- CRM avançado;
- fidelidade/cashback;
- aplicativo mobile nativo;
- múltiplas filiais por restaurante;
- analytics avançado;
- chat;
- cupons complexos;
- programa de pontos;
- IA;
- integração com impressoras térmicas;
- domínio customizado em produção.

A arquitetura deve permitir evolução futura, mas **não antecipar complexidade sem necessidade**.

---

# 6. Stack definida

## 6.1 Backend

- PHP `^8.3`
- Laravel `^13.17`
- Laravel Fortify `^1.37.2`
- Laravel Tinker `^3.0`
- Livewire `^4.1`
- Livewire Flux `^2.13.1`
- Livewire Blaze `^1.0`
- Laravel Chisel `^0.1.0`
- Filament para o painel administrativo e os Resources da primeira entrega

## 6.2 Frontend

- Livewire
- Blade
- Flux UI
- Tailwind CSS `^4.0.7`
- Vite `^8.0.0`
- Vite Plus `0.3.0`
- Laravel Vite Plugin `^3.1`

## 6.3 Qualidade e testes

- Pest `^5.3`
- Pest Plugin Laravel `^5.0`
- Larastan `^3.9`
- Laravel Pint `^1.27`
- Mockery `^1.6`
- Faker `^1.24`

## 6.4 Regras técnicas

Filament é a ferramenta core da área administrativa nesta entrega. Implementar o painel administrativo e o cadastro de produtos com Filament Resources, aproveitando os recursos de tenancy do Filament quando aplicável.

Não substituir Livewire por SPA em React/Vue.

Para a área administrativa, preferir os recursos do Filament a CRUDs administrativos customizados em Livewire. Fora desse painel, priorizar recursos nativos do Laravel antes de adicionar pacotes externos.

Evitar dependências sem necessidade real.

---

# 7. Arquitetura geral

A aplicação seguirá uma arquitetura Laravel tradicional, organizada por domínio e responsabilidades.

```text
Browser
   │
   ▼
Laravel Routes / Filament Panel
   │
   ▼
Middleware
 ├─ Authentication
 ├─ Tenant (Restaurant) Context
 └─ Authorization
   │
   ▼
Filament Resources / Livewire Components / Controllers
   │
   ▼
Actions / Services
   │
   ▼
Eloquent Models
   │
   ▼
Database
```

Princípios:

- regras de negócio fora das views;
- componentes Livewire não devem concentrar lógica complexa;
- consultas sempre respeitando o contexto do restaurante;
- autorização usando Policies/Gates;
- operações importantes encapsuladas em Actions/Services;
- estados do pedido modelados explicitamente;
- itens do pedido armazenam snapshots dos dados do produto.

---

# 8. Multi-tenancy

## 8.1 Estratégia do MVP

Usar **shared database / shared schema multi-tenancy**.

Ou seja:

```text
1 aplicação
1 banco
N restaurantes
```

As tabelas pertencentes a um estabelecimento terão `restaurant_id`.

Exemplos:

```text
categories.restaurant_id
products.restaurant_id
tables.restaurant_id
orders.restaurant_id
restaurant_user.restaurant_id
```

Não criar um banco por tenant no MVP.

Para a primeira entrega, a criação do restaurante e o vínculo inicial do usuário podem ser providos pelo seed de demonstração ou por procedimento controlado de setup; não criar fluxo público de cadastro/provisionamento de tenants.

## 8.2 Restaurant Context

Criar uma classe responsável pelo contexto atual:

```text
App\Support\RestaurantContext
```

Responsabilidades:

- armazenar o restaurante atual durante a requisição;
- impedir acesso a recursos de outro restaurante;
- disponibilizar o tenant para Services, Policies e Components.

No painel administrativo, o tenant atual deve ser resolvido e autorizado pela integração de tenancy do Filament. `RestaurantContext` só deve ser mantido como adaptador da aplicação se houver consumidores fora do painel; não criar uma segunda fonte de verdade para o tenant selecionado.

Exemplo conceitual:

```php
app(RestaurantContext::class)->set($restaurant);
app(RestaurantContext::class)->current();
```

Evitar acessar restaurante atual por variáveis globais ou session espalhada pelo código.

## 8.3 Resolução do restaurante no MVP

Usar inicialmente **slug na URL**.

Exemplo:

```text
/r/burger-do-ze
/r/burger-do-ze/menu
/r/burger-do-ze/mesa/M07
```

Rotas administrativas poderão ser:

```text
/painel/restaurantes/{restaurant:slug}
```

O usuário autenticado só poderá abrir restaurantes aos quais esteja vinculado.

### Evolução futura

A arquitetura deve permitir adicionar:

```text
burgerdoze.mesa7.com
burgerdoze.com.br
```

Para isso, poderá existir futuramente uma tabela `restaurant_domains`.

Não é necessário implementar resolução por domínio customizado no MVP.

---

# 9. Usuários e autorização

## 9.1 Usuários administrativos

Usar a tabela padrão `users`.

Um usuário pode pertencer a um ou mais restaurantes.

Criar pivot:

```text
restaurant_user
```

Campos:

- `id`
- `restaurant_id`
- `user_id`
- `role`
- timestamps

Roles iniciais:

```text
owner
manager
attendant
kitchen
```

### owner

Pode:

- gerenciar cardápio;
- gerenciar mesas e QR Codes;
- visualizar e operar pedidos.

### manager

Pode:

- gerenciar cardápio;
- gerenciar mesas e QR Codes;
- visualizar e operar pedidos.

### attendant

Pode:

- visualizar a fila e os detalhes dos pedidos do estabelecimento;
- avançar ou cancelar pedidos conforme as transições permitidas.

### kitchen

Pode:

- visualizar pedidos;
- alterar status operacional.

Não criar sistema de permissões extremamente granular no MVP.

## 9.2 Cliente final

O cliente **não precisa criar uma conta** no MVP.

Para futuros fluxos de delivery/retirada, solicitar somente os dados necessários.

Exemplos:

- nome;
- telefone;
- endereço, quando delivery;
- observação opcional.

Para pedido de mesa:

- nome pode ser opcional;
- a mesa será identificada pelo QR Code/URL.

---

# 10. Modelo de domínio

## 10.1 Restaurant

Representa um estabelecimento.

Campos sugeridos:

```text
id
name
slug
description nullable
phone nullable
whatsapp nullable
logo_path nullable
primary_color nullable
secondary_color nullable
is_active
created_at
updated_at
```

Regras:

- `slug` único;
- restaurante inativo não aceita novos pedidos;
- dados de aparência devem ter defaults da identidade Mesa7.

## 10.2 Category

Campos:

```text
id
restaurant_id
name
slug
description nullable
sort_order
is_active
created_at
updated_at
```

Regras:

- sempre pertence a um restaurante;
- ordenação configurável;
- categorias inativas não aparecem no cardápio público.

## 10.3 Product

Campos:

```text
id
restaurant_id
name
description nullable
price decimal(10,2)
image_path nullable
is_available
created_at
updated_at
```

Regras:

- todo produto pertence a exatamente um restaurante;
- o tenant é atribuído no servidor a partir do contexto autorizado, nunca de um `restaurant_id` livremente enviado pelo formulário;
- nome e preço são obrigatórios; descrição e imagem são opcionais;
- preço é armazenado como `decimal(10,2)` e validado no servidor;
- `category_id` é opcional; categorias organizam o cardápio público e podem ser gerenciadas no painel do estabelecimento.

## 10.4 RestaurantTable

Evitar model chamado `Table`, pois conflita semanticamente com conceitos do framework/banco.

Model:

```text
RestaurantTable
```

Tabela:

```text
restaurant_tables
```

Campos:

```text
id
restaurant_id
name
code
is_active
created_at
updated_at
```

Exemplo:

```text
name = Mesa 7
code = M07
```

Regra:

- `code` único dentro do restaurante.

URL pública:

```text
/r/{restaurant:slug}/mesa/{restaurantTable:code}
```

## 10.5 Order

Representa qualquer pedido.

Campos:

```text
id
restaurant_id
restaurant_table_id nullable
public_id
source
status
customer_name nullable
customer_phone nullable
delivery_address nullable
notes nullable
subtotal decimal(10,2)
delivery_fee decimal(10,2) default 0
total decimal(10,2)
placed_at
completed_at nullable
created_at
updated_at
```

### source

Enum:

```text
table
delivery
pickup
```

### status

Enum:

```text
new
preparing
ready
completed
cancelled
```

Não usar strings soltas espalhadas pelo código. Criar PHP Enums:

```text
App\Enums\OrderSource
App\Enums\OrderStatus
```

### public_id

Usar um identificador público não sequencial ou código amigável para o cliente.

Exemplo:

```text
M7-8F42K
```

Não expor necessariamente o ID incremental nas páginas públicas.

## 10.6 OrderItem

Campos:

```text
id
order_id
product_id nullable
product_name
unit_price decimal(10,2)
quantity unsignedInteger
notes nullable
line_total decimal(10,2)
created_at
updated_at
```

Importante:

`product_name` e `unit_price` são snapshots.

Se o restaurante alterar ou excluir um produto depois, o pedido histórico deve continuar correto.

---

# 11. Relacionamentos principais

```text
Restaurant
 ├── hasMany Categories
 ├── hasMany Products
 ├── hasMany RestaurantTables
 ├── hasMany Orders
 └── belongsToMany Users

Category
 ├── belongsTo Restaurant
 └── hasMany Products

Product
 ├── belongsTo Restaurant
 └── belongsTo Category (opcional)

RestaurantTable
 ├── belongsTo Restaurant
 └── hasMany Orders

Order
 ├── belongsTo Restaurant
 ├── belongsTo RestaurantTable nullable
 └── hasMany OrderItems

OrderItem
 ├── belongsTo Order
 └── belongsTo Product nullable
```

---

# 12. Regras de consistência multi-tenant

Toda operação deve validar que os recursos envolvidos pertencem ao mesmo restaurante.

Exemplos que devem falhar:

```text
Categoria do Restaurante A + Produto do Restaurante B
Mesa do Restaurante A + Pedido do Restaurante B
Usuário sem vínculo + Painel do Restaurante C
```

Nunca confiar apenas em IDs recebidos via request.

Exemplo:

Errado:

```php
Product::findOrFail($productId);
```

Correto conceitualmente:

```php
$restaurant->products()->findOrFail($productId);
```

ou usar escopo equivalente.

---

# 13. Fluxo do cliente

## 13.1 Cardápio geral

```text
/r/{restaurant:slug}
```

Cardápio público do restaurante, resolvido pelo slug. A rota não exige autenticação.

Exibir:

- logo;
- nome;
- categorias;
- produtos disponíveis;
- descrição e contato, quando cadastrados.

Exibir apenas restaurantes ativos, categorias ativas e produtos disponíveis. A rota geral é de consulta; ações de carrinho aparecem quando o cliente entra pelo contexto de uma mesa ativa.

## 13.2 Contexto de mesa e pedido presencial

```text
QR Code da mesa
  ↓
/r/burger-do-ze/cardapio/{uuid-da-mesa}
  ↓
Cardápio da Mesa 07 → carrinho → revisão e observação
  ↓
Confirmação → pedido presencial → código público de acompanhamento
```

O UUID da mesa deve ser resolvido dentro do restaurante indicado pelo slug; mesa inativa, inexistente ou pertencente a outro restaurante retorna 404. O código legível da mesa é uma referência administrativa. O pedido grava a mesa, o tenant, os itens e os preços calculados no servidor.

## 13.3 Delivery (futuro; fora do MVP presencial)

```text
/r/burger-do-ze
  ↓
Selecionar Delivery
  ↓
Cardápio
  ↓
Carrinho
  ↓
Nome + telefone + endereço
  ↓
Confirmar
  ↓
Order source = delivery
```

Quando o canal de delivery for implementado, a taxa poderá ser zero ou um valor fixo configurado no restaurante.

Não implementar cálculo geográfico no MVP.

## 13.4 Retirada (futuro; fora do MVP presencial)

```text
/r/burger-do-ze
  ↓
Selecionar Retirada
  ↓
Cardápio
  ↓
Carrinho
  ↓
Nome + telefone
  ↓
Confirmar
  ↓
Order source = pickup
```

## 13.5 Acompanhamento

Após criar o pedido:

```text
/r/{restaurant:slug}/pedido/{order:public_id}
```

Mostrar:

- código do pedido;
- itens;
- total;
- tipo;
- status atual.

Pode usar polling do Livewire para atualização simples no MVP.

Não é necessário WebSocket inicialmente.

---

# 14. Carrinho

O carrinho permanece na sessão e fica vinculado ao estabelecimento e à mesa.

Estrutura conceitual:

```php
[
    'restaurant_id' => 1,
    'source' => 'table',
    'table_id' => 7,
    'items' => [
        [
            'product_id' => 10,
            'quantity' => 2,
            'notes' => null,
        ],
    ],
]
```

Regras:

- um carrinho só pode conter produtos de um restaurante;
- preço não deve ser armazenado como fonte confiável no cliente;
- recalcular preços no checkout;
- validar mesa, tenant e disponibilidade dos produtos ao confirmar;
- impedir quantidade <= 0;
- limpar carrinho após criação bem-sucedida do pedido.

---

# 15. Criação de pedido

Criar uma Action:

```text
App\Actions\Orders\CreateOrder
```

Responsabilidades:

1. validar contexto;
2. carregar produtos pelo restaurante atual;
3. validar disponibilidade;
4. recalcular subtotal;
5. recalcular subtotal e total do pedido de mesa, sem taxa de entrega;
6. calcular total;
7. iniciar transaction;
8. criar `orders`;
9. criar `order_items`;
10. finalizar transaction;
11. limpar carrinho;
12. retornar pedido criado.

Sempre usar database transaction.

---

# 16. Máquina de estados do pedido

Fluxo padrão:

```text
new
 ↓
preparing
 ↓
ready
 ↓
completed
```

Cancelamento:

```text
new ───────> cancelled
preparing ─> cancelled
ready ─────> cancelled
```

Não permitir:

```text
completed -> qualquer outro status
cancelled -> qualquer outro status
ready -> new ou preparing
```

Encapsular transições em:

```text
App\Actions\Orders\TransitionOrderStatus
```

A camada de UI não deve simplesmente atribuir qualquer status recebido.

---

# 17. Painel administrativo

O painel administrativo será implementado com Filament e exigirá autenticação. O tenant do painel será um `Restaurant`; cada usuário só poderá selecionar restaurantes aos quais esteja vinculado.

Os recursos administrativos do MVP são Produtos, Categorias, Mesas e Pedidos. O tenant é um `Restaurant`, selecionado entre os estabelecimentos aos quais o usuário está vinculado. O cardápio público e o acompanhamento de pedidos são telas Blade independentes do painel.

URL do painel:

```text
/admin/{restaurant:slug}
```

O painel pode permitir a seleção de outro tenant autorizado pelo usuário. A resolução da URL/tenant e a autorização de acesso devem ser aplicadas no servidor.

Navegação administrativa: Catálogo (Produtos e Categorias) e Salão (Mesas e Pedidos). O Resource Pedidos inclui detalhes, fila por tenant e ações de status.

---

# 18. Operação de pedidos

Exibir:

- quantidade de pedidos novos;
- pedidos em preparo;
- pedidos prontos;
- pedidos recentes.

Layout sugerido:

```text
NOVOS
PREPARANDO
PRONTOS
```

Informações mínimas por pedido:

- código;
- origem;
- mesa quando aplicável;
- nome do cliente quando aplicável;
- tempo desde criação;
- itens;
- observações;
- total;
- ações de mudança de status.

O Resource Pedidos lista pedidos do tenant e inclui todos os status para permitir consulta do histórico. As ações operacionais aparecem conforme o status: iniciar preparo, marcar como pronto, finalizar ou cancelar. O modal de detalhes mostra itens, observações e o histórico cronológico de transições.

---

# 19. Gestão de cardápio

## Categorias

Permitir:

- criar;
- editar;
- ativar/desativar;
- reordenar;
- excluir se não causar inconsistência.

No MVP, pode impedir exclusão de categorias com produtos.

## Produtos

Permitir:

- criar;
- editar;
- alterar preço;
- alterar descrição;
- marcar disponível/indisponível;
- imagem opcional;
- mudar categoria.

---

# 20. Gestão de mesas

Permitir:

- criar mesa;
- editar nome;
- ativar/desativar;
- visualizar código;
- gerar URL do QR Code.

Exemplo:

```text
Mesa 07
/r/burger-do-ze/mesa/M07
```

Cada mesa ativa possui UUID público. A ação administrativa gera uma página HTML independente, com o QR, as informações da mesa e a URL pública por UUID. A página permite imprimir pelo navegador. O QR abre o cardápio da mesa e permite iniciar o fluxo de carrinho e pedido presencial.

---

# 21. Identidade visual

Marca:

```text
Mesa7
```

Conceito:

```text
hospitalidade + tecnologia
```

Paleta base:

```text
Carvão     #1F1F1F
Creme      #F7F4EF
Terracota  #E36A3C
Sálvia     #A6B29A
Areia      #E8DDC6
```

Direção visual:

- minimalista;
- elegante;
- moderna;
- responsiva;
- cards discretos;
- bastante espaço;
- evitar aparência de dashboard corporativo genérico;
- evitar gradientes neon;
- evitar azul/roxo como cores principais.

---

# 22. White-label

No MVP, implementar apenas personalização mínima por restaurante:

- nome;
- logo opcional;
- cor primária opcional.

Futuro:

- domínio customizado;
- favicon;
- tipografia;
- banners;
- tema;
- páginas institucionais.

---

# 23. Componentes Livewire sugeridos

```text
app/
└── Livewire/
    ├── Public/
    │   ├── RestaurantMenu.php
    │   ├── Cart.php
    │   ├── Checkout.php
    │   └── OrderTracking.php
    │
    └── Dashboard/
        ├── RestaurantSwitcher.php
        ├── Orders/
        │   ├── OrderBoard.php
        │   ├── OrderCard.php
        │   └── OrderDetails.php
        │
        ├── Categories/
        │   ├── CategoryIndex.php
        │   └── CategoryForm.php
        │
        ├── Products/
        │   ├── ProductIndex.php
        │   └── ProductForm.php
        │
        └── Tables/
            ├── TableIndex.php
            └── TableForm.php
```

Evitar transformar tudo em um único componente gigante.

---

# 24. Actions / Support / Enums

```text
app/
├── Actions/
│   └── Orders/
│       ├── CreateOrder.php
│       └── TransitionOrderStatus.php
├── Support/
│   └── RestaurantContext.php
└── Enums/
    ├── OrderSource.php
    └── OrderStatus.php
```

Adicionar Services apenas se houver responsabilidade real.

Não criar camadas artificiais só por "arquitetura".

---

# 25. Policies

Criar Policies para:

```text
Restaurant
Category
Product
RestaurantTable
Order
```

Toda tela administrativa deve respeitar autorização no backend.

Esconder botão não é autorização.

---

# 26. Banco de dados

As migrations definem as tabelas de catálogo, pedidos, itens e histórico de status usadas neste MVP.

Tabelas usadas diretamente pelo MVP presencial:

```text
users
restaurants
restaurant_user
categories
products
restaurant_tables
orders
order_items
order_status_histories
```

categories, restaurant_tables, orders e order_items armazenam o catálogo e os pedidos; order_status_histories registra o status inicial e cada transição.

Não adicionar tabelas sem necessidade de domínio.

Índices necessários conforme cada fase for implementada:

```text
categories.restaurant_id
products.restaurant_id
products.category_id
restaurant_tables.restaurant_id
orders.restaurant_id
orders.status
orders.created_at
order_items.order_id
```

Constraints:

```text
restaurants.slug UNIQUE
restaurant_user (restaurant_id, user_id) UNIQUE
restaurant_tables (restaurant_id, code) UNIQUE
restaurant_tables.uuid UNIQUE
```

Não usar cascade indiscriminadamente em dados históricos de pedidos.

---

# 27. Dinheiro e integridade

Nunca usar float.

Usar:

```text
decimal(10,2)
```

no banco.

Nunca confiar no preço ou total enviado pelo frontend; CreateOrder recalcula os valores usando preços atuais do catálogo.

Pedidos não devem ser apagados pelo painel normal.

Snapshots em `order_items` preservam o histórico.

---

# 28. Segurança

Requisitos obrigatórios:

- CSRF padrão Laravel;
- validação server-side;
- autorização via Policies;
- tenant isolation;
- evitar mass assignment inseguro;
- nunca confiar em preço vindo do cliente;
- validar upload de imagem;
- limitar tipo/tamanho de upload;
- escape padrão do Blade;
- rate limiting onde fizer sentido;
- Fortify para autenticação administrativa.

---

# 29. Performance

O MVP não precisa de arquitetura distribuída.

Prioridades:

- eager loading;
- evitar N+1;
- índices corretos;
- paginação no histórico;
- queries limitadas ao restaurante atual;
- polling apenas onde necessário.

Não adicionar Redis, filas ou WebSockets sem necessidade imediata.

---

# 30. Testes

Usar Pest.

No MVP presencial, cobrir pelo menos:

- usuário autenticado acessa somente tenants aos quais está vinculado;
- CRUD de produtos respeita o tenant ativo;
- não é possível ler, editar ou excluir produto de outro tenant manipulando ID ou URL;
- nome, preço e upload opcional são validados;
- cardápio público respeita restaurante ativo, categoria ativa, disponibilidade do produto e contexto de mesa.

Os testes do MVP presencial cobrem os fluxos de pedido e operação descritos abaixo:

### Multi-tenancy

- usuário do restaurante A não acessa restaurante B;
- produto A não pode ser usado em pedido B;
- mesa A não pode ser usada em pedido B.

### Cardápio

- produto disponível aparece;
- produto indisponível não pode ser comprado;
- categoria inativa não aparece.

### Carrinho

- só aceita produtos do tenant e da mesa atuais;
- quantidades inválidas ou acima do limite são rejeitadas;

### Confirmação do pedido presencial

- pedido de mesa exige mesa válida;
- o MVP aceita somente pedidos de mesa; delivery não é oferecido;
- não há fluxo de retirada no MVP;
- total é recalculado no backend.

### Pedidos

- status inicial é `new`;
- transições válidas funcionam;
- transições inválidas falham;
- status, horário e responsável ficam no histórico;
- snapshot do item permanece após alteração ou exclusão do produto.

### Autorização

- `owner` e `manager` administram catálogo e mesas e podem operar pedidos;
- `attendant` e `kitchen` podem consultar e operar pedidos, sem acesso administrativo ao catálogo.

---

# 31. Factories e seeders

Factories necessárias para a primeira entrega:

```text
User
Restaurant
Product
```

Seed mínimo de demonstração:

```text
Dois estabelecimentos de demonstração
Usuários vinculados a um ou mais estabelecimentos com papéis distintos
Categorias e pelo menos 20 produtos por estabelecimento
Mesas ativas para testar QR, cardápio, carrinho e pedidos
```

Pedidos podem ser criados pelo fluxo público de QR para demonstrar a operação; não inserir pedidos de cliente fictícios automaticamente no seeder.

A aplicação deve ficar demonstrável após:

```bash
php artisan migrate:fresh --seed
```

---

# 32. Estratégia de implementação

## Fase 1 — Fundação do tenant administrativo

- modelo e migration de Restaurant;
- vínculo usuário-restaurante e autorização mínima;
- autenticação administrativa existente;
- configuração do painel Filament com Restaurant como tenant;
- isolamento e policy de acesso ao tenant.

## Fase 2 — Cadastro de produtos (base administrativa atual)

- migration e model Product com `restaurant_id`;
- Filament Product Resource com listagem, criação, edição e exclusão;
- validação de nome, preço, descrição opcional e imagem opcional;
- tenant atribuído pelo contexto do painel e escopo de consultas por tenant;
- testes de CRUD, validação, autorização e isolamento entre tenants.

## Evolução por fases

As Fases 1 a 5 formam o MVP presencial implementado: catálogo multi-tenant, cardápio público, QR de mesa, carrinho, pedidos e operação.

### Fase 3 — Catálogo, cardápio e QR (implementada)

- CRUD multi-tenant de categorias, produtos e mesas;
- UUID e página imprimível de QR para mesas ativas;
- cardápio público por slug e por UUID da mesa;
- publicação somente de categorias ativas e produtos disponíveis.

### Fase 4 — Carrinho e pedido presencial (implementada)

- carrinho de sessão vinculado ao estabelecimento e à mesa;
- adicionar, alterar e remover produtos e quantidades;
- validação server-side de mesa, disponibilidade e tenant;
- cálculo monetário no servidor e snapshots do produto no item;
- criação transacional do pedido e registro do status inicial;
- código público para acompanhamento sem conta;
- delivery, retirada e pagamento online permanecem fora do MVP.

### Fase 5 — Operação de pedidos (implementada)

- Resource Pedidos do Filament limitado ao tenant atual;
- visualização de itens e histórico;
- transições permitidas de pendente a preparo, pronto, finalizado ou cancelado;
- identificação do usuário e horário em cada transição;
- acompanhamento público do status com atualização periódica.
### Fase 6 — Refinamento futuro

- UI;
- identidade Mesa7;
- testes;
- performance;
- acessibilidade básica;
- tratamento de erros.

---

# 33. Critérios de aceite do MVP presencial

Esta entrega é considerada funcional quando:

1. um usuário autenticado vinculado a um restaurante acessa o painel daquele tenant;
2. o painel Filament apresenta o recurso Produtos;
3. o usuário consegue listar, criar, editar e excluir produtos;
4. os dados obrigatórios e a imagem opcional são validados no servidor;
5. o tenant do produto é definido pelo contexto autorizado do painel;
6. um usuário não consegue consultar nem alterar produtos de outro restaurante, inclusive manipulando IDs ou URLs;
7. os testes de CRUD, autorização e isolamento passam.
8. um cliente consulta o menu de um estabelecimento ativo sem autenticação;
9. produtos indisponíveis e categorias inativas não aparecem;
10. URL com UUID de mesa exibe o mesmo cardápio e só aceita mesa ativa do estabelecimento informado;
11. categorias e mesas são gerenciadas no tenant autorizado, com as permissões previstas;
12. a página do QR mostra um código legível e imprimível, associado à URL pública da mesa;
13. o cliente pode criar um pedido somente pelo contexto da mesa e acompanha o andamento por identificador público; o backend valida tenant, mesa, disponibilidade e preços;
14. a equipe consulta pedidos do tenant, abre os detalhes e só executa as transições permitidas;
15. cada mudança de status registra horário e responsável, e o pedido mantém snapshots dos itens;

---

# 34. Definição de pronto

Uma feature só é considerada concluída quando:

- funciona;
- possui validação;
- respeita tenant;
- respeita autorização;
- possui estado de erro;
- possui teste quando a regra é relevante;
- mantém pelo menos 85% de cobertura de linhas nas Actions de pedido, verificada por `composer coverage:orders` no CI;
- passa Pint;
- passa Larastan;
- passa Pest.

Comandos esperados:

```bash
composer lint:check
composer types:check
composer test
```

---

# 35. Regras para o Codex

Ao implementar:

1. **não alterar a stack definida sem necessidade**;
2. **usar Filament como ferramenta core da área administrativa e implementar o CRUD de produtos como Resource**;
3. **não adicionar SPA framework**;
4. usar Livewire + Flux nas telas existentes fora do painel Filament;
5. preferir código Laravel idiomático;
6. usar a validação de formulários do Filament no painel administrativo e Form Requests/Livewire conforme o contexto fora dele;
7. usar Policies;
8. evitar lógica de negócio em Blade;
9. evitar componentes Livewire gigantes;
10. evitar overengineering;
11. manter o painel administrativo limitado a produtos, categorias, mesas e operação de pedidos; o menu geral é somente para leitura e o fluxo de compra exige contexto de mesa;
12. preservar isolamento multi-tenant em todas as queries;
13. escrever testes para regras críticas;
14. manter `composer test` verde;
15. executar Pint após mudanças PHP;
16. nas fases de pedido, não confiar em valores monetários enviados pelo browser;
17. usar transactions em criação de pedidos;
18. criar código legível antes de abstrações genéricas.

---

# 36. Estrutura de diretórios esperada

```text
app/
├── Filament/
│   └── Resources/
│       └── Products/
├── Http/
│   ├── Controllers/
│   └── Middleware/
├── Livewire/ (telas existentes e funcionalidades futuras)
├── Models/
├── Policies/
└── Support/

resources/
├── css/
├── js/
└── views/
    ├── components/
    ├── livewire/
    └── layouts/

tests/
├── Feature/
└── Unit/
```

Não criar Repository Pattern para CRUD simples sem justificativa.

---

# 37. Evolução futura

Depois do MVP, a arquitetura poderá evoluir para:

### White-label completo
- domínio próprio;
- temas;
- favicon;
- banners;
- personalização avançada.

### CRM
- clientes recorrentes;
- histórico;
- ticket médio;
- segmentação;
- campanhas.

### Operação
- KDS mais avançado;
- impressão;
- estoque;
- ficha técnica;
- múltiplas unidades.

### Comercial
- planos SaaS;
- assinatura;
- limites por plano;
- billing.

### Integrações
- gateways;
- WhatsApp;
- marketplaces;
- fiscal;
- entregadores.

Esses itens não fazem parte da primeira implementação.

---

# 38. Modelo de negócio conceitual

O Mesa7 foi pensado como um **Vertical SaaS**.

```text
Mesa7
 ├── Restaurante A
 ├── Restaurante B
 ├── Restaurante C
 └── Restaurante N
```

Cada restaurante possui seus próprios:

- usuários;
- produtos;
- categorias;
- mesas;
- pedidos;
- identidade.

A proposta comercial futura é assinatura recorrente, evitando comissão percentual sobre cada pedido realizado através do canal próprio.

Esse modelo de monetização não precisa ser implementado no MVP acadêmico.

---

# 39. Conceito final

Mesa7 não deve ser tratado apenas como:

```text
"um cardápio por QR Code"
```

O produto deve ser entendido como:

> **uma plataforma própria de pedidos e operação digital para restaurantes.**

O QR Code é um canal.
Delivery é um canal.
Retirada é um canal.
O cardápio é a interface de venda.
O painel é a interface operacional.
O pedido é o núcleo do domínio.
O multi-tenancy é a base arquitetural.
O SaaS é a visão de negócio.

---

# 40. Resumo executivo do MVP presencial

Escopo implementado:

```text
ADMINISTRAÇÃO MULTI-TENANT
+ CATÁLOGO: PRODUTOS E CATEGORIAS
+ MESAS E QR CODE IMPRIMÍVEL
+ CARDÁPIO PÚBLICO POR MESA
+ CARRINHO E PEDIDO PRESENCIAL
+ ACOMPANHAMENTO PÚBLICO
+ FILA, TRANSIÇÕES E HISTÓRICO DE STATUS
```

Fluxo do cliente:

```text
QR da mesa
  ↓
Cardápio → carrinho → revisão e observação
  ↓
Confirmação → pedido e código público
  ↓
Acompanhamento do preparo até o encerramento
```

Fluxo da equipe:

```text
Painel Filament do tenant
  ↓
Salão → Pedidos
  ↓
Detalhes → iniciar preparo → marcar pronto → finalizar ou cancelar
```

Delivery, retirada, pagamento online e integrações permanecem fora do MVP presencial.

---
# 41. Stack de referência

## composer.json

A implementação deve respeitar o `composer.json` atual do projeto, que já inclui Filament para o painel administrativo. A base atual inclui:

```text
PHP ^8.3
Laravel 13.17
Livewire 4.1
Livewire Flux 2.13
Livewire Blaze 1.0
Laravel Fortify 1.37
Filament 5 (painel administrativo)
Pest 5
Larastan 3.9
Pint 1.27
```

## package.json

Frontend baseado em:

```text
Tailwind CSS 4
Vite 8
Vite Plus
Laravel Vite Plugin
```

Não trocar essas tecnologias sem solicitação explícita.
