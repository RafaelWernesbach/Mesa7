# Guia de Uso

Este guia descreve como administrar o catálogo, gerar QR Codes, enviar um pedido presencial pela mesa e acompanhar seu andamento. O cliente não precisa criar uma conta; delivery, retirada e pagamento online não fazem parte do MVP.

## 1. Iniciar localmente

Na raiz do repositório:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
New-Item -ItemType File -Force database\database.sqlite
php artisan migrate
npm.cmd install
npm.cmd run build
php artisan db:seed
php artisan storage:link
composer run dev
```

Se `.env` e `APP_KEY` já estiverem configurados, não é necessário recriá-los. O `storage:link` permite exibir imagens de produtos enviadas pelo painel. No PowerShell, use `npm.cmd` caso a política de execução bloqueie `npm`.

Abra `http://localhost:8000`.

## 2. Entrar no painel

Acesse `http://localhost:8000/admin/mesa7-demo` e entre com a conta demonstrativa:

| Campo | Valor |
| --- | --- |
| E-mail | `test@example.com` |
| Senha | `password` |

O usuário de demonstração também está associado ao tenant `cantina-da-praca`. Ao alternar o estabelecimento no seletor do painel, os dados e recursos ficam limitados ao tenant selecionado.

As contas e senhas do seeder são apenas para desenvolvimento local; não as utilize em produção.

## 3. Criar categorias

No menu do painel, abra **Catálogo → Categorias** e escolha **Criar categoria**.

1. Informe o nome, por exemplo `Lanches`.
2. O slug é sugerido a partir do nome quando o campo ainda está vazio. Ele pode ser editado e precisa ser único dentro do estabelecimento.
3. Opcionalmente, preencha a descrição.
4. Defina a ordem em que a categoria aparece no cardápio.
5. Deixe a categoria ativa para publicá-la no cardápio.
6. Salve.

Categorias pertencem ao estabelecimento ativo. Elas não são compartilhadas entre tenants. Uma categoria inativa ou sem produtos disponíveis não é exibida no cardápio público.

## 4. Cadastrar produtos

Abra **Catálogo → Produtos** e escolha **Criar produto**.

1. Informe o nome e o preço.
2. Selecione uma categoria do estabelecimento atual. A categoria é opcional, mas produtos sem categoria aparecem em **Outros** no cardápio.
3. Preencha a descrição, se desejar.
4. Opcionalmente, envie uma imagem. O formulário aceita imagem de até 2 MB; os arquivos são guardados no disco público dentro de `products/{id-do-estabelecimento}`.
5. Marque **Disponível para venda** para que o produto seja mostrado ao cliente.
6. Salve.

Na listagem é possível pesquisar pelo nome, conferir categoria e preço, filtrar produtos disponíveis/indisponíveis e editar registros. Produtos indisponíveis não aparecem no menu público.

## 5. Cadastrar uma mesa e abrir seu QR

No painel, abra **Salão → Mesas** e escolha **Criar mesa**.

1. Informe um nome para exibição, como `Mesa 08`.
2. Defina um código interno legível, como `M08`. O código precisa ser único dentro daquele estabelecimento.
3. Deixe **Mesa ativa** ligado e salve.
4. Na listagem, use a ação **QR Code** da mesa. Ela abre uma página HTML independente, em nova aba, com os dados da mesa, o QR e a URL pública.
5. Se quiser imprimir, use **Imprimir** na página ou o comando de impressão do navegador. A tela não abre a pré-visualização automaticamente.

O UUID usado no QR é gerado pelo sistema. Não é necessário preenchê-lo. Cada QR aponta para `/r/{slug-do-estabelecimento}/cardapio/{uuid-da-mesa}`. O endereço completo usa o domínio da aplicação; em produção, configure o domínio e HTTPS antes de gerar os materiais impressos.

Mesas inativas não exibem a ação de QR, e suas URLs públicas retornam `404`.

## 6. Testar como cliente

É possível abrir a URL pública em uma janela anônima, sem autenticação administrativa. O QR de uma mesa deve exibir:

- a marca e o nome do estabelecimento;
- o nome da mesa no cabeçalho e a indicação **Cardápio da mesa**;
- categorias ativas que tenham produtos disponíveis;
- somente produtos disponíveis, com seus preços;
- produtos sem categoria na seção **Outros**.

Como alternativa, o cardápio geral é acessível por `http://localhost:8000/r/mesa7-demo`. A rota legada `/r/{slug}/mesa/{codigo}` permanece disponível por compatibilidade; os novos QR Codes usam UUID.

### Fazer um pedido pela mesa

