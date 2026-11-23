# Roteiro de apresentação do Mesa7

Este roteiro apresenta o Mesa7 do ponto de vista do proprietário, do cliente, da equipe e do administrador da plataforma. Ele acompanha uma demonstração presencial: configuração do estabelecimento, criação do cardápio, pedido pelo QR Code e acompanhamento até a finalização.

O fluxo demonstrado é de pedidos **presenciais por mesa**. Delivery, retirada e pagamento online não fazem parte da implementação atual.

## 1. Antes da apresentação

### Preparar o ambiente

O projeto precisa de PHP 8.4.1 ou superior com as extensões exigidas pelo Composer, incluindo `intl`, SQLite, `mbstring`, `openssl`, `curl` e `zip`; também precisa de Composer, Node.js e npm. No workspace usado neste roteiro, o PHP está em `C:\dev\mesa7\php8.4.24`.

No PowerShell, abra o projeto e selecione o PHP para o terminal atual:

```powershell
cd C:\dev\mesa7
$env:Path = "C:\dev\mesa7\php8.4.24;$env:Path"
php -v
```

Se o PHP estiver em outro local, ajuste o caminho. Para uma instalação nova, prepare as dependências e o banco com os comandos abaixo. Eles preservam um `.env`, uma chave e um arquivo SQLite já existentes:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
composer install
if (-not (Select-String -Path .env -Pattern '^APP_KEY=.+')) { php artisan key:generate }
if (-not (Test-Path database\database.sqlite)) { New-Item -ItemType File database\database.sqlite }
php artisan migrate
npm.cmd install
npm.cmd run build
```

Para carregar as contas, cardápios e mesas de demonstração e disponibilizar as imagens locais:

```powershell
php artisan db:seed
php artisan storage:link
```

O seeder não apaga o banco, mas **atualiza dados de demonstração existentes**: pode restaurar papéis, mesas, categorias, preços e disponibilidade e limpar imagens de produtos demo. Use-o em uma base de demonstração e confira os dados antes de apresentar. Se o link de armazenamento já existir, não é necessário criá-lo novamente.

Inicie o projeto no mesmo terminal:

```powershell
composer run dev
```

Mantenha o terminal aberto e acesse `http://localhost:8000`. Se o projeto já estiver rodando, use o endereço exibido no terminal. O QR com `localhost` funciona no próprio computador; para abri-lo em outro dispositivo, a aplicação precisa estar acessível pela rede e o endereço gerado precisa ser válido para esse dispositivo.

### Preparar o acesso à plataforma

O seeder não cria um superadministrador automaticamente. Para demonstrar também o painel global, execute uma vez, em outro terminal com o mesmo PHP:

```powershell
php artisan tinker
```

No Tinker, atribua a permissão à conta de demonstração existente e saia:

```php
\App\Models\User::where('email', 'test@example.com')->update(['is_super_admin' => true]);
exit
```

O retorno `1` indica que uma conta foi atualizada; `0` indica que o usuário não foi encontrado. A permissão é global e deve ser atribuída manualmente apenas em uma conta autorizada. Em ambiente real, use uma conta e credenciais próprias.

### Contas e dados para a demonstração

Em uma base recém-populada pelo seeder, as contas abaixo usam a senha **`password`**:

| Conta | Mesa7 Demo | Cantina da Praça |
| --- | --- | --- |
| `test@example.com` | Proprietário | Gerente |
| `ana@mesa7.demo` | Gerente | Proprietária |
| `bruno@mesa7.demo` | Cozinha | Sem vínculo |
| `carla@mesa7.demo` | Atendente | Cozinha |

O seeder não redefine a senha de uma conta que já existe. Se a senha tiver sido alterada anteriormente, use a senha atual. Essas credenciais são somente para demonstração local.

Os nomes dos campos próprios do Mesa7 estão em português. Conforme o idioma configurado, alguns botões e controles do Filament podem aparecer em inglês, como **Create**, **Edit**, **Save** e **Search**, equivalentes a criar, editar, salvar e pesquisar.

Os dois estabelecimentos já possuem categorias, produtos e mesas `M01`, `M02` e `M07`. Para acompanhar o pedido durante a apresentação, deixe duas janelas abertas:

- **Equipe:** janela normal, autenticada no painel administrativo.
- **Cliente:** janela anônima no mesmo computador, aberta pela URL do QR da mesa.

## 2. Entrar como proprietário e apresentar o painel

1. Na janela da equipe, acesse `http://localhost:8000/admin/mesa7-demo`.
2. Entre com **`test@example.com`** e senha **`password`**.
3. Confira no cabeçalho o estabelecimento selecionado, o campo de pesquisa e o seletor de estabelecimento.
4. Mostre os grupos **Configurações**, **Catálogo** e **Salão** no menu lateral.

