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
| Medium | Brute force | No login throttling | File-locked per-client throttle that reserves each attempt atomically before verification |
| Medium | Brute force | First 1.0.0 draft checked and recorded attempts separately, so 40 parallel requests reached password verification 16 times against a limit of 5 | Atomic reservation; the same probe now reaches verification 5 times |
| Medium | Brute force | IPv6 clients could rotate addresses inside one /64 to avoid the limit | IPv6 clients share a /64 bucket, and IPv4-mapped addresses share the IPv4 bucket |
| Medium | Local file overwrite | A pre-created throttle directory in shared temp storage could redirect state writes through a symlink | Directory must be private, owned by the application user, and not a symlink |
| Medium | Session | No session hardening, and draft expiry relied on probabilistic garbage collection | Strict mode, `HttpOnly`, `SameSite=Strict`, ID regeneration, and server-enforced 30-minute idle and 8-hour absolute timeouts |
| Medium | Information disclosure | Uncaught errors could display paths and stack traces | Display disabled, generic 500 response, minimal logging, `X-Powered-By` removed |
| Low | Credentials | bcrypt ignored input past 72 bytes or after a NUL byte, so a different password could match | Such input is rejected at verification and by `hash-password` |
| Medium | Headers | No browser security headers | CSP, HSTS on HTTPS, and related headers |
| Medium | Password field | Password rendered as a plain text input | `type="password"` with autocomplete hints |
| Low | Supply chain | End-of-life Bootstrap 3 loaded from a CDN | Replaced with a self-hosted stylesheet |
| Low | Repository | Source committed as a zip archive and a file name containing a query string | Replaced with reviewable source files |
| Low | Input handling | Guess page read its parameter before checking it exists, and array input was not handled | Typed request accessors that accept only strings |
| Low | Maintainability | Untyped procedural code | PHP 8.4+, strict types, PHPStan at max level with iterable value types excluded |

## Reviewed, no defect found

The 2018 pages already escaped the reflected guess, name, and result values. The rewrite keeps that behavior and adds regression tests.

## Open

| Severity | Item | Status |
| --- | --- | --- |
| Low | No multi-factor authentication | Not implemented; the app uses a single demo login |
| Low | Throttle is keyed by client address | A shared address or IPv6 /64 shares a bucket, and distributed attempts need edge rate limiting |
| Low | Throttle state files are not pruned | One small file remains per client bucket; prune the directory periodically if hosted |
| Low | `/hash-recovery` is unauthenticated | Work per request is bounded; edge rate limiting is recommended if hosted |
| Info | Repository license for the 2026 changes is undeclared | Awaiting an owner decision |

## Resolved after the audit

| Severity | Item | Resolution |
| --- | --- | --- |
| Low | Removed files, including a published course demo credential, remained in Git history | Resolved by the 2026-11-24 history rewrite: pre-2026 commits are no longer in any branch or tag. Old commits may remain reachable by direct SHA on GitHub until unreferenced objects are purged. The value is a public course sample, must be treated as compromised, and must never be reused |

## Verification

Test reports are in [reports/tests/](../reports/tests/). Dependency audit results reflect the advisory data available on the audit date and require ongoing monitoring.
