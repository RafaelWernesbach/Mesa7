# Mesa 7

Aplicacao Laravel com Livewire, Fortify, Flux, Tailwind CSS e Vite. O projeto usa SQLite por padrao para facilitar a execucao local.

## Requisitos

- PHP `^8.3` com extensoes comuns do Laravel habilitadas: `pdo`, `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `ctype`, `fileinfo`, `tokenizer`, `xml`, `curl` e `zip`.
- Composer 2.x.
- Node.js compativel com Vite 8: `^20.19.0` ou `>=22.12.0`.
- npm compativel com a versao do Node instalada.
- Git.

Versoes:

- PHP `8.4.25`
- Composer `2.10.3`
- Node.js `26.8.1`
- npm `11.19.0`

## Tecnologias principais

- Laravel `13`
- Livewire `4`
- Laravel Fortify para autenticacao
- Livewire Flux para componentes de interface
- Tailwind CSS `4`
- Vite `8`
- Pest para testes
- Larastan/PHPStan para analise estatica
- Laravel Pint para formatacao PHP

## Instalacao dos requisitos

### Windows

1. Instale o PHP 8.3 ou superior. Opcoes comuns: Laragon, XAMPP ou instalacao manual do PHP.
2. Habilite as extensoes `pdo_sqlite` e `sqlite3` no `php.ini`.
3. Instale o Composer 2.x.
4. Instale o Node.js em uma versao suportada pelo Vite 8.
5. Abra um novo PowerShell e confirme:

```powershell
php -v
composer --version
node -v
npm.cmd -v
```

> No PowerShell, se `npm` for bloqueado por politica de execucao de scripts, use `npm.cmd` nos comandos.

### macOS ou Linux

1. Instale PHP 8.3 ou superior com SQLite habilitado.
2. Instale Composer 2.x.
3. Instale Node.js em uma versao suportada pelo Vite 8.
4. Confirme:

```bash
php -v
composer --version
node -v
npm -v
```

## Instalacao do projeto

Entre na pasta do projeto:

```powershell
cd C:\dev\Faculdade\LABENG\mesa7
```

Em macOS/Linux, use o caminho correspondente:

```bash
cd mesa7
```

### Opcao rapida

Crie o arquivo do banco SQLite e rode o script de setup do Composer:

```powershell
New-Item -ItemType File -Force database\database.sqlite
composer run setup
```

Em macOS/Linux:

```bash
touch database/database.sqlite
composer run setup
```

O comando `composer run setup` executa a instalacao PHP, cria o `.env` se ele ainda nao existir, gera a chave da aplicacao, roda as migrations, instala dependencias JavaScript e gera o build dos assets.

### Opcao manual

Use estes passos se quiser controlar cada etapa ou se o setup automatico falhar.

PowerShell:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
New-Item -ItemType File -Force database\database.sqlite
php artisan migrate
npm.cmd install
npm.cmd run build
```

macOS/Linux:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
```

## Configuracao local

O arquivo `.env.example` ja vem preparado para ambiente local:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
```

Para usar outro banco, altere `DB_CONNECTION` e preencha as variaveis correspondentes no `.env`.

## Inicializando a aplicacao localmente

Na raiz do projeto, rode:

```powershell
composer run dev
```

Esse comando chama `php artisan dev`, que inicia o ambiente de desenvolvimento do Laravel. Depois, acesse:

```text
http://localhost:8000
```

Se preferir iniciar os processos separadamente, use dois terminais:

Terminal 1:

```powershell
php artisan serve
```

Terminal 2:

```powershell
npm.cmd run dev
```

Em macOS/Linux, o segundo terminal pode usar:

```bash
npm run dev
```

## Rotas principais

- `/` - pagina inicial
- `/register` - cadastro
- `/login` - login
- `/dashboard` - painel autenticado
- `/settings/profile` - perfil do usuario autenticado
- `/settings/security` - seguranca e passkeys
- `/settings/appearance` - aparencia

## Painel administrativo de produtos

Crie os dados locais de demonstração e habilite o acesso público às imagens enviadas:

```powershell
php artisan db:seed
php artisan storage:link
```

Entre em `http://localhost:8000/admin/mesa7-demo` com:

- E-mail: `test@example.com`
- Senha: `password`

No painel, abra **Produtos** para cadastrar, editar, filtrar por disponibilidade ou excluir produtos. Essas credenciais são somente para desenvolvimento local; não as use em produção.

## Comandos uteis

```powershell
php artisan migrate
php artisan route:list
php artisan optimize:clear
composer run lint
composer run types:check
composer run test
npm.cmd run build
```

Em macOS/Linux, troque `npm.cmd` por `npm`.

## Testes e qualidade

Rode a suite principal:

```powershell
composer run test
```

Esse script executa:

- limpeza de configuracao do Laravel
- verificacao de estilo com Pint
- analise estatica com PHPStan/Larastan
- testes automatizados com Pest

## Solucao de problemas

### Erro: arquivo SQLite nao existe

Crie o arquivo e rode as migrations novamente:

```powershell
New-Item -ItemType File -Force database\database.sqlite
php artisan migrate
```

macOS/Linux:

```bash
touch database/database.sqlite
php artisan migrate
```

### Erro: APP_KEY ausente

Gere uma nova chave:

```powershell
php artisan key:generate
```

### Erro relacionado ao Vite manifest

Gere os assets ou inicie o Vite em modo desenvolvimento:

```powershell
npm.cmd run build
```

ou:

```powershell
npm.cmd run dev
```

### PowerShell bloqueando npm

Use `npm.cmd`:

```powershell
npm.cmd install
npm.cmd run dev
```

### Porta 8000 ocupada

Suba o servidor Laravel em outra porta:

```powershell
php artisan serve --port=8001
```

Depois ajuste `APP_URL` no `.env` se necessario:

```dotenv
APP_URL=http://localhost:8001
```
