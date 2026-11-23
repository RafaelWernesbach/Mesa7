# Roteiro do MVP presencial e manual de uso

Este documento reúne o estado das migrations, a cobertura dos requisitos, as melhorias planejadas para o banco e os passos para demonstrar o Mesa7 localmente.

As referências analisadas foram `Mesa7_Especificacao_Requisitos_MVP_Presencial_RF10.docx` e `Mesa7_Requisitos_Funcionais_Nao_Funcionais_Escopo_UML.docx`. Quando há diferenças entre a visão futura do segundo documento e o escopo restrito do primeiro, este roteiro segue o MVP presencial: pedidos por mesa, sem delivery e sem pagamento integrado. Os números RF dos documentos não são equivalentes; use o nome do requisito ao comparar as listas.

## 1. Estado do MVP

O fluxo principal está implementado: administração do catálogo e das mesas por tenant, cardápio público via QR de mesa, carrinho, confirmação de pedido, acompanhamento público e operação com histórico de status.

**Não falta uma tabela ou migration funcional para concluir o fluxo principal do MVP.** O carrinho fica no navegador, com sessão como alternativa sem JavaScript; portanto, não precisa de tabela própria. A aplicação valida no servidor as regras que o banco ainda não impõe como restrições físicas. As melhorias de integridade listadas na seção 4 são reforços planejados, não impedimentos para a demonstração atual.

### Escopo desta versão

- Incluído: categorias, produtos e disponibilidade; mesas e QR estático; pedido presencial; fila e detalhes; status e histórico; acompanhamento sem conta de cliente.
- Fora do MVP: delivery, retirada, pagamento integrado, dados completos de cliente, GPS, despacho, estoque avançado, ERP, fidelidade e analytics.
- A tabela `orders` já contém alguns campos candidatos a delivery (`customer_name`, `customer_phone`, `delivery_address`, `delivery_fee`). Eles são estrutura futura e não habilitam um fluxo de delivery.
- A tabela `restaurant_domains` existe como estrutura futura; domínios personalizados ainda não fazem parte do fluxo atual.

## 2. Migrations e dados persistidos

| Tabela | Uso atual | Observações de integridade |
| --- | --- | --- |
| `users` | Contas administrativas e autenticação | E-mail único; credenciais são armazenadas pelo mecanismo de autenticação do Laravel. |
| `restaurants` | Tenant e dados básicos do estabelecimento | `slug` único; inclui campos de perfil visual. |
| `restaurant_user` | Vínculo usuário–tenant e papel | `UNIQUE (restaurant_id, user_id)`; o papel é armazenado como texto. |
| `categories` | Categorias próprias de cada tenant | Slug único por tenant; ordem e estado ativo indexados. |
| `products` | Produtos, preço, descrição, imagem e disponibilidade | Categoria opcional; a interface limita a seleção ao tenant atual. |
| `restaurant_tables` | Mesas, código interno, estado ativo e UUID do QR | Código único por tenant; UUID único globalmente e obrigatório. |
| `orders` | Pedido, tenant, mesa, identificador público, valores, origem, status e datas | `public_id` único; índices por tenant/status e tenant/criação. O fluxo implementado cria pedidos `table`; outras origens ainda não têm interface/fluxo. |
| `order_items` | Itens e cópias do nome e preço do produto no pedido | Produto pode ser nulo após exclusão; o snapshot mantém a consulta histórica. Quantidade é unsigned no banco e validada como positiva pela aplicação. |
| `order_status_histories` | Status inicial e transições com horário e usuário opcional | Histórico não é apagado junto com pedido; a migration cria uma entrada inicial para pedidos preexistentes. |
| `restaurant_domains` | Reserva para domínio personalizado | Não participa do fluxo atual. |

### Requisitos cobertos pelo modelo atual

| Requisito do documento presencial | Implementação e persistência | Situação |
| --- | --- | --- |
| RF01 — acessar pelo QR da mesa ativa | `restaurant_tables.uuid`, `is_active` e rota por UUID escopada pelo slug do tenant | Implementado |
| RF02 — consultar categorias e produtos disponíveis | `categories`, `products`, `is_active` e `is_available` | Implementado |
| RF03 — adicionar, alterar e remover itens | Carrinho local associado ao tenant e à mesa; nenhuma migration necessária | Implementado |
| RF04 — revisar e confirmar | `orders`, `order_items` e `order_status_histories`; valores recalculados no servidor e snapshots gravados em transação | Implementado |
| RF05 — acompanhar sem conta | `orders.public_id` único e rota pública de acompanhamento | Implementado |
| RF06 — autenticar e restringir o painel | `users`, `restaurant_user.role`, tenant do Filament e policies | Implementado |
| RF07 — consultar e organizar a fila | Lista do tenant, detalhes, ordenação por chegada e filtro por status | Implementado; a fila não é um quadro de colunas agrupadas |
| RF08 — transições e histórico | `orders.status`, `completed_at` e `order_status_histories`; action valida sequência e registra ator/horário | Implementado |
| RF09 — manter catálogo sem alterar pedidos antigos | `categories`, `products`, disponibilidade e snapshots em `order_items` | Implementado |
| RF10 — manter mesas e QR | `restaurant_tables` com código, UUID único e estado ativo | Implementado |

