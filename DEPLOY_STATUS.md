# 🚨 URGENT: Security Fixes Not Deployed

**Status:** Three security-hardening commits pushed to GitHub but **NOT live** on Render.

---

## Evidence

Live service `https://busbooking-fyhg.onrender.com` still serves old code:

✅ **Confirmed vulnerability still exploitable:**
- `/custom_register` returns **200** with working Admin Register form
- This is the CVSS 9.8 public admin self-registration vulnerability (fixlog B7)
- Anyone can create an admin account with two clicks (load form → submit)

🔍 **Deploy markers:**
- `/pay-via-ajax` returns 500 (should be 404 — route deleted in `68814a4`)
- Security headers absent (X-Frame-Options, etc.)
- Password-cookie fix not live
- CVE patches (symfony/mime, guzzle, laravel) not live

---

## What Was Pushed

| Commit | Date | Changes |
|--------|------|---------|
| `1a5c7dd` | 2026-08-05 10:27 UTC | Patch 4 HIGH CVEs: symfony/mime, http-foundation, guzzle, laravel/framework |
| `7088bdd` | 2026-08-05 10:03 UTC | Add SESSION_SAME_SITE=lax to render.yaml |
| `68814a4` | 2026-08-05 09:52 UTC | Security hardening: plaintext passwords, security headers, CSRF tokens, mass assignment |

All three are on `origin/main` (`git rev-parse HEAD origin/main` = `1a5c7dd`).

---

## Why Render Didn't Deploy

**Unknown.** Auto-deploy either:
1. Is turned off in Render dashboard
2. Failed silently during build
3. Service is pointed at the wrong branch
4. Webhook from GitHub → Render is broken

The Dockerfile and composer.lock are both valid — local `composer install --no-dev` succeeds.

---

## Required Action

**You must manually trigger a deploy from the Render dashboard:**

1. Log into https://render.com
2. Find the `busbooking` service
3. Click **Manual Deploy** → deploy from `main` branch
4. Wait for build to complete (~5-10 min)
5. Verify: `curl -I https://busbooking-fyhg.onrender.com/custom_register` should return **404**

---

## What Remains Exploitable Until Deploy

| Issue | Severity | Status |
|-------|----------|--------|
| Public admin registration (`/custom_register`) | CVSS 9.8 | **LIVE NOW** |
| Plaintext password cookies | Critical | Fixed in code, not deployed |
| Missing security headers | High | Fixed in code, not deployed |
| 4 HIGH CVEs in dependencies | High | Patched in code, not deployed |

---

## Timeline

- **09:52 UTC** — First security commit pushed
- **10:03 UTC** — Second commit pushed
- **10:27 UTC** — CVE patches pushed
- **10:14 UTC** — Verified old container still serving
- **10:37 UTC** — Monitor timeout after 23 min (no new build)
- **11:00+ UTC** — Manual recheck: still old code, `/custom_register` still 200

**Elapsed:** 1+ hour with no auto-deploy.

---

**Recommendation:** Investigate Render auto-deploy settings immediately after manual deploy completes.
