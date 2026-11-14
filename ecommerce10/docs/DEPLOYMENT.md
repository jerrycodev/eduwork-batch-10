# Development and Production Deployment

This guide covers a native Ubuntu/Linux deployment using **Nginx + PHP-FPM**. Docker, Docker Compose, Coolify, and Nixpacks are not required.

The application is built with Laravel 13, PHP 8.3+, Composer, Vite, and MySQL/MariaDB.

## 1. Requirements

### Development machine

- Git
- PHP 8.3 or newer and the PHP extensions listed below
- Composer 2
- Node.js and npm (use the versions supported by the lockfile/Vite)
- MySQL 8+ or a compatible MariaDB server

Required PHP extensions for a typical Laravel + MySQL installation:

- ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl
- pdo, pdo_mysql, session, tokenizer, xml
- bcmath, gd, intl, zip

Check your local runtime:

```bash
php -v
composer --version
node --version
npm --version
php -m
```

### Production server

- Ubuntu 22.04/24.04 (or another supported Linux distribution)
- Nginx
- PHP-FPM 8.3 or newer with the required extensions
- Composer 2
- Node.js/npm for building frontend assets (or build assets in CI)
- MySQL/MariaDB
- A DNS name pointing to the server and a TLS certificate

Keep PHP CLI and PHP-FPM on compatible PHP versions. The Nginx example below uses the PHP 8.3 FPM socket; change it if the installed PHP version differs.

## 2. Development setup from Git clone

### 2.1 Clone the repository

Replace the URL and branch with the actual repository values:

```bash
git clone https://github.com/jerrycodev/eduwork-batch-10
cd eduwork-batch-10/ecommerce10
```

### 2.2 Install dependencies

```bash
composer install
npm ci
```

### 2.3 Configure the environment

```bash
cp .env.example .env
```

Edit `.env` and configure at least:

```dotenv
APP_NAME="Ecommerce"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce10
DB_USERNAME=root
DB_PASSWORD=your-local-database-password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
SESSION_SECURE_COOKIE=false
```

Generate the local application key:

```bash
php artisan key:generate
```

Never commit `.env`. Use a different key and credentials for every environment.

#### Transactional order emails and invoice PDFs

The application queues customer emails for order creation, verified payment, order processing, shipment (including courier and tracking number), and delivery. The order-created email includes an invoice PDF; the verified-payment email includes a payment receipt PDF. Customers can also download the current invoice/receipt from their authenticated order detail page. The invoice endpoint checks order ownership (administrators may access it for support).

Emails are queued after the related database transaction commits. Keep a Laravel queue worker running in every environment; otherwise messages remain pending in the `jobs` table and are not delivered:

```bash
php artisan queue:work --tries=3 --backoff=10
```

The default queue connection is `database`. For production, supervise the queue worker with Supervisor, systemd, or the process manager used by your hosting platform. Configure a real SMTP provider using the `MAIL_*` variables below. Do not use Mailpit in production.

#### Local email testing with Mailpit

Mailpit captures outgoing application emails locally; it does not deliver them to real inboxes. Start a Mailpit container if one is not already available:

```bash
docker run -d --name ecommerce-mailpit \
  -p 1025:1025 \
  -p 8025:8025 \
  axllent/mailpit:latest
```

For Laravel running directly on the host, use these settings (they are already the defaults in `.env.example`):

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="noreply@ecommerce.test"
MAIL_FROM_NAME="${APP_NAME}"
```

Open the Mailpit inbox at `http://127.0.0.1:8025`. If Laravel itself runs inside Docker on the same Docker network as Mailpit, set `MAIL_HOST` to the Mailpit service/container network alias (for example, `mailpit`) instead of `127.0.0.1`. After changing mail settings, clear cached configuration:

```bash
php artisan config:clear
```

For production, replace these local Mailpit values with the credentials and host of a real SMTP provider; see the production environment example in section 4.2.

### 2.4 Run migrations and development seeders

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

The default seeder behaves differently by environment:

- In `local` and `testing`, it creates two stable demo users (`demo1@ecommerce.test` and `demo2@ecommerce.test`) only when they do not already exist, plus the sample product catalog (five categories and 16 products).
- Sample categories and products are created only when missing. Re-running the seeder does not reset existing product stock, images, prices, or descriptions.
- In production, sample users and sample catalog data are skipped.
- An administrator is created/promoted only when both `ADMIN_EMAIL` and `ADMIN_PASSWORD` are supplied. An existing administrator's password is not reset by re-running the seeder.

