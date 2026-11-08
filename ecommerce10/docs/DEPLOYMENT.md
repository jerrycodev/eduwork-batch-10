# Deployment Preparation

This project is a Laravel 13 application. The repository is intentionally kept hosting-provider agnostic until the deployment target is selected.

## 1. Runtime requirements

- PHP 8.3 or newer, with the PHP extensions required by Laravel 13 and the selected database driver.
- Composer 2.
- Node.js compatible with the Vite version in `package-lock.json` and npm.
- MySQL or MariaDB with a dedicated application database and user.
- A web server or managed Laravel runtime whose document root points to this repository's `public/` directory.
- HTTPS for the temporary public URL.

Confirm exact PHP extensions and runtime versions on the selected host before deployment.

## 2. Production environment

1. Create the production environment file from `.env.production.example` (copy it to `.env` on the server).
2. Replace the temporary URL, database credentials, and SMTP credentials with real values from the host.
3. Generate `APP_KEY` once on the production server if it is empty. Never regenerate it on an existing installation because encrypted data and sessions may become unreadable.
4. Keep `APP_ENV=production` and `APP_DEBUG=false`.
5. Keep `.env` outside version control and do not expose it through the web root.
6. Configure the host's TLS certificate before enabling `SESSION_SECURE_COOKIE=true` and accepting public traffic.

Do not copy the development machine's `.env` or its `public/storage` symlink to the server.

## 3. Build and install

Run from the project root on the deployment environment (or build assets in CI and publish `public/build`):

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan key:generate --force # only for a brand-new installation with an empty APP_KEY
php artisan storage:link
php artisan migrate --force
php artisan optimize
```

Do not run `key:generate --force` on a deployment that already has an APP_KEY. Back up the database before migrations. Review migration changes before applying them to an existing production database.

The `composer setup` script is for local project setup and runs migrations automatically; do not use it as a production release command.

## 4. Web process and writable paths

- Configure the web server document root to `public/`, never the repository root.
- Allow the PHP process to write to `storage/` and `bootstrap/cache/`.
- Run `php artisan storage:link` on the target host so the symlink points to that host's own `storage/app/public` directory.
- Do not make the whole repository writable by the web process.
- Ensure uploaded product images persist across releases and are included in backups.

## 5. Database, queue, scheduler, and mail

- Provision the database and least-privilege credentials before running migrations.
- The current environment template uses database-backed sessions, cache, and queues. Confirm the corresponding Laravel migrations exist and have run.
- If queued jobs are introduced, configure a persistent worker with a process supervisor and a restart strategy. If no queue jobs are dispatched, a worker is not needed yet.
- If scheduled tasks are added, configure the host scheduler to invoke `php artisan schedule:run` every minute.
- Configure a real SMTP provider and test registration, email verification, and password-reset delivery. The `log` mailer is only suitable for development.
- The default seeder no longer creates demo users or a predictable default admin password in production. To provision the first administrator, temporarily provide `ADMIN_EMAIL`, `ADMIN_PASSWORD`, and optionally `ADMIN_NAME` in the server environment, run `php artisan db:seed --class=DatabaseSeeder --force`, then remove the temporary password variable. Never use a sample/default password.

## 6. Release verification

Before exposing the temporary URL:

```bash
composer audit --no-dev
php artisan migrate:status
php artisan route:list --except-vendor
php artisan about
php artisan test
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Also verify HTTPS redirects/cookies, login and registration, product browsing, image upload and display, cart, checkout, customer order isolation, admin authorization, and email delivery in a browser on the actual host.

## 7. Backups and operations

- Back up the database and uploaded files before each release and migration.
- Keep deployment logs and application logs accessible to the operator.
- Have a rollback plan for both code and database changes; not every schema migration can be safely reversed after real orders have been created.
- Never commit production credentials, APP_KEY, database dumps, or uploaded customer data.

## 8. Current known limitations

- The Coolify/Nixpacks build configuration is prepared, but the public GitHub repository URL, branch, Coolify instance, and temporary hostname still need to be entered in Coolify.
- Browser-based end-to-end testing against a deployed instance has not yet been performed.
- Configure and verify SMTP credentials before treating transactional email as production-ready.

## 9. Coolify deployment from a public GitHub repository (Nixpacks)

The repository root contains `nixpacks.toml`. In Coolify:

1. Create a new **Public Repository** application and enter the GitHub HTTPS repository URL. Select the intended branch.
2. Set **Build Pack / Build Strategy** to `Nixpacks`, **Base Directory** to `/ecommerce10` (the Laravel project is a subdirectory of the Git repository), and **Output Type / Site Type** to a web application (not Static/SPA).
3. Set **Ports Exposes** / internal port to `80`. Nixpacks' PHP provider runs Nginx + PHP-FPM; do not set the app port to Laravel's development server port `8000`.
4. Add the production environment variables from `.env.production.example` in Coolify. Generate a unique `APP_KEY` once (`php artisan key:generate --show` locally or `php artisan key:generate` in a secure one-off server shell) and set the resulting value as `APP_KEY` in Coolify. Never commit it.
5. Create a MySQL resource in Coolify (or use an external MySQL server) and set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to the connection details Coolify provides. Do not use `127.0.0.1` unless MySQL is in the same container (it should normally be a separate resource).
6. Use a persistent volume for uploaded public files, mounted at `/app/storage/app/public`. `nixpacks.toml` creates the `public/storage` symlink during build. Do not mount over `/app` or `/app/public`.
7. Set `APP_URL` to the temporary Coolify domain (including `https://`), keep `APP_ENV=production`, `APP_DEBUG=false`, and `SESSION_SECURE_COOKIE=true` once HTTPS is active. Configure SMTP values before testing registration, verification, or password reset.
8. Configure the domain in Coolify, enable its HTTPS certificate, deploy, and inspect build/runtime logs.
9. After the first deploy, run `php artisan migrate --force` once using Coolify's terminal/execute-command feature. Back up the database before every later migration. Do not run `db:seed` without explicitly provisioning the initial admin via `ADMIN_EMAIL` and `ADMIN_PASSWORD`.

`nixpacks.toml` installs Composer production dependencies and Node dependencies, builds Vite assets, and configures Laravel's `public/` document root. It intentionally does not run migrations or generate an application key during image build.
