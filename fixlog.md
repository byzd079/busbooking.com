# Render Deployment — Fixlog

Target: Render free tier (Docker web service + free Postgres).
App: Laravel 11, PHP 8.2, repo root = `E:\busbooking.com\busbooking`, branch `main`.

---

## SOLVED

| # | Problem | Fix |
|---|---|---|
| 1 | `package-lock.json` untracked → Docker `COPY package.json package-lock.json ./` fails, build dies at frontend stage | committed (`a79a8b7`); `npm ci --dry-run` passes, 50 pkgs resolve |
| 2 | **Entire Render config untracked** — `Dockerfile`, `render.yaml`, `.dockerignore`, `docker/start.sh` existed locally but not in git, so Render had nothing to build | committed (`637e3cf`) |
| 3 | Refund migration `2025_08_06_021748` was an empty stub, but `RefundController` reads those columns → every refund path 500s on a fresh Postgres DB | filled in 8 columns + `down()` (`637e3cf`) |
| 4 | Laravel didn't trust Render's proxy → `url()`/`asset()` emit `http://`, mixed-content | `trustProxies(at: '*')` in `bootstrap/app.php` (`637e3cf`) |
| 5 | **`/pay` would break on Render only.** `SslCommerzNotification.php` built success/fail/cancel/ipn URLs from `env('APP_URL')`. `docker/start.sh` runs `php artisan optimize` → config cached → `env()` returns NULL → callbacks collapse to relative `/success`, gateway rejects init. Verified: `config:cache` then `env('APP_URL')` = NULL | swapped all 4 to `config('app.url')` (L235/245/255/265); re-verified sandbox init returns `SUCCESS` + GatewayPageURL **with config cached** |

## NEEDS TO SOLVE (blocking)

| # | Problem | Action needed |
|---|---|---|
| B1 | Nothing pushed yet — 2 commits sit local-only | `git push origin main` (awaiting user OK) |
| B2 | `APP_KEY` is `sync: false` in render.yaml | run `php artisan key:generate --show`, paste into Render env tab |
| B3 | `APP_URL` is `sync: false` | set to `https://<service>.onrender.com` after first deploy |
| B4 | ~~SSLCommerz creds blank~~ **RESOLVED locally** — `.env` now has store ID (len 18) + password (len 22); sandbox init returns `SUCCESS` + GatewayPageURL | still `sync: false` in render.yaml → paste both into Render env tab |

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
| N8 | `makePayment(..., 'checkout')` is dead code — `formatResponse` returns a JSON *string* for that type, but `makePayment` then does `if (!is_array(...)) return "Error: Invalid response from payment gateway"`. Never hit today (controller uses `'hosted'`) | leave, or fix if a popup/ajax checkout is ever wired up |
| N9 | `SslCommerzPaymentController@index` has no `return` after `makePayment(..., 'hosted')`. Works only because the library calls raw `header()` + `exit()`, bypassing Laravel's response cycle. If the gateway ever errors, the branch `print_r($payment_options)` prints then falls off the end → blank page | return a redirect/view instead of relying on `exit()` |

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

---

codex-[2026-08-05 11:01:15 +06:00]: Investigating why `http://127.0.0.1:8000/pay` does not work. Opening this URL in a browser sends a GET request, but `routes/web.php` defines `/pay` as POST-only, so Laravel returns HTTP 405 as expected. The intended flow is: search for a bus, select seats, open `/payment_details`, complete the passenger form, and submit that form to `/pay`. A second blocking problem occurs in that normal flow: local `.env` has no `SSLCZ_STORE_ID` or `SSLCZ_STORE_PASSWORD`, so SSLCommerz cannot initialize a payment. This needs to be solved by adding valid SSLCommerz sandbox credentials to `.env`; keep `SSLCZ_TESTMODE=true` and `IS_LOCALHOST=true` for local testing. Do not expose or commit those credentials. No payment route code was changed during this diagnosis.
- `.dockerignore` correctly excludes `.env`, `vendor`, `node_modules`, `public/build`.
- `public/build` gitignored but built inside Docker frontend stage → correct.