O segundo documento usa outra numeração: por exemplo, o RF10 dele é criação de pedido, enquanto o RF10 do documento presencial é mesas e QR. No MVP, pedidos de delivery descritos no segundo documento permanecem fora do escopo.

### Responsabilidade MVC por fluxo

| Fluxo | Entrada e apresentação | Regra de negócio e persistência |
| --- | --- | --- |
| Cardápio | `PublicMenuController` e views Blade em `resources/views/public/` | Models `Restaurant`, `RestaurantTable`, `Category` e `Product` |
| Carrinho e confirmação | Rotas públicas e `PublicOrderController`; views Blade de carrinho e acompanhamento | `PublicTableCart`, `CreateOrder`, `Money`, models `Order` e `OrderItem` |
| Operação administrativa | `OrderResource` e tabela do Filament | `TransitionOrderStatus`, `OrderPolicy`, `OrderStatusHistory` |
| Banco | Migrations em `database/migrations/` | FKs, índices, unicidade, snapshots e histórico persistido |

## 3. Roteiro de demonstração ponta a ponta

Use esta lista para validar a entrega sem depender de pagamentos ou serviços externos:

Os itens marcados `[x]` têm implementação e cobertura automatizada; isso não significa que a conferência manual em cada dispositivo já foi executada.

1. [x] Preparar o banco e carregar os dados demonstrativos.
2. [x] Entrar com um papel administrativo e alternar entre tenants autorizados.
3. [x] Criar/editar categorias e produtos; testar produto disponível e indisponível.
4. [x] Criar uma mesa ativa, abrir a página de QR e imprimir pelo navegador.
5. [x] Abrir o QR como cliente sem autenticação e confirmar que a mesa correta aparece.
6. [x] Adicionar produtos, alterar quantidades, remover item e revisar valores.
7. [x] Confirmar um pedido; conferir mesa, valor, horário, status inicial e código público.
8. [x] Operar o pedido como atendente: **Pendente → Em preparo → Pronto → Finalizado**.
9. [x] Conferir no link público que o status e o histórico foram atualizados.
10. [x] Criar outro pedido e testar cancelamento; confirmar que pedido finalizado/cancelado não retorna ao preparo.
11. [x] Repetir verificações de acesso com outro tenant e com usuário sem vínculo.
12. [ ] Fazer conferência manual em celular e navegadores previstos para a demonstração; os testes automatizados não substituem essa verificação visual.

Na fila do painel, a chegada é a ordenação inicial e a coluna de status pode ser filtrada. Se a equipe precisar de uma visualização em colunas/grupos por status, isso é uma melhoria de interface; não exige migration.

## 4. Roteiro do que ainda pode evoluir nas migrations

Não crie migrations novas para delivery, pagamento, perfil de cliente ou carrinho persistente dentro do escopo presencial atual. Para reforçar o modelo existente, a sequência sugerida é:

### M1 — Restrições de tenant no banco (prioridade antes de ampliar integrações)

As policies, relacionamentos Filament e Actions já validam o tenant durante o uso normal. As migrations atuais, porém, não impedem todo vínculo cruzado se alguém gravar dados diretamente no banco.

- Considerar FK composta para garantir que `products.category_id` pertença ao mesmo `restaurant_id` do produto.
- Considerar FK composta para garantir que `orders.restaurant_table_id` pertença ao mesmo `restaurant_id` do pedido de mesa.
- Definir o comportamento de exclusão antes da constraint: hoje produto pode ficar sem categoria e pedido pode manter histórico se uma mesa for removida. Uma FK composta com `SET NULL` ingênuo também pode tentar anular `restaurant_id`.
- Para vincular `order_items.product_id` ao tenant do pedido, decidir se o tenant deve ser redundante no item ou se a validação transacional da Action é a regra suficiente. Os snapshots devem continuar consultáveis depois da exclusão do produto.
- Antes da migration, consultar e corrigir registros inconsistentes; depois, cobrir integridade e exclusões com testes no mesmo banco usado em produção.

### M2 — Restrições de domínio e valores

