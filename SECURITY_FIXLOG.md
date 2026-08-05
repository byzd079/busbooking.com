# Security Audit & Fix Log
**Date:** 2026-08-05  
**Audit Scope:** Laravel application + Docker + Render.com deployment

---

## Critical Vulnerabilities

### 1. ✅ Plaintext Passwords in Cookies
**File:** `app/Http/Controllers/AuthController.php`  
**Lines:** 34-38, 64-68  
**Issue:** User passwords stored in plaintext cookies for "Remember Me" functionality  
**Risk:** Password theft via XSS, network interception, or cookie access  
**Fix:** Removed all plaintext password cookie storage. Laravel's built-in `Auth::attempt($credentials, $remember)` already handles secure "Remember Me" via encrypted tokens in `remember_token` database column.  
**Status:** ✅ FIXED

### 2. ℹ️ CSRF-Exempt Routes (Payment Gateway Callbacks) — NOT a critical issue
**File:** `bootstrap/app.php`
**Lines:** 20-28
**Original claim (WRONG):** "Any attacker can cancel pending orders with known transaction IDs."

**Correction after reading the actual code:** `SslCommerzPaymentController::closeUnsuccessfulOrder()` (L251-272) already guards every case:
- Missing `tran_id` → redirect with error (L254)
- Unknown order → redirect with error (L259)
- Already `Processing`/`Complete` → refuses to downgrade (L263)
- Only a `Pending` order transitions (L267)

And `tran_id` is `Str::random(30)` (L54) — not guessable, and known only to the buyer and the gateway. The realistic worst case is a user cancelling their own pending booking, which they can already do.

These routes **must** remain CSRF-exempt: SSLCommerz redirects the browser to them without a session token, so requiring CSRF would break the payment flow with 419 errors. CSRF was never the applicable control here.

**Status:** ℹ️ NO ACTION NEEDED (exemptions documented in code). See `fixlog.md` B8 for optional future hardening — note that naively adding `orderValidate()` would leave failed orders stuck `Pending`, since it validates *successful* transactions.

### 3. ✅ Dead Route with CSRF Exemption
**File:** `routes/web.php` line 87  
**Issue:** `/pay-via-ajax` route removed (controller method doesn't exist)  
**Status:** ✅ FIXED

---

## High Severity

### 4. ✅ Session Cookies Sent Over HTTP
**File:** `.env`  
**Issue:** `SESSION_SECURE_COOKIE` not set, cookies sent unencrypted  
**Fix:** Added `SESSION_SECURE_COOKIE=true` to `.env`  
**Status:** ✅ FIXED

### 5. ✅ Missing CSRF Defense-in-Depth
**File:** `.env`  
**Issue:** `SESSION_SAME_SITE` not set (should be `lax`)  
**Fix:** Added `SESSION_SAME_SITE=lax` to `.env`  
**Status:** ✅ FIXED

### 6. ✅ No Security Headers
**Issue:** Missing X-Frame-Options, CSP, HSTS, X-Content-Type-Options, Referrer-Policy  
**Fix:** Installed `bepsvpt/secure-headers` package and registered middleware in `bootstrap/app.php`  
**Status:** ✅ FIXED

### 7. 📝 CORS Wildcard Origins
**File:** `config/cors.php` (uses Laravel framework defaults)  
**Issue:** `allowed_origins` defaults to `['*']`  
**Fix:** Added `FRONTEND_URL` environment variable documentation in `.env`. No config/cors.php exists yet (using framework defaults). For production: restrict to actual frontend domain.  
**Status:** 📝 DOCUMENTED

### 8. ✅ Debug Mode Enabled
**File:** `.env`  
**Issue:** `APP_DEBUG=true` and `APP_ENV=local` will leak stack traces in production  
**Fix:** Added production deployment comment: `# PRODUCTION: Set APP_ENV=production, APP_DEBUG=false, IS_LOCALHOST=false`  
**Status:** ✅ DOCUMENTED (developer action required on deployment)

---

## Medium Severity

### 9. ✅ SSL Verification Disabled in Production
**File:** `.env`  
**Issue:** `IS_LOCALHOST=true` disables SSL peer verification for payment gateway  
**Fix:** Added comment: `# PRODUCTION: Set IS_LOCALHOST=false to enable SSL verification for payment gateway`  
**Status:** ✅ DOCUMENTED (developer action required on deployment)

### 10. ✅ CSRF Token Coverage Audit
**Issue:** Two files had POST forms without @csrf tokens  
**Fix:** Added `@csrf` to:
  - `resources/views/exampleEasycheckout.blade.php`
  - `resources/views/businformation.blade.php`  
**Status:** ✅ FIXED

---

## Low / Hardening

### 11. ✅ Mass Assignment Risk
**File:** `app/Models/Order.php`  
**Issue:** `refund_processed_by` in $fillable could be forged  
**Fix:** Removed `refund_processed_by` from $fillable array. Admins must now set it explicitly.  
**Status:** ✅ FIXED

---

## Production Deployment Checklist

**Note:** `render.yaml` already sets these for the deployed container — `APP_ENV=production`, `APP_DEBUG=false`, `IS_LOCALHOST=false`, `SESSION_SECURE_COOKIE=true`, and (added in `7088bdd`) `SESSION_SAME_SITE=lax`. The local `.env` is **not** used on Render, so the `.env` comments added during this audit are for local dev clarity only.

Still `sync: false` in render.yaml and must be pasted into the Render env tab by hand:
- `APP_KEY`
- `APP_URL`
- `SSLCZ_STORE_ID`
- `SSLCZ_STORE_PASSWORD`

---

## Fix Summary
- ✅ **Fixed and pushed:** 6 real issues (plaintext password cookies — write *and* read paths, legacy cookie expiry, security headers, session samesite, 2 missing CSRF tokens, mass-assignment)
- ℹ️ **Investigated, no action needed:** 1 (payment callback CSRF exemptions — original "critical" claim was wrong, see #2)
- 📝 **Documented only:** CORS defaults

**Corrections made to this document after re-checking the code:**
1. The password-cookie fix was initially incomplete — the controller stopped *writing* the cookie but `loginview.blade.php:45` still *read* it back into the password field. Fixed in `68814a4`.
2. Finding #2 was overstated as critical. The handler already guards null/unknown/completed states and `tran_id` is a 30-char random token. Downgraded.
3. The `.env` production settings were largely redundant — `render.yaml` already sets them. Only `SESSION_SAME_SITE` was genuinely missing.

---

## Not Yet Verified
- **Security headers on production.** Confirmed working locally (`127.0.0.1:8001` returns X-Frame-Options, nosniff, Referrer-Policy, samesite=lax). As of 10:14 UTC the live host still served the old container — deploy monitoring in progress. See `fixlog.md` B9.
- **`composer audit`** — not run (first attempt used a malformed path; not retried).

---

## Next Steps (Future Hardening)

1. **Unauthenticated bus CRUD** (`fixlog.md` N6) — `routes/web.php` L39-46 allow anyone to `DELETE /bus/{id}` or `POST /storedata`. This is a genuinely bigger hole than anything in this document and should be fixed first.
2. **Public diagnostic routes** (`fixlog.md` N5) — `/extra`, `/temporary`, `/test-bus-rating/{id}`, `/layout`, `/master2`, `/example1` are all unauthenticated.
3. **CORS Configuration:** create `config/cors.php` and restrict `allowed_origins` to the actual frontend domain.
4. **Dependency Audit:** run `composer audit` for known CVEs.