**O que acontece:** o usuário entra no painel do estabelecimento ao qual está vinculado. Como ele é proprietário do Mesa7 Demo, tem acesso ao catálogo, às mesas, aos pedidos, aos usuários e à aparência desse estabelecimento.

## 3. Personalizar nome, logo e cores

1. No menu lateral, clique em **Configurações → Aparência**.
2. Em **Nome do estabelecimento**, informe, por exemplo, `Mesa7 Apresentação`.
3. Em **Logo**, selecione uma imagem PNG, JPEG ou WebP de até 2 MB, caso tenha uma disponível. A imagem é opcional.
4. Escolha uma **Cor principal** e uma **Cor secundária**.
5. Clique em **Salvar aparência** e recarregue a página.
6. Para seguir usando os nomes deste roteiro, volte a informar `Mesa7 Demo` no campo de nome, salve e recarregue a página. As cores e a logo podem permanecer como foram escolhidas.

**O que acontece:** o nome, a logo e as cores são salvos para o estabelecimento selecionado. A identidade aparece nas páginas públicas e no painel administrativo; o nome fica ao lado da logo no cabeçalho. Ao restaurar `Mesa7 Demo`, o nome anterior volta a aparecer. Alterar o nome não muda o identificador `mesa7-demo` nem as URLs dos QR Codes já criados.

Para comparar depois, anote as cores anteriores ou volte a usar os valores desejados ao terminar a apresentação.

## 4. Criar uma categoria e um produto

### Categoria

1. Clique em **Catálogo → Categorias** e depois em **Criar categoria**.
2. Em **Nome**, informe `Especial da apresentação`. O slug é sugerido após sair do campo; confira se ficou preenchido.
3. Em **Ordem no cardápio**, informe `0` e mantenha **Categoria ativa no cardápio** ligada.
4. Clique em **Criar** para salvar.

**O que acontece:** a categoria passa a pertencer somente ao estabelecimento selecionado. Ela aparecerá no cardápio quando tiver pelo menos um produto disponível. Se já existir uma categoria com o mesmo slug, escolha outro nome ou slug.

### Produto

1. Clique em **Catálogo → Produtos** e depois em **Criar produto**.
2. Preencha **Nome** com `Lanche da apresentação`, **Categoria** com `Especial da apresentação` e **Preço** com `19.90`.
3. Opcionalmente, informe uma descrição e envie uma imagem de até 2 MB.
4. Mantenha **Disponível para venda** ligado e clique em **Criar**.
5. Na lista, mostre a pesquisa por nome, a categoria, o preço e o filtro de disponibilidade.

**O que acontece:** o produto fica disponível no cardápio do Mesa7 Demo. O proprietário ou gerente pode editar o registro; produtos indisponíveis não aparecem no cardápio público.

Para mostrar a edição, abra a ação **Editar** de um produto, altere a descrição ou a disponibilidade e salve. A exclusão também está disponível nas ações do recurso; use-a apenas em registros criados para a apresentação. Se desativar o produto que será usado no pedido, volte a ativá-lo antes de continuar.

## 5. Abrir o QR Code de uma mesa

1. Clique em **Salão → Mesas**.
2. Localize **Mesa 07**, código `M07`, e clique na ação **QR Code**.
3. Mostre a página aberta em outra aba: ela contém o nome do estabelecimento, a identificação da mesa, o QR Code e a URL pública.
4. Se quiser demonstrar a impressão, clique em **Imprimir**; feche a pré-visualização sem imprimir se não precisar do papel.
5. Copie a URL exibida e abra-a na janela anônima do cliente.

**O que acontece:** o endereço contém um UUID da mesa. O cliente vê o cardápio associado ao estabelecimento e à Mesa 07, sem precisar criar conta. Para mostrar o cadastro, use **Criar mesa**, informe um nome e um código ainda não utilizado, mantenha-a ativa e salve; o UUID é gerado automaticamente.

## 6. Mostrar o cardápio e fazer um pedido

Na janela anônima do cliente:

1. Confira o nome e a identidade visual do estabelecimento, a identificação **Mesa 07** e as categorias.
2. Localize o `Lanche da apresentação` e clique em **Adicionar**. Selecione outra quantidade antes de adicionar, se desejar.
3. Adicione também outro produto, como `Água Mineral`.
4. Mostre que o cliente continua no cardápio e que o contador de **Carrinho** aumenta.
5. Clique em **Carrinho**. Altere uma quantidade e clique em **Atualizar**; remova um dos itens com **Remover**. Deixe ao menos um produto no carrinho.
6. Opcionalmente, preencha a observação, por exemplo `Sem cebola`.
7. Clique em **Confirmar pedido**.

