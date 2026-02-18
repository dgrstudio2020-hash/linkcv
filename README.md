# LinkCV

Aplicação web para criar e gerenciar currículos (CV) com edição em tempo real e exportação para PDF.

## Funcionalidades

- Autenticação e área logada (Laravel Breeze)
- CRUD de currículos (rotas em PT-BR, ex.: `/curriculos`)
- Edição do currículo com seções como experiências, formação, idiomas, habilidades e informações adicionais
- Upload de foto (armazenada em `storage/app/public/resume-photos/...`)
- Templates de layout do currículo (ex.: `modern`, `minimal`, `classic`, etc.)
- Front-end com Inertia + Vue 3

## Stack

- PHP 8.2+
- Laravel 12
- Inertia.js
- Vue 3
- Tailwind CSS + Vite
- SQLite (padrão) ou MySQL/PostgreSQL

Dependências usadas no front para recursos do editor:

- `cropperjs` (recorte/ajuste de imagem)
- `html2pdf.js` (exportação para PDF)

## Rodando localmente

Pré-requisitos:

- PHP 8.2+
- Composer
- Node.js + npm

### Setup rápido (1 comando)

O projeto já tem scripts para facilitar:

```bash
composer run setup
```

Isso executa (em sequência): `composer install`, cria `.env`, gera APP_KEY, roda migrations, instala dependências do Node e builda o front.

### Setup manual

```bash
composer install
copy .env.example .env
php artisan key:generate

# SQLite (padrão)
type NUL > database/database.sqlite

php artisan migrate
npm install
```

### Subir a aplicação

Modo dev completo (Laravel + Vite + logs/queue, tudo junto):

```bash
composer run dev
```

Alternativa (rodando separado):

```bash
php artisan serve
npm run dev
```

Depois acesse:

- http://127.0.0.1:8000

## Testes

```bash
composer run test
```

## Notas de portfólio

- O repositório não inclui `.env`, `vendor/`, `node_modules/` e arquivos gerados (ver `.gitignore`).
- Para fotos enviadas no currículo, use `php artisan storage:link` se precisar do link simbólico em ambiente local.

## Licença

MIT