- Avaliar `CHECK` para quantidade maior que zero, preço válido e valores não negativos, alinhados às validações já aplicadas pela UI e pelas Actions.
- Restringir `restaurant_user.role` aos papéis suportados (`owner`, `manager`, `attendant`, `kitchen`) se a equipe decidir manter uma lista fechada no banco.
- Restringir `orders.status` e `orders.source` aos valores aceitos. No MVP a origem é mesa; quando outros canais forem aprovados, ampliar a regra na mesma migration de evolução.
- Avaliar a consistência `total = subtotal + delivery_fee` sem introduzir lógica de arredondamento divergente dos cálculos em centavos da aplicação.
- Confirmar suporte de `CHECK` e FKs compostas no banco de produção antes de escrever a migration; manter os testes de integração cobrindo o banco selecionado.

### M3 — Índices de fila (quando volume justificar)

- Medir a consulta da fila e, se necessário, adicionar índice composto em `orders (restaurant_id, status, placed_at)` para tenant, filtro de status e chegada.
- Considerar índice explícito em `order_items.order_id` para a leitura de detalhes em bancos que não criem índice automaticamente para FK.
- A tabela de histórico já tem índice por `(order_id, changed_at)`.
- Medir antes de adicionar índices redundantes; validar a migration em SQLite local e no banco de produção.

### M4 — Só após aprovação de novo escopo

| Funcionalidade futura | Decisão/migration a planejar quando aprovada | Não fazer agora |
| --- | --- | --- |
| Delivery | Definir endereço obrigatório, contato, taxa/área, origem, validação e estratégia para separar dados de entrega | Não abrir formulário/rota de delivery só porque existem colunas candidatas em `orders` |
| Retirada | Definir identificação do cliente, horário e estado operacional, caso necessário | Não usar `restaurant_table_id` como substituto impróprio da retirada |
| Pagamento integrado | Modelar tentativa, provedor, estado, identificador externo e conciliação após decisão de segurança | Não armazenar dados de cartão nem marcar pedido como pago sem integração aprovada |
| Domínio personalizado | Ativar `restaurant_domains`, validação de propriedade e resolução de tenant por host | Não criar gestão de domínio para a demonstração do MVP |
| Persistência de carrinho entre dispositivos | Definir identidade/expiração antes de criar `carts` e `cart_items` | O carrinho atual é local ao navegador |
| Estoque/ERP/BI/cliente/fidelidade | Modelagem própria conforme requisitos futuros | Permanecem fora do MVP |

## 5. Manual de uso local

### 5.1 Preparar e iniciar