**O que acontece:** o carrinho fica armazenado no navegador, separado por estabelecimento e mesa; adicionar, alterar e remover itens não enviam requisições ao servidor. Na confirmação, o servidor confere mesa, produtos, disponibilidade e preços antes de criar o pedido. Se um produto ficar indisponível ou seu preço mudar, revise o carrinho e tente novamente.

A tela de acompanhamento mostrará o código público do pedido, os itens, o total e o status **Pendente**. Guarde essa aba aberta. O pagamento é feito diretamente com o estabelecimento.

## 7. Operar e acompanhar o pedido

1. Volte à janela da equipe e abra **Salão → Pedidos** no Mesa7 Demo.
2. Localize o pedido pelo código exibido ao cliente. Caso ele ainda não apareça, atualize a lista.
3. Clique em **Detalhes** e mostre os itens, a observação e o histórico inicial.
4. Feche os detalhes e clique em **Iniciar preparo**. Se houver confirmação, confirme.
5. Volte à janela do cliente e aguarde a atualização: o status deve mudar para **Em preparo**.
6. Na janela da equipe, clique em **Marcar como pronto**. Confira no acompanhamento o status **Pronto**.
7. Na janela da equipe, clique em **Finalizar atendimento**. Confira no acompanhamento o status **Finalizado** e abra novamente **Detalhes** para mostrar o histórico de transições.

**O que acontece:** o pedido segue a sequência **Pendente → Em preparo → Pronto → Finalizado**. Cada mudança registra o horário e o usuário responsável. A página individual do cliente é atualizada a cada 15 segundos enquanto o pedido está pendente, em preparo ou pronto; depois de finalizado, ela deixa de atualizar automaticamente. Se a última mudança ainda não aparecer, recarregue a página.

Para demonstrar o cancelamento, crie outro pedido pela mesa e use **Cancelar pedido** no painel enquanto ele estiver pendente, em preparo ou pronto. Pedidos finalizados e cancelados não voltam ao fluxo de preparo.

## 8. Reabrir pedidos pelo cardápio

1. Na tela de acompanhamento do cliente, clique em **Voltar ao cardápio**.
2. Localize a seção **Pedidos da mesa**.
3. Mostre o pedido criado e seu status. Clique em **Acompanhar** para reabrir os detalhes.

**O que acontece:** o cardápio com UUID lista pedidos daquela mesa, inclusive finalizados e cancelados. A lista consulta os status a cada 15 segundos enquanto a aba está visível, sem recarregar a página inteira. Qualquer pessoa que tenha o QR ou a URL da mesa pode acessar essa lista e os detalhes; use dados de demonstração durante a apresentação.

O cardápio geral em `http://localhost:8000/r/mesa7-demo` pode ser consultado sem mesa. A compra e a lista de pedidos são demonstradas pela URL com UUID do QR.

## 9. Demonstrar usuários, papéis e isolamento

### Gerenciar a equipe como proprietário

1. Na janela da equipe, confirme que o estabelecimento selecionado é **Mesa7 Demo** e a conta é `test@example.com`.
2. Abra **Configurações → Usuários** e mostre os nomes, e-mails e papéis associados ao estabelecimento.
3. Clique em **Cadastrar usuário**. Informe um nome, um e-mail de demonstração ainda não cadastrado, uma senha inicial e o papel **Atendente**; confirme o cadastro.
4. Na linha criada, use **Alterar papel** para trocar para **Cozinha** e confirme.
5. Para demonstrar um vínculo existente, use **Desvincular** nessa mesma conta de demonstração. Em seguida, clique em **Vincular usuário existente**, informe o mesmo e-mail, escolha o papel e confirme.

**O que acontece:** a lista e os papéis referem-se apenas ao estabelecimento selecionado. Vincular uma conta já existente não altera sua senha nem seus vínculos com outros estabelecimentos. O sistema impede que o último proprietário seja desvinculado ou perca o papel de proprietário.

### Alternar estabelecimentos e mostrar papéis

1. No seletor ao lado da pesquisa do cabeçalho, troque de **Mesa7 Demo** para **Cantina da Praça**.
2. Abra **Catálogo → Produtos** e mostre que os produtos são os da Cantina, como `Feijoada Completa`, em vez do cardápio do Mesa7 Demo.
3. Explique que `test@example.com` é **gerente** na Cantina: pode administrar catálogo e mesas e operar pedidos, mas as configurações reservadas ao proprietário não ficam disponíveis para ele nesse estabelecimento.
4. Se quiser mostrar o papel de cozinha, saia pelo menu do usuário e entre no Mesa7 Demo com **`bruno@mesa7.demo` / `password`**. Mostre **Salão → Pedidos** e a ausência das opções de administração do catálogo.
5. Saia e volte a entrar com **`test@example.com` / `password`** para continuar o roteiro.

