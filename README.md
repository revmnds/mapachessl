# MapacheSSL

Web wizard for issuing Let's Encrypt SSL certificates. The user enters a domain, picks HTTP or DNS validation, and downloads a ZIP with the cert, chain, and private key. Wildcards supported.

**Live demo:** <https://mapachessl.com>

![MapacheSSL welcome screen](docs/screenshot.png)

Uses the ACME v2 protocol via `skoerfgen/acmecert` — no certbot dependency.

## Stack

Laravel 12, PHP 8.4, Alpine.js, Tailwind 4, Vite 7, PostgreSQL. Docker for deployment.

## Setup

```bash
cp .env.example .env
docker compose up --build -d
docker compose exec app php artisan key:generate
```

Migrations run automatically on start (`RUN_MIGRATIONS=true` on the `app` service).

Open <http://localhost:8080>.

Local dev without Docker: `composer install && npm install && composer dev`.

## Production (Portainer GitOps)

`docker-compose.gitops.yml` is the production stack. In Portainer: *Stacks > Add stack > Repository* with `https://github.com/revmnds/mapachessl`, reference `refs/heads/main`, compose path `docker-compose.gitops.yml`, GitOps updates on. Set `APP_KEY` and `DB_PASSWORD` as stack environment variables. Every push to `main` rebuilds and redeploys.

Nothing is published on the host: Nginx Proxy Manager reaches the `web` service on the external `npm` network as `mapachessl-nginx:80`.

## Configuration

In `.env`:

- `ACME_STAGING` — `true` for testing against Let's Encrypt staging (no rate limits, untrusted certs).
- `DB_*` — PostgreSQL credentials (`DB_PASSWORD` is required in production).
- `QUEUE_WORKERS` / `ACME_MAX_CONCURRENT` — parallel generations.
- `TRUSTED_PROXIES` — overrides `bootstrap/trusted-proxies.php` (private networks + Cloudflare).

## Notes

- PostgreSQL (`postgres` service). Certificate requests live at most 7 days, so there is little to back up besides the ACME account key in `storage/app/acme`.
- `QUEUE_WORKERS` workers; each generation can hold one for up to an hour.
- No automatic renewal — each request is one-shot.
- HTTP-01 needs the domain pointing at a server you control on port 80.

## License

MIT.
