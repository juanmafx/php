# Changelog

This project follows [Semantic Versioning](https://semver.org/).

## [1.0.0] - Unreleased

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

### Removed

- `rps.zip` and the original single-file scripts, which remain in Git history.

## Pre-1.0 - 2018

- Original *Web Applications for Everybody* coursework.