**O que acontece:** o mesmo usuário pode ter papéis diferentes em estabelecimentos diferentes. Proprietário e gerente administram catálogo e mesas; proprietário, gerente, atendente e cozinha podem consultar e operar pedidos. Os registros ficam restritos ao estabelecimento selecionado.

## 10. Demonstrar a administração da plataforma

Esta etapa exige que `test@example.com` tenha recebido a permissão global conforme a preparação da seção 1.

1. Com `test@example.com` autenticado no painel administrativo, clique em **Plataforma** no cabeçalho. Também é possível acessar `http://localhost:8000/plataforma/login` diretamente.
2. Na lista de **Estabelecimentos**, mostre os dois tenants, seus identificadores, a quantidade de usuários e o estado ativo.
3. Clique em **Criar estabelecimento**. Preencha um nome, por exemplo `Casa de demonstração`, e um identificador único, por exemplo `casa-apresentacao`; mantenha **Estabelecimento ativo** ligado e salve. Se o identificador já existir, escolha outro.
4. Abra a edição do novo estabelecimento e clique em **Vincular proprietário**. Informe `test@example.com` e confirme.
5. Use **Painel do estabelecimento** no cabeçalho para voltar ao painel administrativo. No seletor de estabelecimento, escolha a casa recém-criada.

**O que acontece:** o superadministrador administra os estabelecimentos globalmente. Ao vincular uma conta como proprietária, ela passa a poder selecionar e configurar aquele estabelecimento no painel comum. Um estabelecimento novo começa sem o catálogo e as mesas de demonstração.

Para demonstrar a desativação, volte a **Plataforma**, edite **apenas a casa criada para a apresentação** e desligue **Estabelecimento ativo**. Após salvar, seu cardápio e o acesso ao seu painel ficam indisponíveis. Reative-a na mesma tela se quiser continuar usando-a. Evite desativar o estabelecimento no qual a demonstração de pedidos está em andamento.

O superadministrador precisa estar vinculado a um estabelecimento ativo para acessar o painel comum; o botão de retorno aparece quando esse acesso existe. A permissão global não é concedida pela tela de usuários do estabelecimento.

## 11. Encerramento e funcionalidades de conta

Para concluir, retome o fluxo apresentado: **QR da mesa → cardápio → carrinho → pedido → operação → acompanhamento**. Mostre que a equipe trabalha no painel do seu estabelecimento e que a plataforma possui uma administração separada.

Se houver tempo, com uma conta autenticada, abra diretamente `http://localhost:8000/settings/profile` para mostrar a edição de nome e e-mail da conta e `http://localhost:8000/settings/security` para mostrar senha, autenticação em dois fatores e passkeys quando disponíveis. A tela de segurança pode solicitar confirmação de senha. Essas configurações pertencem à conta do usuário; não são a identidade visual do estabelecimento. Evite alterar credenciais ou ativar métodos de autenticação na conta compartilhada durante a apresentação.

## 12. Se algo não aparecer

- **Não consigo entrar com a senha demo:** o seeder não substitui a senha de contas já existentes. Use a senha atual ou uma base de demonstração preparada previamente.
- **O botão Plataforma não aparece:** confirme que a conta recebeu `is_super_admin = true`, saia e entre novamente se necessário, e confira se está no painel administrativo.
- **Não aparecem Usuários ou Aparência:** confirme que o usuário é proprietário do estabelecimento selecionado. `test@example.com` é gerente na Cantina da Praça.
- **A imagem não aparece:** confirme o link `public/storage` e execute `php artisan storage:link` se ainda não existir. Confira se a imagem foi salva na tela de aparência ou no produto.
- **O QR não abre no celular:** `localhost` no celular aponta para o próprio celular. A aplicação precisa estar acessível pela rede e o QR deve conter um endereço alcançável pelo aparelho.
- **Mesa ou cardápio retorna 404:** verifique se o estabelecimento e a mesa estão ativos e se a URL pertence à mesa do estabelecimento correto.
- **Produto não aparece:** confira se está disponível e se sua categoria está ativa. Uma categoria sem produtos disponíveis também não aparece.
- **Pedido não aparece ou está com status antigo:** confirme o estabelecimento, atualize a lista administrativa e aguarde até 15 segundos na tela pública. A atualização automática da tela individual para após finalização ou cancelamento.
- **Pedido não confirma:** verifique a mensagem do carrinho; um produto pode ter ficado indisponível ou ter mudado de preço desde que foi adicionado.