1. Abra o QR da mesa ou sua URL `/r/{slug}/cardapio/{uuid}`. O carrinho só é habilitado no contexto de uma mesa ativa.
2. Em cada produto, escolha a quantidade e clique em **Adicionar**. Você permanece no cardápio, e o contador do carrinho é atualizado.
3. Abra **Carrinho** para revisar os itens. Use **Atualizar** para alterar quantidades ou **Remover** para excluir um item.
4. Escreva uma observação geral, se precisar, e clique em **Confirmar pedido**.
5. A tela mostra o código público do pedido, os itens e o status atual. Guarde o link para consultar o andamento; enquanto estiver em preparo, a página atualiza a cada 15 segundos.

Ao voltar ao cardápio pela URL com UUID da mesa, a seção **Pedidos da mesa** lista os pedidos e seus status, atualizados automaticamente a cada 15 segundos enquanto a página está visível. Escolha **Acompanhar** para reabrir os detalhes. A lista inclui pedidos finalizados e cancelados e fica visível a qualquer pessoa com acesso ao QR ou à URL da mesa.

O carrinho fica salvo neste navegador, separado por estabelecimento e mesa. Adicionar, alterar e remover itens não exige acesso ao servidor; ao confirmar, o servidor valida os itens e recalcula o total. Se um preço mudar ou um produto ficar indisponível, revise o carrinho antes de tentar novamente. O MVP não coleta pagamento; o pagamento é feito diretamente no estabelecimento.

### Operar os pedidos

1. Entre no painel autenticado e selecione o estabelecimento.
2. Abra **Salão → Pedidos**. A lista contém pedidos do tenant selecionado; **Detalhes** exibe itens, observações e o histórico.
3. Use **Iniciar preparo** para aceitar um pedido pendente, **Marcar como pronto** após o preparo e **Finalizar atendimento** ao concluir.
4. **Cancelar pedido** está disponível enquanto o pedido não estiver finalizado ou cancelado.

As mudanças aparecem na página pública de acompanhamento. Cada mudança registra o status, o horário e o usuário da equipe que a realizou.

## 7. Papéis e acesso

Os usuários vinculados a um estabelecimento podem selecionar aquele tenant no painel. Produtos, categorias e mesas são gerenciados pelos papéis `owner` e `manager`. Os papéis `owner`, `manager`, `attendant` e `kitchen` podem consultar e operar pedidos. `attendant` e `kitchen` não gerenciam o catálogo.

Em **Configurações → Usuários**, o proprietário cadastra um usuário com senha inicial, vincula uma conta existente pelo e-mail, altera papéis ou desvincula pessoas. Vincular uma conta existente não altera sua senha. O estabelecimento deve manter pelo menos um proprietário. A senha inicial deve ser comunicada por um canal seguro e pode ser alterada nas configurações da conta.

Em **Configurações → Aparência**, o proprietário define o nome, a logo e as cores principal e secundária das páginas públicas e do painel administrativo do estabelecimento. Alterar o nome não muda o identificador usado nas URLs. A logo aceita PNG, JPEG e WebP de até 2 MB; para exibi-la localmente é necessário `php artisan storage:link`. Recarregue o painel após salvar para aplicar a alteração nele.

### Administração da plataforma

O painel `/plataforma` permite ao superadministrador listar, cadastrar, editar e ativar ou desativar estabelecimentos. Na edição, **Vincular proprietário** associa uma conta existente ao estabelecimento. A desativação bloqueia o cardápio e o acesso ao painel do estabelecimento.

O primeiro superadministrador deve ser definido manualmente por quem administra o banco. Após `php artisan migrate`, execute `php artisan tinker` e substitua o e-mail abaixo pelo de uma conta existente:

```php
\App\Models\User::where('email', 'admin@exemplo.com')->update(['is_super_admin' => true]);
```

Essa permissão é global e não pode ser concedida pela tela de usuários do estabelecimento. O superadministrador acessa `/plataforma/login`; para operar um estabelecimento no painel comum, precisa também estar vinculado a ele.

## 8. Problemas comuns

**Imagem enviada não aparece:** execute `php artisan storage:link` e confira se o produto está disponível.

**QR abre endereço incorreto:** confira `APP_URL`, o domínio usado para acessar o painel e a configuração de HTTPS/proxy no ambiente publicado.

**Mesa ou menu retorna `404`:** confirme que o estabelecimento e a mesa estão ativos e que o UUID pertence ao estabelecimento que aparece na URL.

**Não aparecem categorias/produtos:** confirme que a categoria está ativa, que há produtos vinculados a ela e que esses produtos estão disponíveis.
