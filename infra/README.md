# infra — imprint.jerome-arfouche.ca

Production runs on the shared DigitalOcean droplet described in
`~/Code/util/infra/` (OS, nginx globals, backups, restore). This directory is
everything specific to this app. Facts as of 2026-10-23.

## Where it lives

| | |
|---|---|
| Domain | `imprint.jerome-arfouche.ca` (`www.` → apex, port 80 → https) |
| Stack | Laravel 13 on PHP 8.5-FPM (`/run/php/php8.5-fpm.sock`), Vite assets built on the server (pnpm), PostgreSQL |
| Site root | `/home/forge/imprint.jerome-arfouche.ca/current/` → `releases/<id>/`, 4 releases kept (Forge setting); web root is `current/public` |
| Forge site id | `3265168` |
| Queue | `database` driver, one worker under supervisor (`worker-1061143`). Sessions and cache are also `database` |
| Scheduler | none — no `Schedule::` entries in `routes/console.php` and no crontab on the box |
| TLS | Let's Encrypt via Forge, renewed Sun 05:32 (`/root/.letsencrypt-renew/3563259`) |
| Runs as | `forge` |

## Files here

| File | Purpose | Applied how |
|---|---|---|
| `forge_deploy.sh` | The deploy script — identical to the one in Forge's *Deploy Script* box | Keep the two in step by hand (it isn't on the server, so `snapshot.sh` can't check it) |
| `nginx.conf` | The vhost (`/etc/nginx/sites-available/imprint.jerome-arfouche.ca`) | By hand; `./snapshot.sh` flags drift |
| `supervisord.conf` | The queue worker (`/etc/supervisor/conf.d/worker-1061143.conf`) | Created by Forge's *Queue* page; `./snapshot.sh` flags drift |
| `postgres.sql` | One-time role/database grants for `imprintdb`/`imprintuser` (run as `postgres`) | By hand |
| `forge-conf/` | Forge-generated includes, read-only reference. `site.conf` holds the PHP location (`fastcgi_pass` + `forge_fastcgi_defaults`), headers, error log and the `.`-file deny rule | Refreshed by `./snapshot.sh` |
| `env.example` | Names of the variables in the server-side `.env` | — |
| `manifest`, `snapshot.sh` | What to compare with the server, and the script | `./snapshot.sh` |

## Server layout

```
/home/forge/imprint.jerome-arfouche.ca/
  .env                          (keys listed in env.example — app, DB, Mailgun, notification recipient, deploy vars)
  auth.json                     composer credentials
  storage/                      shared across releases: app, framework, logs
  current -> releases/<id>
  releases/<id>/
    .env -> ../../.env
    storage -> ../../storage
    public/storage -> …/storage/app/public         (artisan storage:link)
    public/fonts   -> /mnt/data-volume/imprintfonts
```

Postgres runs on the same box (`127.0.0.1:5432`); `DB_*` in `.env`. Mail goes out through Mailgun.

## Deploy

1. Push to `master` → `deploy` job in `.github/workflows/ci.yml` (after `checks`, environment
   `production`) calls the Forge deploy-trigger URL (secret `FORGE_DEPLOY_URL`).
2. `forge_deploy.sh`: `$CREATE_RELEASE()`, strip the token from the remote URL, fix
   `storage`/`bootstrap/cache` permissions, `composer install --no-dev`, `pnpm install` + `pnpm run build`,
   `artisan optimize`, `storage:link`, **`migrate --force`**, config/route/view cache, link the fonts,
   `$ACTIVATE_RELEASE()`, `$RESTART_QUEUES()`, then `flock`-guarded `sudo service php8.5-fpm reload`.

`forge` may run `service php8.5-fpm reload` and `supervisorctl restart|start|stop|status|update` without a
password (`util/infra/droplet.md`). The `$…()` macros are Forge's and aren't plain shell — see below.

## nginx

`nginx.conf` includes Forge's `forge-conf/3265168/{before,after}/*` at its ends and
`forge-conf/3265168/site.conf` inside the server block; shared pieces come from `~/Code/util/infra/nginx/`
(`snippets/bot-mitigation.conf`, the `imprint_api` zone — commented out). Gzip and long-lived caching for the
Vite `/build/` directory are set in the vhost.

## Leaving Forge — what needs a new owner

- **The Forge macros**: `$CREATE_RELEASE()` (clone into `releases/<id>`), `$ACTIVATE_RELEASE()` (swap `current`,
  prune to 4 releases, **and link `.env` and the `storage` linked directory**), `$RESTART_QUEUES()` (`php artisan queue:restart`).
- **Deploy trigger** — SSH as `forge` replaces the curl in `ci.yml`.
- **vhost includes** — inline `forge-conf/3265168/site.conf` and the redirects, drop the `# FORGE …` lines.
- **The queue worker** — keep `supervisord.conf` as is; it's an ordinary supervisor program.
- **TLS** — Forge-managed `ssl_certificate` paths (`/etc/nginx/ssl/domains/1765309/<cert-id>/`), rewritten on every
  renewal; keep `/.well-known/acme-challenge/` served from `/home/forge/.letsencrypt/`.
- **Laravel scheduler** — nothing to migrate today.
