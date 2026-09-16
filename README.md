# PiShock Interface

A self-hosted control panel for [PiShock](https://pishock.com) devices, built with Laravel. One owner manages devices and safety limits, then hands out revocable named links so other people can operate those devices remotely without needing an account.

- **Owner** — logs in, adds devices (by their PiShock share code), sets max duration/intensity per operation, and creates/revokes operator links.
- **Operators** — visit their personal link (`/pishock/{token}`), no login required, and can send shock/vibrate/beep commands within the owner's configured limits. Every command is logged with who sent it.

This app supports exactly one owner account. Public self-registration is intentionally disabled.

## Requirements

- Docker and Docker Compose (recommended — see below; nothing else needs to be installed), **or**
- PHP 8.2+ with the `pdo_mysql`, `mbstring`, `bcmath`, and `zip` extensions (add `pdo_sqlite` too if you want to run the test suite), Composer, Node.js/npm, and a MySQL/MariaDB database if running natively

## Setup (Docker)

1. Copy the environment file and fill in your PiShock credentials:

   ```bash
   cp .env.example .env
   ```

   Set `DB_HOST=db` (to match the `db` service below), and `PISHOCK_USERNAME` / `PISHOCK_API_KEY` to the credentials from your [PiShock account](https://pishock.com/#/account).

2. Start everything:

   ```bash
   docker compose up -d --build
   ```

   This builds the PHP image, installs Composer dependencies, generates an `APP_KEY`, runs migrations, and builds the frontend assets (`npm ci && npm run build`, in its own `node` container — no Node needed on the host). The app is served at [http://localhost:8000](http://localhost:8000).

3. Create your owner account — this is the only way to get a login, since registration is disabled:

   ```bash
   docker compose exec app php artisan owner:create "Your Name" "you@example.com" "a-strong-password"
   ```

   Running it again once an owner exists will refuse — there can only be one.

4. Log in at `/login`, add your device(s) under **Device management** (device name + PiShock share code), and optionally set max duration/intensity per operation from the control panel.

5. Create an operator link under **Operator links** for each person you want to be able to operate your devices, and share that link with them. Revoke it any time to cut off access.

If you change frontend code later, rebuild assets with `docker compose up -d --build app` (it re-runs the `node` build before starting).

## Setup (without Docker)

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
# configure DB_* and PISHOCK_* in .env, then:
php artisan migrate
php artisan owner:create "Your Name" "you@example.com" "a-strong-password"
php artisan serve
```

Forgot your password? There's no email-based reset flow (this app never needs mail configured) — run:

```bash
docker compose exec app php artisan owner:reset-password
# or, without Docker:
php artisan owner:reset-password
```

## Deploying to production

The Docker setup above (`docker-compose.yml`) is for local development: it runs `php artisan serve`, Laravel's single-threaded dev server, and bind-mounts your working directory. For a real deployment, use `docker-compose.prod.yml` instead, which:

- Builds a self-contained image (`Dockerfile.prod`) with your code and built assets baked in, rather than bind-mounted
- Runs php-fpm behind nginx instead of `artisan serve`
- Runs `migrate`, `config:cache`, `route:cache`, and `view:cache` on startup

This app doesn't handle TLS itself — it's meant to sit behind a reverse proxy (nginx, Caddy, Cloudflare Tunnel, whatever you already run) that terminates HTTPS and forwards to it. It trusts that proxy's forwarded headers unconditionally (`bootstrap/app.php`), since it's never meant to be reachable except through one.

1. Set these in your `.env` (in addition to the usual `DB_*`/`PISHOCK_*` values):

   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.example
   SESSION_SECURE_COOKIE=true
   DB_PASSWORD=some-strong-password
   ```

   `APP_DEBUG=false` matters most: left `true`, error pages leak stack traces and config values to anyone who can trigger one.

2. Build and start it:

   ```bash
   docker compose -f docker-compose.prod.yml up -d --build
   ```

   It listens on `127.0.0.1:8080` by default (see `docker-compose.prod.yml`) — point your reverse proxy at that.

3. Create the owner account the same way as in dev:

   ```bash
   docker compose -f docker-compose.prod.yml exec app php artisan owner:create "Your Name" "you@example.com" "a-strong-password"
   ```

If you change code after the first deploy, rebuild: `docker compose -f docker-compose.prod.yml up -d --build`.

## Running tests

```bash
docker compose exec app php artisan test
# or, without Docker:
php artisan test
```

## Notes

- Max duration/intensity limits set by the owner are enforced server-side — an operator can't exceed them by bypassing the UI.
- `OperationHistory` records every command sent, attributed to either the owner or the named operator who sent it.
- Sending commands is rate limited per owner/operator (`PISHOCK_COMMANDS_PER_MINUTE` in `.env`, default 20/minute) so no single person can spam commands. Change it if 20/minute is too low or too high for how you use it, and restart the app for the change to take effect.
