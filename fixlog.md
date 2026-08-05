# Render Deployment — Fixlog

Target: Render free tier (Docker web service + free Postgres).
App: Laravel 11, PHP 8.2, repo root = `E:\busbooking.com\busbooking`, branch `main`.

---

## SOLVED

| # | Problem | Fix |
|---|---|---|
| 1 | `package-lock.json` untracked in git → Docker `COPY package.json package-lock.json ./` fails, build dies at frontend stage | staged the lockfile (2878 lines); needs commit+push |

## NEEDS TO SOLVE (blocking)

| # | Problem | Action needed |
|---|---|---|
| B1 | `APP_KEY` marked `sync: false` in render.yaml — not auto-generated | run `php artisan key:generate --show` locally, paste into Render env tab |
| B2 | `APP_URL` marked `sync: false` | set to `https://<service>.onrender.com` after first deploy |
| B3 | SSLCommerz creds empty (`SSLCZ_STORE_ID` / `_PASSWORD` blank in .env) | user must supply, or payments stay broken |
| B4 | Not pushed to GitHub yet | Render deploys from repo; local commits alone do nothing |

## NEEDS TO SOLVE (non-blocking, before real traffic)

| # | Problem | Note |
|---|---|---|
| N1 | `SSLCZ_TESTMODE=true` in render.yaml | live payments won't move money until `false` |
| N2 | Free Postgres expires 30 days after creation, no backups | hard clock; upgrade or `pg_dump` before expiry |
| N3 | Free web service spins down after 15 min idle, ~1 min cold start | expected, not a bug |
| N4 | `FILESYSTEM_DISK=local` + ephemeral FS | OK only while nothing is uploaded; any future upload feature needs S3/R2 |
| N5 | Diagnostic routes are public: `/extra` (L23), `/temporary` (L67), `/test-bus-rating/{id}` (L167), `/layout` (L57), `/master2` (L95), `/example1` (L81) | remove before launch — all unauthenticated |
| N6 | **CONFIRMED: bus CRUD is fully unauthenticated.** `routes/web.php` L39-46 sit outside every middleware group; `BusController` has no `__construct` and no in-method auth check. Anyone can `DELETE /bus/{id}`, `POST /storedata`, `POST /updatedata/{id}` | wrap L39-46 in `Route::middleware(['admin'])->group(...)` — same guard the admin panel already uses at L114 |
| N7 | `/api/bus/{id}` (L152) returns full Bus model as JSON, unauthenticated | check for sensitive columns; scope the response |

## VERIFIED OK (no action)

- `Dockerfile` — multi-stage node:20 → php:8.2-apache, installs `gd`+`pdo_pgsql`+`zip`, docroot rewritten to `public/`. Matches app needs (dompdf/simple-qrcode need gd).
- `render.yaml` — free plan both services, DB creds wired via `fromDatabase`, `healthCheckPath: /up`.
- `/up` health route exists (`bootstrap/app.php` `health: '/up'`).
- `docker/start.sh` — runs `migrate --force` + `optimize` at container start. Correct pattern given free tier has no shell.
- `trustProxies(at: '*')` already set — HTTPS detection behind Render's proxy works.
- `sessions` + `cache` + `jobs` tables all have migrations; `SESSION_DRIVER=database`, `CACHE_STORE=database` are safe.
- `QUEUE_CONNECTION=sync` — no worker needed, correct for free tier.
- No `Mail::` usage anywhere → Render's SMTP block (ports 25/465/587) is a non-issue.
- No local-disk file uploads found.
- `.dockerignore` correctly excludes `.env`, `vendor`, `node_modules`, `public/build`.
- `public/build` gitignored but built inside Docker frontend stage → correct.