Requisitos: PHP 8.3+ com SQLite, Composer 2.x e Node.js compatível com Vite 8. Na raiz do projeto, em PowerShell:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Select-String -Path .env -Pattern '^APP_KEY=.+')) { php artisan key:generate }
if (-not (Test-Path database\database.sqlite)) { New-Item -ItemType File database\database.sqlite }
php artisan migrate --seed
npm.cmd install
npm.cmd run build
php artisan storage:link
composer run dev
```

Para macOS/Linux:

```bash
composer install
[ -f .env ] || cp .env.example .env
grep -q '^APP_KEY=.' .env || php artisan key:generate
[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
composer run dev
```

O comando só gera a chave quando `APP_KEY` está vazia, evitando invalidar dados criptografados de um ambiente existente.

`composer run dev` inicia servidor Laravel, fila e Vite. A aplicação fica em `http://localhost:8000`. Para somente atualizar os dados de demonstração em um banco já migrado, use `php artisan db:seed`.

> `php artisan migrate:fresh --seed` apaga e recria todas as tabelas. Use somente em banco local descartável.

### 5.2 Credenciais e estabelecimentos de demonstração

Em um banco de demonstração novo, todas as contas abaixo usam a senha local `password` e são criadas pelo seeder. Se um usuário com o mesmo e-mail já existir, `firstOrCreate` preserva a senha que já estiver gravada.

| E-mail | Papel em **Mesa7 Demo** (`mesa7-demo`) | Papel em **Cantina da Praça** (`cantina-da-praca`) | Uso sugerido |
| --- | --- | --- | --- |
| `test@example.com` | `owner` | `manager` | Administrador de demonstração com acesso aos dois tenants |
| `ana@mesa7.demo` | `manager` | `owner` | Administradora dos dois tenants |
| `bruno@mesa7.demo` | `kitchen` | Sem vínculo | Operação de pedidos na Mesa7 Demo |
| `carla@mesa7.demo` | `attendant` | `kitchen` | Atendente na Mesa7 Demo e operação na Cantina |

`owner` e `manager` acessam catálogo, mesas e pedidos. `attendant` e `kitchen` acessam a operação de pedidos, sem CRUD de catálogo. O seeder inclui pelo menos 20 produtos por estabelecimento, categorias e mesas de exemplo. Credenciais são exclusivamente para desenvolvimento; troque-as antes de qualquer implantação acessível externamente.

O proprietário pode administrar usuários e vínculos no painel do estabelecimento; os papéis demonstrativos iniciais vêm do seeder. O painel separado `/plataforma` permite ao superadministrador gerenciar estabelecimentos.

### 5.3 Acessar o painel e alternar tenant

1. Abra `http://localhost:8000/admin` e entre com uma conta da tabela.
2. Acesse diretamente `http://localhost:8000/admin/mesa7-demo` para a Mesa7 Demo ou `http://localhost:8000/admin/cantina-da-praca` para a Cantina da Praça.
3. O seletor de tenant do painel também permite escolher um estabelecimento ao qual a conta esteja vinculada.
4. Confira o nome do tenant antes de editar registros; produtos, categorias, mesas e pedidos são filtrados pelo estabelecimento ativo.

### 5.4 Administrar categorias e produtos

1. No menu **Catálogo**, abra **Categorias** e cadastre/edite nome, slug, ordem e estado ativo.
2. Abra **Produtos** e cadastre nome, preço, descrição e, opcionalmente, imagem de até 2 MB.
3. Selecione uma categoria do tenant atual. Sem categoria, o produto aparece em **Outros**.
4. Use **Disponível para venda** para controlar publicação. Produto indisponível não pode entrar em um pedido novo.
5. Ao retirar um item do cardápio, desative sua disponibilidade ou exclua-o. Pedidos anteriores preservam nome e preço nos snapshots.

### 5.5 Cadastrar mesa e imprimir QR

1. Em **Salão → Mesas**, crie uma mesa com nome e código interno único no tenant, como `Mesa 08` e `M08`.
2. Mantenha a mesa ativa e salve. O UUID é gerado pelo sistema.
3. Na lista, abra a ação **QR Code**. A página independente mostra o QR e o endereço da mesa.
4. Use o botão **Imprimir** ou a impressão do navegador.
5. A URL tem o formato `/r/{slug}/cardapio/{uuid}`. Mesa ou estabelecimento inativo não aceita pedidos.

### 5.6 Fazer e acompanhar pedido como cliente

1. Abra o endereço impresso do QR ou `http://localhost:8000/r/mesa7-demo` para consultar o cardápio sem login. Para comprar, use o QR de uma mesa ativa.
2. Adicione produtos disponíveis, ajuste quantidade e abra **Carrinho**.
3. Confira mesa, itens e valores; altere quantidades ou remova itens. Se necessário, informe uma observação geral.
4. Confirme o pedido. O servidor valida novamente mesa, disponibilidade e preço e grava o status inicial **Pendente**.
5. Guarde o link/código exibido. Acompanhe o pedido sem conta; a página atualiza o estado periodicamente.
6. O total exibido não significa pagamento pelo Mesa7. O pagamento ocorre fora do sistema, conforme o estabelecimento.

### 5.7 Operar pedidos

1. Entre no tenant correto e abra **Salão → Pedidos**.
2. A fila mostra código, mesa, chegada, status, quantidade de itens e total; use o filtro de status ou a ordenação por chegada.
3. Abra **Detalhes** para ver os itens, observações e linha do tempo do histórico.
4. Para um pedido normal, use **Iniciar preparo**, **Marcar como pronto** e **Finalizar atendimento**, nessa ordem.
5. Se necessário, cancele um pedido pendente, em preparo ou pronto. Cancelado e finalizado são estados terminais.
6. Confirme no link público que o cliente vê o status atualizado. Cada transição registra data/hora e usuário; a criação inicial tem ator nulo porque vem do cliente público.

## 6. Verificações de qualidade

```powershell
composer run test
composer run coverage:orders
npm.cmd run build
```

O CI executa Pint, PHPStan, Pest e a meta mínima de 85% de cobertura das Actions de pedidos. A medição local precisa de um driver de cobertura compatível instalado no PHP, como PCOV ou Xdebug.

Cobertura automatizada existente: fluxo público e validações do carrinho, cálculo de preço no servidor, snapshots, isolamento por tenant, autorização, transições, histórico, seeder e conversão monetária. A conferência visual em dispositivos móveis segue como passo manual da demonstração.

## 7. Documentos relacionados

- [Guia de uso atual](GUIA_DE_USO.md)
- [Estrutura técnica e MVC](ESTRUTURA_TECNICA.md)
- [Arquitetura](MESA7_ARCHITECTURE.md)
- [Diagrama lógico ERD](diagramas/MESA7_ERD.mmd)
- [README e instalação](../README.md)
