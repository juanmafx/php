# Release Notes: 1.0.0

**Status:** prepared, not tagged or published.

Version 1.0.0 is the first versioned release. It rebuilds the 2018 course exercises as one typed PHP 8.4+ application.

## Highlights

- Guessing game, MD5 PIN recovery demo, and a complete Rock Paper Scissors game.
- Clear `backend/` and `frontend/` separation with no runtime Composer dependencies.
- Argon2id or bcrypt login, CSRF protection, throttling, hardened sessions, and a strict CSP.
- Automated tests and PHPStan, verified on PHP 8.4 and 8.5.

## Breaking changes

- Requires PHP 8.4.1 or newer.
- Old `.php` URLs are replaced by the routes listed in the README.
- Login requires `PHP_LABS_PASSWORD_HASH`. The old course credential no longer works.
- The web document root must be `backend/public`.

## Known limitations

See the Open section of [docs/SECURITY_AUDIT.md](docs/SECURITY_AUDIT.md).
