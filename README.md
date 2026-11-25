# php-web-exercises

Three classic PHP web exercises rebuilt as one small, typed, security-hardened application for PHP 8.4+.

> **Project history:** originally created in 2018 as coursework for *Web Applications for Everybody*, modernized in 2026. The exercises are course-derived; see [NOTICE.md](NOTICE.md).

## Purpose

This project shows how small procedural course scripts can be restructured into a maintainable application with explicit boundaries, strict typing, automated tests, and defensive HTTP handling. It is a learning and portfolio project. It is not a production service.

## Why this project exists

The 2018 versions were single-file exercises that mixed input handling, logic, and HTML. They used a hardcoded MD5 credential, loose hash comparison, and a CDN-hosted Bootstrap 3 stylesheet. The rewrite keeps each exercise recognizable and replaces those patterns with current practice.

## What it does

| Route | Feature | Behavior |
| --- | --- | --- |
| `/` | Home | Links to each exercise |
| `/guess` | Guessing Game | Validates a numeric guess against the fixed target 42 |
| `/hash-recovery` | MD5 PIN Recovery | Searches the 10,000 four-digit PINs for a matching MD5 digest, demonstrating why fast unsalted hashes are unsuitable for credentials |
| `/login` | Log in | Authenticates against one operator-configured password hash, with CSRF protection and throttling |
| `/game` | Rock Paper Scissors | Login-protected game against a random opponent, with an all-outcomes view |
| `/logout` | Log out | Destroys the session (POST only) |

## Architecture

```text
backend/
  bin/hash-password   Creates a password hash from standard input
  public/index.php    Front controller and only web entry point
  src/
    Domain/           Framework-free game and hash recovery logic
    Http/             Request, response, routing, and controllers
    Security/         Sessions, CSRF, credentials, throttling, headers
    View/             Escaping template renderer
frontend/
  assets/app.css      Self-hosted stylesheet
  templates/          Server-rendered HTML views
db/schema.sql         Optional SQLite schema
tests/                Unit, integration, and end-to-end suites
reports/tests/        Committed reports from real test runs
```

Details are in [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Quick start

Requirements: PHP 8.4.1 or newer with `mbstring` and `session`, plus Composer. The test suite also needs `dom`, `xml`, `xmlwriter`, and `pdo_sqlite`.

```bash
composer install --no-scripts --no-plugins
IFS= read -rsp 'Password: ' LABS_PASSWORD; echo
export PHP_LABS_PASSWORD_HASH="$(printf '%s' "$LABS_PASSWORD" | php backend/bin/hash-password)"
unset LABS_PASSWORD
php -S 127.0.0.1:8080 -t backend/public backend/public/index.php
```

`hash-password` reads the password from standard input and accepts 12 to 4,096 bytes, or 12 to 72 bytes when PHP lacks Argon2id and it falls back to bcrypt. Open <http://127.0.0.1:8080>.

The built-in PHP server is for local development only.

## Usage guide

1. **Guessing Game:** open `/guess`, enter a number, and follow the higher or lower feedback.
2. **MD5 PIN Recovery:** open `/hash-recovery` and paste the MD5 digest of a four-digit PIN. For example, generate one with `printf 1234 | md5sum`.
3. **Rock Paper Scissors:** open `/login`, enter any display name with the configured password, then choose a move. **Show all outcomes** prints the full result matrix.

## Configuration

| Variable | Required | Description |
| --- | --- | --- |
| `PHP_LABS_PASSWORD_HASH` | For login | Argon2id or bcrypt hash. Without a valid hash, login stays disabled |
| `PHP_LABS_THROTTLE_DIR` | No | Directory for login throttle state. Defaults to a folder in the system temp directory. It must be owned by the application user with no group or other access (mode `0700`), or requests fail with a 500 |

The application reads these from the process environment. It does not load `.env` files.

## Database

The application **does not use a database**, and [db/schema.sql](db/schema.sql) is **optional**. It provides a small SQLite schema for anyone who wants to record players, rounds, guesses, and recovery runs:

```bash
sqlite3 php-labs.db < db/schema.sql
```

## Testing

```bash
composer test
composer analyse
```

Without local PHP extensions, run the suite in the official image:

```bash
docker run --rm --network none --user "$(id -u):$(id -g)" -v "$PWD:/app" -w /app php:8.5-cli vendor/bin/phpunit
```

Reports from actual runs on PHP 8.4 and 8.5 are in [reports/tests/](reports/tests/). [The CI workflow](.github/workflows/ci.yml) defines validation, dependency audit, PHPStan, and PHPUnit jobs for both versions.

## Security notes

- Passwords are verified with Argon2id or bcrypt, and login fails closed when unconfigured.
- All POST routes require a CSRF token, and session cookies are `HttpOnly` and `SameSite=Strict`.
- Login allows five attempts per client per 15-minute window, counted atomically. IPv6 clients are grouped by /64.
- Sessions expire after 30 idle minutes and 8 hours in total, enforced on the server.
- Internal errors return a generic 500 response and are written to the PHP error log without request data.
- Dynamic output goes through a central escaping helper, and a strict Content Security Policy blocks scripts.
- The MD5 tool is intentionally limited to four-digit PINs and exists for education.

See [SECURITY.md](SECURITY.md) and [docs/SECURITY_AUDIT.md](docs/SECURITY_AUDIT.md).

## Project history

Originally created in 2018 as *Web Applications for Everybody* coursework, modernized in 2026. See [CHANGELOG.md](CHANGELOG.md).

## License and attribution

Course-derived material is credited in [NOTICE.md](NOTICE.md), and its upstream BSD-2-Clause license is in [third_party/wa4e/LICENSE](third_party/wa4e/LICENSE). This repository does not currently declare a separate license for the 2026 changes.