To provision a local administrator, add these values to your local `.env` before running `php artisan db:seed`:

```dotenv
ADMIN_NAME="Administrator"
ADMIN_EMAIL=admin@example.test
ADMIN_PASSWORD=use-a-unique-local-password
```

Remove the temporary password from `.env` after the administrator has been created.

### 2.5 Build assets and run the application

For normal frontend development, run Vite in one terminal:

```bash
npm run dev
```

Run Laravel in another terminal:

```bash
php artisan serve
```

Open http://127.0.0.1:8000.

To create a production-style frontend build locally:

```bash
npm run build
```

### 2.6 Run tests

```bash
php artisan test
```

Run the test suite before pushing changes. Tests should use the isolated testing database configured in `phpunit.xml`, not a production database.

## 3. Prepare the Ubuntu production server

The commands below assume a deployment user named `deploy`, an application path of `/var/www/ecommerce10`, and a domain such as `shop.example.com`. Replace these placeholders with your actual values.

Install the server packages (package names can vary by Ubuntu release and PHP repository):

```bash
sudo apt update
sudo apt install -y nginx mysql-server git unzip curl acl
sudo apt install -y php8.3-fpm php8.3-cli php8.3-common \
  php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-bcmath php8.3-gd php8.3-intl php8.3-zip
```

Install Composer 2 and a supported Node.js/npm release using their official installation instructions. Verify the versions:

```bash
php -v
php-fpm8.3 -v
composer --version
node --version
npm --version
nginx -v
```

Create a deployment user if one does not already exist:

```bash
sudo adduser --disabled-password --gecos "" deploy
sudo usermod -aG www-data deploy
```

Provision a dedicated MySQL database and least-privilege application user. Do not use the MySQL root account in the Laravel `.env` file:

```sql
CREATE DATABASE ecommerce10 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ecommerce10'@'localhost' IDENTIFIED BY 'replace-with-a-strong-production-password';
GRANT ALL PRIVILEGES ON ecommerce10.* TO 'ecommerce10'@'localhost';
FLUSH PRIVILEGES;
```

## 4. Deploy the application

### 4.1 Clone the repository

Run these commands from your sudo-capable administrator account. The clone itself runs as the deployment user:

```bash
sudo mkdir -p /var/www
sudo chown deploy:www-data /var/www
sudo -u deploy -H git clone <repository-url> /var/www/ecommerce10
sudo -iu deploy
cd /var/www/ecommerce10
```

For subsequent releases, fetch and update the intended branch instead of cloning again:

```bash
git fetch origin
git checkout <production-branch>
git pull --ff-only origin <production-branch>
```

Do not run `git reset --hard` on a working deployment directory. Keep server configuration and uploaded files out of Git.

### 4.2 Configure production environment

Create the environment file and edit it on the server:

```bash
cp .env.example .env
nano .env
```

Set production values (replace every example value):

```dotenv
APP_NAME="Ecommerce"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://shop.example.com
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce10
DB_USERNAME=ecommerce10
DB_PASSWORD=replace-with-a-strong-production-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

Generate `APP_KEY` once on the server:

```bash
php artisan key:generate
```

Keep the generated key for every future release. **Never regenerate the key on an existing installation.** Never commit or expose `.env`, database credentials, SMTP credentials, or production keys.

### 4.3 Install dependencies and build frontend assets

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
```

If Node.js is not installed on the production host, build `public/build` in a trusted CI/build environment using the lockfile and deploy the resulting assets with the release.

### 4.4 Run migrations and create storage link

Back up the database before applying migrations, especially on an existing installation:

```bash
php artisan migrate --force
php artisan storage:link
```

Do not run `migrate:fresh` or `db:wipe` on production. Do not run the sample product seeder in production.

To create the first administrator, temporarily set `ADMIN_NAME`, `ADMIN_EMAIL`, and a strong unique `ADMIN_PASSWORD` in the server environment (or export them for the one command), then run:

```bash
ADMIN_NAME="Administrator" \
ADMIN_EMAIL="admin@your-real-domain.com" \
ADMIN_PASSWORD="replace-with-a-unique-strong-password" \
php artisan db:seed --class=DatabaseSeeder --force
```

Remove the temporary password from the shell environment or server configuration immediately after provisioning. Re-running this command will not reset an existing administrator's password.

### 4.5 Set file permissions

Before continuing, leave the deployment-user shell with `exit` and return to your sudo-capable administrator account.

