# Security Audit

Audit of the 2018 code and the 1.0.0 rewrite, performed in November 2026. This was a manual code review supported by automated tests, PHPStan, and `composer audit`. It was not an independent penetration test. Credential values are intentionally omitted.

## Fixed

| Severity | Area | Finding | Resolution |
| --- | --- | --- | --- |
| High | Authentication | Hardcoded credential hash using salted MD5 | Removed; operator-supplied Argon2id or bcrypt hash, fails closed |
| High | Authentication | Password hint exposed in page source | Removed |
| High | Authorization | Game access relied only on a `name` query parameter | Server-side session required |
| Medium | Hash comparison | Loose `==` comparison of hashes | Strict format validation and `hash_equals` |
| Medium | CSRF | State-changing forms had no token | Per-session token on every POST, rotated at login |
| Medium | Brute force | No login throttling | File-locked per-client throttle |
| Medium | Session | No session hardening | Strict mode, `HttpOnly`, `SameSite=Strict`, ID regeneration |
| Medium | Headers | No browser security headers | CSP, HSTS on HTTPS, and related headers |
| Medium | Password field | Password rendered as a plain text input | `type="password"` with autocomplete hints |
| Low | Supply chain | End-of-life Bootstrap 3 loaded from a CDN | Replaced with a self-hosted stylesheet |
| Low | Repository | Source committed as a zip archive and a file name containing a query string | Replaced with reviewable source files |
| Low | Input handling | Guess page read its parameter before checking it exists, and array input was not handled | Typed request accessors that accept only strings |
| Low | Maintainability | Untyped procedural code | PHP 8.4+, strict types, PHPStan at max level |

## Reviewed, no defect found

The 2018 pages already escaped the reflected guess, name, and result values. The rewrite keeps that behavior and adds regression tests.

## Open

| Severity | Item | Status |
| --- | --- | --- |
| Low | Removed files, including a published course demo credential, remain in Git history | History was not rewritten. The value is a public course sample and must never be reused |
| Low | No multi-factor authentication | Not implemented; the app uses a single demo login |
| Low | Throttle is keyed by client address | A shared address shares a bucket, and distributed attempts need edge rate limiting |
| Low | `/hash-recovery` is unauthenticated | Work per request is bounded; edge rate limiting is recommended if hosted |
| Info | Repository license for the 2026 changes is undeclared | Awaiting an owner decision |

## Verification

Test reports are in [reports/tests/](../reports/tests/). Dependency audit results reflect the advisory data available on the audit date and require ongoing monitoring.
