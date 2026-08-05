# Admin Authentication Security Hardening — Implementation Plan

**Date:** 2026-08-05  
**Scope:** Secure admin auth, migrate to Laravel guard, professional UI/UX redesign

---

## CRITICAL VULNERABILITIES FOUND

### 1. Public Admin Self-Registration (CRITICAL — FIX FIRST)

**Current state:**
- `GET/POST /custom_register` (`routes/web.php:107-108`) is **PUBLIC** with only `web` middleware
- **Anyone can create an admin account** and gain full access to:
  - Bus CRUD (already unguarded — N6 in fixlog)
  - User PII search (`AdminController@admin_search`)
  - Order management + refund approval
  - Seat layout editing
  - Bulk bus generation

**Fix:** Delete the routes or protect behind existing admin middleware + env flag.

### 2. Session-Array Authentication (HIGH)

**Current state:**
- Both `AdminController@adminLoginPost` and `CustomController@custom_loginPost` manually store `session()->put('admin_user', [...])` (lines 41-44, 49-54)
- `OnlyAdmin` middleware checks `session()->has('admin_user')` (L20)
- Bypasses:
  - Laravel's `Auth::guard()` session regeneration (CSRF session fixation protection)
  - Login throttling (`RateLimiter`)
  - Remember-me encryption
  - `Auth::check()` / `Auth::user()` convenience

**Fix:** Migrate to `Auth::guard('admin')->attempt($credentials, $remember)`.

### 3. No Login Throttling (MEDIUM)

**Current state:** No `RateLimiter` on `/admin_login` POST or `/custom_login` POST.

**Fix:** Add `throttle:5,1` middleware (5 attempts per minute per IP).

### 4. Duplicate Controllers (MEDIUM — CLEANUP)

- `AdminController@adminLoginPost` (L31-52) and `CustomController@custom_loginPost` (L39-61) are **identical** except variable names.
- Two separate login routes: `/admin_login` POST → `AdminController`, `/custom_login` POST → `CustomController`.

**Fix:** Delete `CustomController` entirely, keep only `AdminController`.

### 5. Docker Root User (HIGH — Render security)

**Current state:** `Dockerfile` has no `USER` directive — container runs as root.

**Fix:** Add `RUN groupadd -r www && useradd -r -g www www` + `USER www` before `CMD`.

---

## IMPLEMENTATION STEPS

### Phase 1: Disable Public Registration (DO FIRST)

**Option A (recommended):** Delete the routes entirely.

```php
// routes/web.php L107-108 — DELETE THESE TWO LINES
```

**Option B:** Protect behind admin middleware + env flag (if you want seeding in dev):

```php
Route::middleware(['admin'])->group(function () {
    if (config('app.allow_admin_registration', false)) {
        Route::get('/custom_register', [CustomController::class, 'custom_register']);
        Route::post('/custom_register', [CustomController::class, 'custom_registerPost']);
    }
});
```

Then `.env`: `ALLOW_ADMIN_REGISTRATION=false` (and `render.yaml` sync: false).

### Phase 2: Migrate to Laravel Auth Guard

**2.1. Update `AdminController@adminLoginPost`**

Replace manual session storage with `Auth::guard('admin')->attempt()`:

```php
public function adminLoginPost(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::guard('admin')->attempt(
        $request->only('email', 'password'),
        $request->boolean('remember')
    )) {
        $request->session()->regenerate(); // CSRF session-fixation protection
        return redirect()->intended(route('admin.dashboard'));
    }

    return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
}
```

**2.2. Update `OnlyAdmin` middleware**

Replace `session()->has('admin_user')` with `Auth::guard('admin')->check()`:

```php
public function handle(Request $request, Closure $next): Response
{
    if (!Auth::guard('admin')->check()) {
        return redirect()->route('admin_login.view')
            ->with('error', 'Please login to access admin panel');
    }
    return $next($request);
}
```

**2.3. Update `AdminController@adminLogOut`**

```php
public function adminLogOut()
{
    Auth::guard('admin')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('home')->with('success', 'Logged out successfully');
}
```

**2.4. Delete `CustomController` entirely**

Delete `app/Http/Controllers/CustomController.php` and remove the `use` statement from `routes/web.php:8`.

Delete or comment out `routes/web.php:107-108, 136`.

### Phase 3: Add Login Throttling

**3.1. Apply `throttle` middleware to login routes**

```php
// routes/web.php L111
Route::post('/admin_login', [AdminController::class, 'adminLoginPost'])
    ->middleware('throttle:5,1') // 5 attempts per minute per IP
    ->name('admin_login.post');
```

