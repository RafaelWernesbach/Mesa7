# Guia de Uso

Este guia descreve como administrar o catálogo e como testar a visualização pública do cardápio no contexto de uma mesa. O cardápio público é somente para consulta: pedidos, carrinho e checkout ainda não fazem parte deste fluxo.

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
4. Na listagem, use a ação **QR Code** da mesa. Ela abre uma página com o QR e a URL pública.
5. Use **Imprimir QR Code** para imprimir a página ou copie/abra a URL para testar.

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

## 7. Papéis e acesso

Os usuários vinculados a um estabelecimento podem selecionar aquele tenant no painel. As telas de gerenciamento de produtos, categorias e mesas são autorizadas para os papéis `owner` e `manager`; o papel `kitchen` não gerencia esses recursos.

## 8. Problemas comuns

**Imagem enviada não aparece:** execute `php artisan storage:link` e confira se o produto está disponível.

**QR abre endereço incorreto:** confira `APP_URL`, o domínio usado para acessar o painel e a configuração de HTTPS/proxy no ambiente publicado.

**Mesa ou menu retorna `404`:** confirme que o estabelecimento e a mesa estão ativos e que o UUID pertence ao estabelecimento que aparece na URL.

**Não aparecem categorias/produtos:** confirme que a categoria está ativa, que há produtos vinculados a ela e que esses produtos estão disponíveis.
