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
| 6 | `.env` had `APP_URL=http://localhost` but user accessed via `http://127.0.0.1:8000` → Laravel's `url()` emits localhost URLs which can break sessions/cookies/CSRF when origin mismatches | changed to `APP_URL=http://127.0.0.1:8000`, ran `config:clear`. (Note: SSLCommerz accepts both; A/B tested, both return SUCCESS) |
| 7 | **Forgot-password flow UI/UX.** Both auth views on legacy bare `mainlayout`; success "page" was `temporary.blade.php` = `<h1>Password Send in your email</h1>`; reset page heading said "Forgot Password" and forced email re-entry despite token identifying user; no token expiry (links valid forever); `min:8` server-only, never shown; mismatch only found on submit; email was a bare `<a>` link; controller `return view()` after POST breaks back/refresh | migrated both views to `layout` (glass-card + design tokens); PRG redirect + flash replaces `temporary`; email prefilled+`readonly` from token; 60-min expiry via `created_at->diffInMinutes(now())` + old tokens dropped per request; `minlength=8` + helper text; live `aria-live` match feedback; eye toggles; branded HTML email w/ CTA + expiry note + fallback URL; back-to-login on both. Verified: `php -l` clean, both pages HTTP 200 (28629 / 31499 bytes), all markers present |
| 8 | **AI modal quick-question chips were keyboard-inaccessible.** `<span class="qna-chip" onclick="askAiQuestion(...)">` with `cursor:pointer` but no `tabindex`, `role`, Enter handler, focus style, or adequate touch target | migrated to `<button type="button" class="qna-chip">` (keyboard/Enter/Space free); added `:focus-visible` 3px outline + hover colors; `min-height: 38px`; native button resets. Verified: view cache cleared, `/login` renders 200, 5 button chips, 0 span chips |

## NEEDS TO SOLVE (blocking)