Nginx/PHP-FPM must be able to read the application, while only Laravel's runtime directories need to be writable:

```bash
sudo chown -R deploy:www-data /var/www/ecommerce10
sudo find /var/www/ecommerce10 -type d -exec chmod 755 {} \;
sudo find /var/www/ecommerce10 -type f -exec chmod 644 {} \;
sudo find /var/www/ecommerce10/storage /var/www/ecommerce10/bootstrap/cache -type d -exec chmod 2775 {} \;
sudo find /var/www/ecommerce10/storage /var/www/ecommerce10/bootstrap/cache -type f -exec chmod 664 {} \;
```

Do not make the entire project writable by the web server. Keep `.env` readable only by the deployment user and the required privileged processes; for example:

```bash
chmod 640 /var/www/ecommerce10/.env
```

Ensure uploaded files under `storage/app/public` are persistent and included in backups.

### 4.6 Optimize Laravel for production

Run these commands from your sudo-capable administrator account; Artisan runs as the deployment user:

```bash
sudo -u deploy -H bash -lc 'cd /var/www/ecommerce10 && php artisan optimize'
```

When changing environment values or configuration during a release, rebuild the configuration cache:

```bash
sudo -u deploy -H bash -lc 'cd /var/www/ecommerce10 && php artisan optimize:clear && php artisan optimize'
```

## 5. Configure Nginx

Create an Nginx server block:

```bash
sudo nano /etc/nginx/sites-available/ecommerce10
```

Use the following configuration. Replace the domain and confirm that the PHP-FPM socket matches the installed PHP version:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name shop.example.com;

    root /var/www/ecommerce10/public;
    index index.php;

    charset utf-8;
    client_max_body_size 20M;

    add_header X-Content-Type-Options nosniff always;
    add_header X-Frame-Options SAMEORIGIN always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* ^/(?:\.env|composer\.(?:json|lock)|package(?:-lock)?\.json|artisan) {
        deny all;
    }
}
```

Enable the site and validate the configuration:

```bash
sudo ln -s /etc/nginx/sites-available/ecommerce10 /etc/nginx/sites-enabled/ecommerce10
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl enable --now nginx php8.3-fpm
```

If the default Nginx site conflicts with this domain, disable it after confirming it is not serving another application:

```bash
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

**Important:** The Nginx document root must be `/var/www/ecommerce10/public`, never the repository root. Do not expose `.env`, `vendor/`, `storage/`, or other application internals over HTTP.

## 6. Enable HTTPS

Point the domain's DNS A/AAAA records to the server and make sure ports 80 and 443 are reachable. Then install Certbot using the official instructions for your Ubuntu release and issue the certificate:

```bash
sudo certbot --nginx -d shop.example.com
sudo certbot renew --dry-run
```

After HTTPS is active, confirm `APP_URL=https://shop.example.com` and `SESSION_SECURE_COOKIE=true`. Keep `APP_DEBUG=false` in production.

## 7. Queue, scheduler, and storage operations

- The current environment template uses database-backed sessions, cache, and queues. Confirm the standard Laravel database migrations have run.
- If the application dispatches queued jobs, run a persistent worker under Supervisor or systemd and restart it after deployments with `php artisan queue:restart`. If no jobs are dispatched, a worker is not needed yet.
- If scheduled tasks are introduced, add a cron entry to run `php artisan schedule:run` every minute as the deployment user.
- Keep `storage/` and `bootstrap/cache/` writable by PHP-FPM.
- Keep uploaded files persistent between releases. Back up `storage/app/public` and the database.
- Configure SMTP and test registration, verification, password-reset, and transactional order emails.
- Verify queue workers deliver order invoices, payment receipts, and fulfillment status emails.

## 8. Release checklist

For each release:

1. Put the application into maintenance mode if the release requires downtime.
2. Back up the database and uploaded files.
3. Fetch the intended Git branch and install locked dependencies.
4. Build frontend assets.
5. Run reviewed database migrations with `php artisan migrate --force`.
6. Run `php artisan optimize`.
7. Restart queue workers if configured.
8. Reload Nginx only when its configuration changes.
9. Verify the site and exit maintenance mode.

Useful verification commands:

```bash
composer audit --no-dev
php artisan migrate:status
php artisan route:list --except-vendor
php artisan about
php artisan test
sudo nginx -t
sudo systemctl status nginx php8.3-fpm --no-pager
```

Verify in a browser:

