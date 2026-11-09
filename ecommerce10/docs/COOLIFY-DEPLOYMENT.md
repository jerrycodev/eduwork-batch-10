# Deploy Ecommerce10 to Coolify

## Coolify application settings

- Build/deployment type: Docker Compose
- Compose file: `docker-compose.yml`
- Base directory: repository root (`/`)
- Public service: `web`
- Public port: `80`
- Do not expose `app`, `worker`, or `scheduler` publicly.
- The app image uses PHP 8.4 FPM; Nginx serves the production Vite assets.

## Required environment variables

Set these in the Coolify application's Environment Variables. Do not commit a real `.env` file or put secrets in the Dockerfile.

| Variable | Value |
| --- | --- |
| `APP_KEY` | Existing Laravel key, or generate a new one with `php artisan key:generate --show` |
| `APP_URL` | Public HTTPS domain assigned to the `web` service |
| `DB_CONNECTION` | `mysql` (fixed in Compose) |
| `DB_HOST` | Reachable MySQL hostname/IP; **not `localhost` or `127.0.0.1`** from inside the container |
| `DB_PORT` | `3306` unless the database provider specifies otherwise |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` | Database user |
| `DB_PASSWORD` | Database password |

Recommended optional values:

- `SESSION_DRIVER=file` (the repository has no sessions-table migration)
- `CACHE_STORE=database` (the default cache-table migration exists)
- `QUEUE_CONNECTION=database` (the default jobs-table migration exists)
- `SESSION_SECURE_COOKIE=true`
- `SESSION_SAME_SITE=lax`

`worker` processes database-backed jobs and `scheduler` runs Laravel scheduled tasks. Both use the same app image and persistent storage volume.

## First deployment

1. Make sure the MySQL database exists and is reachable from the Coolify Docker network.
2. Set the required environment variables above in Coolify.
3. Assign the domain to service `web` on port `80` and enable HTTPS in Coolify.
4. Deploy the Compose application.
5. After `app` is healthy, run migrations once from the Coolify terminal/exec console in the `app` service:

   ```sh
   php artisan migrate --force
   ```

6. Verify the public URL and confirm `web`, `app`, `worker`, and `scheduler` are running.

Do not run `migrate:fresh` or remove the `laravel_storage` volume during deployment. The named volume stores uploaded files under `storage/app/public`; back it up independently of the database.

## Important notes

- The local `.env` currently uses a localhost database host. That value will not work from a container; set `DB_HOST` to the actual database endpoint in Coolify.
- `bootstrap/cache/config.php` and other generated Laravel cache files are excluded from the Docker build context to prevent local environment values from being baked into production images.
- The `web` service serves `/up` for its health check. The `app` health check verifies that PHP-FPM listens on port `9000`.
