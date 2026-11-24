# Changelog

This project follows [Semantic Versioning](https://semver.org/).

## [1.0.1] - 2026-11-24

Documentation corrections only. No change to code behaviour.

### Changed

- Release notes, changelog, notice, and security audit now reflect the published 1.0.0 release and the 2026-11-24 rewrite of the public Git history, which no longer contains the 2018 files.

## [1.0.0] - 2026-11-24

### Added

- Single front controller with typed request, response, and routing layers.
- Nested `backend/` and `frontend/` structure.
- Unit, integration, and end-to-end tests with committed reports.
- PHPStan analysis and GitHub Actions CI with pinned actions and read-only permissions.
- Optional SQLite schema, architecture guide, security policy, and security audit.

### Changed

- Rewrote the guessing game, MD5 exercise, and Rock Paper Scissors for PHP 8.4+.
- Completed the Rock Paper Scissors rules with a random opponent.
- Replaced Bootstrap 3 from a CDN with a self-hosted stylesheet.

### Security

- Replaced the hardcoded MD5 credential with Argon2id or bcrypt verification.
- Added CSRF protection, login throttling, hardened sessions, and security headers.
- Replaced loose hash comparison with validated, timing-safe comparison.
- Made login throttling atomic under concurrency and grouped IPv6 clients by /64.
- Enforced server-side idle and absolute session timeouts.
- Required a private throttle directory, which blocks symlink file overwrites.
- Replaced displayed errors with a generic 500 response and removed `X-Powered-By`.
- Rejected NUL bytes and bcrypt input beyond 72 bytes.

### Removed

- `rps.zip` and the original single-file scripts.

## Pre-1.0 - 2018

- Original *Web Applications for Everybody* coursework.