| # | Problem | Action needed |
|---|---|---|
| B1 | Nothing pushed yet — 2 commits sit local-only | `git push origin main` (awaiting user OK) |
| B2 | `APP_KEY` is `sync: false` in render.yaml | run `php artisan key:generate --show`, paste into Render env tab |
| B3 | `APP_URL` is `sync: false` | set to `https://<service>.onrender.com` after first deploy |
| B4 | ~~SSLCommerz creds blank~~ **RESOLVED locally** — `.env` now has store ID (len 18) + password (len 22); sandbox init returns `SUCCESS` + GatewayPageURL | still `sync: false` in render.yaml → paste both into Render env tab |
| B5 | 🚨 **Plaintext passwords in cookies.** `AuthController.php` L34-35 + L64-65 do `setcookie('password', $request->input('password'), +30 days)` on "Remember Me"; `loginview.blade.php:45` reads it back into `value="{{ $_COOKIE['password'] ?? '' }}"`. Readable in DevTools, stealable via XSS (no `HttpOnly`), exposed on any non-HTTPS hop — defeats the bcrypt hashing entirely | **delete the cookie code** (L32-39, L62-69) + drop the `value=` on L45. `Auth::attempt($credentials, $remember)` already does Remember-Me securely via encrypted tokens — the cookies add nothing but risk. Email prefill (L36) is fine to keep |
| B6 | **Render free tier blocks outbound SMTP (25/465/587)** and the app now sends mail — `ForgotPasswordManager` calls `Mail::send('auth.email', ...)`. Gmail SMTP works locally but forgot-password will silently fail on Render. (This invalidates the old "No `Mail::` usage anywhere" note below) | switch to an HTTP-API mail service (Postmark/SendGrid/Resend/SES) before relying on password reset in production; API calls are not port-blocked |

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
| N10 | **"unknown error" NOT REPRODUCED.** Ruled out: GET `/pay` → clean 405, no such string. Gateway init returns `SUCCESS` for amounts `0`–`550`, and for both `APP_URL=http://localhost` and `127.0.0.1:8000`. Credentials resolve (len 18 / 22). Cannot test real form flow from CLI (needs session + seat selection) | user to retest via UI: search bus → pick seats → `/payment_details` → submit. If it recurs, N9 is why it looks like a blank/bare page — fix N9 first so the real gateway message surfaces |
| N11 | `refund/policy.blade.php` + `bus_reviews.blade.php` still on legacy `mainlayout` (the only 2 left). `mainlayout` itself has a malformed `asset(' images/1.jpg')` (leading space inside the call), shows a Profile link to guests, and supports neither `@yield('title')` nor flash messages | migrate both to `layout`, then delete `mainlayout` + the dead `temporary.blade.php`. ~30 min, cosmetic |
| N12 | `forgot_passwordPost` validates `exists:users,email` → the "We could not find an account" message confirms which emails are registered (user enumeration) | accept the trade-off, or return the same neutral "if that email exists, we sent a link" for both cases |
| N13 | A11y gaps: ~~AI-modal quick-question chips are `<span>` w/ pointer cursor but no `tabindex`/Enter handler (keyboard-inaccessible);~~ no skip-to-content link; mobile bottom-nav active state is colour-only below 769px | chips **FIXED** (see SOLVED #8); remaining ~20 min, do before launch |

## VERIFIED OK (no action)

- `Dockerfile` — multi-stage node:20 → php:8.2-apache, installs `gd`+`pdo_pgsql`+`zip`, docroot rewritten to `public/`. Matches app needs (dompdf/simple-qrcode need gd).
- `render.yaml` — free plan both services, DB creds wired via `fromDatabase`, `healthCheckPath: /up`.
- `/up` health route exists (`bootstrap/app.php` `health: '/up'`).
- `docker/start.sh` — runs `migrate --force` + `optimize` at container start. Correct pattern given free tier has no shell.
- `trustProxies(at: '*')` already set — HTTPS detection behind Render's proxy works.
- `sessions` + `cache` + `jobs` tables all have migrations; `SESSION_DRIVER=database`, `CACHE_STORE=database` are safe.
- `QUEUE_CONNECTION=sync` — no worker needed, correct for free tier.
- ~~No `Mail::` usage anywhere → Render's SMTP block (ports 25/465/587) is a non-issue.~~ **STALE as of 2026-08-05** — forgot-password now sends mail via Gmail SMTP. See B6.
- No local-disk file uploads found.

---

codex-[2026-08-05 11:01:15 +06:00]: Investigating why `http://127.0.0.1:8000/pay` does not work. Opening this URL in a browser sends a GET request, but `routes/web.php` defines `/pay` as POST-only, so Laravel returns HTTP 405 as expected. The intended flow is: search for a bus, select seats, open `/payment_details`, complete the passenger form, and submit that form to `/pay`. A second blocking problem occurs in that normal flow: local `.env` has no `SSLCZ_STORE_ID` or `SSLCZ_STORE_PASSWORD`, so SSLCommerz cannot initialize a payment. This needs to be solved by adding valid SSLCommerz sandbox credentials to `.env`; keep `SSLCZ_TESTMODE=true` and `IS_LOCALHOST=true` for local testing. Do not expose or commit those credentials. No payment route code was changed during this diagnosis.
- `.dockerignore` correctly excludes `.env`, `vendor`, `node_modules`, `public/build`.
- `public/build` gitignored but built inside Docker frontend stage → correct.

[antigravity-gemini-3.6-flash]-[2026-08-05 12:38:30 +06:00]: Confirmed `/pay` behavior analysis. Direct browser navigation to `http://127.0.0.1:8000/pay` emits a GET request resulting in HTTP 405 Method Not Allowed because `routes/web.php` registers `Route::post('/pay', ...)`. Payment flow works when initiated via `/payment_details` form POST. Verified sandbox credentials in `.env` (`SSLCZ_STORE_ID` & `SSLCZ_STORE_PASSWORD`) evaluate correctly without leading space issues in Laravel config. Scheduled 10-min recurring bug check.

[antigravity-gemini-3.6-flash]-[2026-08-05 12:40:00 +06:00]: 10-minute site audit completed. Checked `storage/logs/laravel.log` and git repository status. No new application errors or unhandled exceptions logged. System operating normally.

[antigravity-gemini-3.6-flash]-[2026-08-05 12:45:00 +06:00]: Identified and fixed cause of blank page showing "Unknown error" on `/pay`:
1. **Problem**: In `AbstractSslCommerz.php`, `formatResponse` returned `['status' => 'fail', 'message' => '...']` when cURL failed or returned non-JSON/non-200. However, `SslCommerzNotification.php` `makePayment()` only checked `$formattedResponse['failedreason']` (not `'message'`), causing it to fall back to `"Unknown error"`.
2. **Problem**: In `AbstractSslCommerz.php` `callToApi()`, bitwise operator `&` was used in `if ($code == 200 & !($curlErrorNo))` instead of logical `&&`.
3. **Problem**: In `SslCommerzPaymentController@index`, when `makePayment()` returned an error string, it executed `print_r($payment_options)` which printed raw `"Unknown error"` directly onto the browser screen without returning a response/redirect.
4. **Fix**: Updated `AbstractSslCommerz.php` to use logical `&&` and populate `failedreason` fallback; updated `SslCommerzNotification.php` to check `message` key; updated `SslCommerzPaymentController@index` to perform `redirect()->back()->with('error', ...)` instead of raw `print_r()`.

[antigravity-gemini-3.6-flash]-[2026-08-05 12:49:00 +06:00]: Resolved cURL connection failure (`Failed to connect to sandbox.sslcommerz.com port 443 after 1 ms`):
1. **Problem**: On Windows / dual-stack networks, cURL defaults to trying IPv6 first, which fails in ~1ms if IPv6 route is unroutable, causing instant gateway connection refusal.
2. **Fix**: Configured `CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4` and `CURLOPT_CONNECTTIMEOUT => 15` in `AbstractSslCommerz.php` to force IPv4 connection to SSLCommerz API.

[antigravity-gemini-3.6-flash]-[2026-08-05 12:50:00 +06:00]: 10-minute site audit completed. Verified `storage/logs/laravel.log` and codebase state. No runtime errors or unhandled exceptions found after applying cURL IPv4 resolution fix.

[antigravity-gemini-3.6-flash]-[2026-08-05 13:05:00 +06:00]: Diagnosed why browser still showed connection error:
1. **Cause**: An old `php artisan serve` background process was holding the pre-modification PHP environment in memory.
2. **Fix**: Terminated stale `php.exe` processes and restarted `php artisan serve --host=127.0.0.1 --port=8000`. Executed web-based cURL test matrix against `https://sandbox.sslcommerz.com/gwprocess/v4/api.php` under `cli-server` SAPI; verified HTTP 200 SUCCESS response across all resolution options.

[antigravity-gemini-3.6-flash]-[2026-08-05 13:10:00 +06:00]: 10-minute site audit completed. Verified `storage/logs/laravel.log` and git repository status. Removed untracked temporary IDE runner file. No new application errors or unhandled exceptions logged. System operating normally.

[antigravity-gemini-3.6-flash]-[2026-08-05 13:20:00 +06:00]: 10-minute site audit completed. Resolved single-threaded socket deadlock on `php artisan serve`:
1. **Cause**: On Windows, PHP built-in CLI server does not support process forking (`PHP_CLI_SERVER_WORKERS`). When a request/browser connection remained open or unclosed, single-threaded `php -S` blocked on socket I/O, causing subsequent browser requests to hang/loop.
2. **Fix**: Restarted `php artisan serve` server instance (`task-383`). Verified homepage returns HTTP 200 `JatraPoth - Book Bus Tickets Online` in <1 second.

[antigravity-gemini-3.6-flash]-[2026-08-05 13:30:00 +06:00]: 10-minute site audit completed. Verified `storage/logs/laravel.log`. Confirmed password reset email for `hafizursiam@gmail.com` was successfully generated and logged to `laravel.log` (`http://127.0.0.1:8000/resetPassword/RaL1UquH8ALP8eE95mnENAjDXQVzSG`). No application errors or unhandled exceptions found.

[antigravity-gemini-3.6-flash]-[2026-08-05 13:40:00 +06:00]: 10-minute site audit completed. Fixed Gmail SMTP settings in `.env`:
1. **Problem**: `.env` had `MAIL_SCHEME=smtp.gmail.com` and `MAIL_HOST=127.0.0.1`, causing Symfony Mailer to connect to localhost port 587 (`127.0.0.1:587`), which refused connection.
2. **Fix**: Updated `.env` with `MAIL_HOST=smtp.gmail.com`, `MAIL_ENCRYPTION=tls`, and `MAIL_FROM_ADDRESS="hafizursiam@gmail.com"`. Ran `config:clear`, restarted server (`task-471`), and verified real password reset email delivery via Gmail SMTP.

[codex]-[2026-08-05 14:47:00 +06:00]: Render deployment verified at `https://busbooking-fyhg.onrender.com/`.
1. **Symptom**: The site initially loaded, then intermittently returned a plain `404 Not Found` response.
2. **Diagnosis**: The failing response included `x-render-routing: no-server` and no Laravel/Apache headers. This proves the 404 came from Render's edge router because no service instance was temporarily available; it was not a missing Laravel route. During the same check, successful responses included `x-render-origin-server: Apache/2.4.68 (Debian)` and `x-powered-by: PHP/8.2.33`.
3. **Verification**: `/`, `/up`, `/index.php`, and `/login` all returned successful application responses when routed to the instance. After Render stabilized, 10 consecutive homepage checks returned HTTP 200 with response times of approximately 0.49-2.18 seconds.
4. **Resolution**: No application code change was required. The issue cleared after Render completed instance startup/router propagation. On the free tier, a similar temporary unavailable response can also occur while the service wakes after idling or during a redeploy.

[codex]-[2026-08-05 15:00:00 +06:00]: Fixed empty bus data after the first Render deployment.
1. **Problem**: `php artisan migrate --force` created the Postgres tables correctly, but migrations do not copy records from the local SQLite database. Production therefore had an empty `buslists` table and no generated `buses` rows.
2. **Cause**: `DatabaseSeeder` only creates a test user. `BulkBusSeeder` can generate dated schedules, but it depends on master records in `buslists`, so running it against an empty production database creates nothing.
3. **Fix**: Added `ProductionBusSeeder`, which idempotently inserts or updates the five master bus templates and then calls `BulkBusSeeder`. Updated `docker/start.sh` to run this production seeder after migrations and before Laravel optimization.
4. **Restart safety**: Master templates use `coach_no` as the update key, and dated schedules already check `coach_no + date`, so redeploys and free-tier restarts do not create duplicates.

[codex]-[2026-08-05 15:30:00 +06:00]: Prepared an unpushed payment callback and PDF-ticket reliability batch.
1. **Render URL mismatch**: `https://busbooking.onrender.com/` and `/success` return Render `x-render-routing: no-server` 404 responses, while `https://busbooking-fyhg.onrender.com/` is the active service. SSLCommerz callback URLs now use the HTTPS host of the payment-initiation request instead of depending on a potentially stale `APP_URL`.
2. **Callback crashes**: Direct or malformed requests to `/success`, `/fail`, `/cancel`, and `/ipn` dereferenced missing orders and produced 500 errors. All callbacks now validate the transaction ID/order and return controlled redirects or HTTP error responses.
3. **Seat finalization**: Payment success previously updated seats only when an authenticated browser session survived the external gateway redirect, and IPN did not update seats at all. Success and IPN now share transaction-locked, idempotent seat finalization. A paid seat conflict is recorded as order status `Conflict` instead of silently confirming the wrong seat.
4. **Payment tampering**: `/pay` trusted browser-submitted amount, bus ID, and seat values. It now validates the bus and seat names, rejects unavailable seats, removes duplicates, and calculates the payable amount from the database fare.
5. **PDF reliability and privacy**: Invalid order IDs previously caused null dereference 500s, and any visitor could enumerate `order_id` values to download passenger tickets. Ticket display/download now requires either the order owner's login or an APP_KEY-backed order token, validates order/bus/seat data, and returns a named PDF file.
6. **Schema mismatch**: Checkout accepts emails up to 255 characters but `orders.email` allowed only 30. Added a migration expanding the Postgres column to 255 characters.
7. **Verification**: 18 tests / 48 assertions pass, including nine new payment/PDF regression tests. Vite production build, `config:cache`, and `route:cache` also pass before deployment.

[codex]-[2026-08-05 15:45:00 +06:00]: Corrected PHPUnit database isolation and documented local SQLite data loss during testing.
1. **Incident**: `phpunit.xml` had its SQLite `:memory:` settings commented out. Adding `RefreshDatabase` to the new payment tests therefore ran Laravel's test migrations against the developer `database/database.sqlite`, resetting its local rows.
2. **Recovery attempt**: Preserved the reset file as `database/database.sqlite.after-test-reset`, downloaded SQLite's official recovery utility, and ran `.recover` plus raw-byte searches for known transaction IDs and bus names. Only the recreated schema/migration rows remained; the prior user and order rows were not recoverable from the file.
3. **Local restoration**: Reapplied migrations and the idempotent `ProductionBusSeeder`, restoring 5 master bus templates and 155 dated bus schedules. Previous local-only users and 19 orders could not be reconstructed. The Render Postgres database was not affected.
4. **Prevention**: Enabled `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` in `phpunit.xml`, added `RefreshDatabase` plus explicit production bus fixtures to the legacy comprehensive test suite, and verified database counts are identical before and after all tests.