**3.2. Show throttle errors in the login view**

Already handled — `@if($errors->any())` will catch `TooManyRequestsException`.

### Phase 4: Secure Docker Image

**4.1. Add non-root user to `Dockerfile`**

Insert before the final `CMD`:

```dockerfile
# Create non-root user and switch to it
RUN groupadd -r www && useradd -r -g www -s /bin/false www
RUN chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache
USER www
```

**⚠️ Apache conflict:** The base image `php:8.2-apache` runs Apache as `www-data` (UID 33). If you switch the container user to `www` (a different UID), Apache will fail to bind to port 80 (needs root or `CAP_NET_BIND_SERVICE`).

**Correct fix for `php:8.2-apache`:**

```dockerfile
# Reuse the existing www-data user from the base image
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
USER www-data
```

### Phase 5: Admin Panel UI/UX Redesign

**Current issues:**
- `admin/dashboard.blade.php` extends `layout` (public layout with mobile bottom-nav, AI FAB)
- Most other admin views extend `admin.layout` (bare, no design tokens)
- `admin/login.blade.php` is standalone (purple gradient, no layout inheritance)
- Inconsistent: 2 layouts for admin, 1 standalone login

**Redesign plan:**
1. Create a unified `resources/views/admin/layouts/admin.blade.php` — clean admin-specific design (sidebar nav, top bar with logout, no public mobile elements)
2. Migrate all admin views to extend `admin.layouts.admin`
3. Redesign `admin/login.blade.php` to match the new admin aesthetic (keep the existing polished look, but align with admin branding)
4. Apply design tokens, glass-card pattern, proper spacing, WCAG AA compliance

---

## FILES TO MODIFY

| File | Action |
|---|---|
| `routes/web.php` | Delete L107-108 (custom_register), L136 (custom_login POST), add `throttle:5,1` to L111 |
| `app/Http/Controllers/CustomController.php` | **DELETE** |
| `app/Http/Controllers/AdminController.php` | Rewrite `adminLoginPost` (L31-52) + `adminLogOut` (L119-124) to use `Auth::guard('admin')` |
| `app/Http/Middleware/OnlyAdmin.php` | Replace `session()->has('admin_user')` with `Auth::guard('admin')->check()` |
| `Dockerfile` | Add `USER www-data` before `CMD` |
| `resources/views/admin/login.blade.php` | Redesign with admin branding, add Remember Me checkbox, WCAG improvements |
| `resources/views/admin/layouts/admin.blade.php` | **CREATE** — new unified admin layout (sidebar, top bar, tokens) |
| All `resources/views/admin/*.blade.php` | Migrate `@extends('admin.layout')` → `@extends('admin.layouts.admin')` |
| `resources/views/admin/dashboard.blade.php` | Migrate `@extends('layout')` → `@extends('admin.layouts.admin')` |

---

## VERIFICATION STEPS

After all changes:

1. **Test auth:**
   - Try `/custom_register` → should 404
   - Login at `/admin_login` → should redirect to `/admin.dashboard`
   - Access `/adminOrders` without login → should redirect to login
   - Logout → should clear session and redirect to home

2. **Test throttle:**
   - Fail login 6 times in 60s → should see "Too many login attempts"

3. **Test Remember Me:**
   - Check Remember Me → close browser → reopen → should still be logged in

4. **Docker security:**
   - `docker exec <container> whoami` → should print `www-data`, not `root`

5. **Dependency audit:**
   - `composer update dompdf/dompdf` (from <3.1.6 to >=3.1.6) to fix 5 CVEs
   - Re-run `composer audit` → should show 0 vulnerabilities for dompdf

---

## SEVERITY SUMMARY

| Finding | Severity | CVSS | Fix Priority |
|---|---|---|---|
| Public admin registration | **CRITICAL** | 9.8 | 1 (blocks deploy) |
| Session-array auth (no regeneration) | **HIGH** | 7.5 | 2 |
| No login throttling | **MEDIUM** | 5.3 | 3 |
| Docker root user | **HIGH** | 7.2 | 2 (Render-specific) |
| dompdf CVEs (file read/DoS) | **MEDIUM** | 5.5 | 4 |
| Duplicate controllers | LOW | — | 5 (cleanup) |

**Blocking for production:** 1, 2, 4.

---

## NEXT STEPS

User to decide:
- **Option A (recommended):** Delete `/custom_register` routes entirely (safest)
- **Option B:** Keep but guard behind admin middleware + `ALLOW_ADMIN_REGISTRATION=false` env flag (for dev seeding only)

Once confirmed, I'll implement Phase 1–5 in sequence.