- HTTPS, redirects, and secure cookies
- Login, registration, and administrator authorization
- Product listing, product details, and category filtering
- Product image upload and display
- Cart and checkout
- Customer order isolation and admin order management
- Email delivery

## 9. Backups, security, and troubleshooting

- Back up the database and uploaded files before each release and migration.
- Never commit `.env`, `APP_KEY`, production credentials, database dumps, or customer uploads.
- Do not use demo credentials in production. Use a unique administrator password and rotate it through the application's supported account flow.
- Do not run `php artisan db:seed` in production unless you intend to provision the administrator with the explicit environment variables described above. Development sample users and products are guarded by the application environment.
- Do not use `migrate:fresh`, `db:wipe`, or `key:generate` on an existing production installation.
- Inspect Laravel logs in `storage/logs/`, Nginx logs in `/var/log/nginx/`, and PHP-FPM logs when diagnosing errors.
- A 502 response usually indicates PHP-FPM is stopped, the configured socket is incorrect, or Nginx cannot access it.
- A 500 response should be investigated in Laravel logs; do not enable `APP_DEBUG` on a public production server.
- A 404 for uploaded images often means the storage symlink is missing or the uploaded files were not persisted.

## 10. Deployment assumptions and project-specific notes

- This project is a Laravel application, not a static website. Nginx serves files from `public/` and forwards PHP requests to PHP-FPM.
- The sample product catalog is development/test data. Create real production catalog records through the application/admin workflow.
- Browser-based end-to-end testing on the actual production domain must be performed after deployment.
- Confirm PHP-FPM's installed version and socket path before enabling the Nginx site.


## Realtime order updates (Laravel Reverb)

Order detail pages subscribe to a private `orders.{orderId}` channel. When an administrator changes fulfillment status or tracking number, Laravel broadcasts `order.status.updated` after the database transaction commits. Only the order owner and administrators can authorize that private channel.

For local development, configure the following in `.env` (the values below are local-only examples):

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=local-app-id
REVERB_APP_KEY=local-app-key
REVERB_APP_SECRET=local-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_ALLOWED_ORIGINS=localhost,127.0.0.1
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
QUEUE_CONNECTION=database
```

Run the WebSocket server and queue worker alongside the Laravel application:

```bash
php artisan reverb:start
php artisan queue:work
npm run dev
```

Build frontend assets for production with `npm run build`. In production, use a process supervisor for Reverb and the queue worker, configure a TLS-enabled public WebSocket endpoint, set the matching `REVERB_*` and `VITE_REVERB_*` values, and restrict Reverb allowed origins to the storefront domain rather than `*`. Restart long-running Laravel, queue, and Reverb processes after changing environment values.

If Reverb or the queue worker is unavailable, the order page remains usable but will not receive live updates until the connection is restored; refreshing the page still loads the latest persisted order state.


## Realtime and in-app notifications

Realtime connections are opt-in. The application stores order notifications in the database even when realtime is disabled, so users can read them the next time they open the application. The default `.env` settings do not open WebSocket connections or broadcast order updates.

```dotenv
BROADCAST_CONNECTION=null
REALTIME_ENABLED=false
REALTIME_ORDER_STATUS_ENABLED=false
REALTIME_NOTIFICATIONS_ENABLED=false
```

To enable Reverb, configure its credentials and set `BROADCAST_CONNECTION=reverb`. Then enable only the realtime features you need:

```dotenv
BROADCAST_CONNECTION=reverb
REALTIME_ENABLED=true
REALTIME_ORDER_STATUS_ENABLED=true
REALTIME_NOTIFICATIONS_ENABLED=true
```

`REALTIME_ENABLED` is the master switch. `REALTIME_ORDER_STATUS_ENABLED` controls live order-detail updates, while `REALTIME_NOTIFICATIONS_ENABLED` controls live notification delivery to the authenticated user's private channel. Each feature flag is independently opt-in and requires the master switch. Database notifications remain enabled regardless of these flags.

Run the Reverb server and queue worker only when realtime delivery is enabled. After changing `.env`, clear cached configuration and restart long-running Laravel, queue, Vite, and Reverb processes so they load the new values:

```bash
php artisan config:clear
php artisan queue:restart
php artisan reverb:start
```

Payment notifications are created only when a verified Midtrans callback transitions an order into the paid state. Repeated callbacks do not create duplicate payment-success or admin new-order notifications. Administrators receive a notification for each newly paid order; customers receive payment confirmation and subsequent fulfillment status/tracking updates.
