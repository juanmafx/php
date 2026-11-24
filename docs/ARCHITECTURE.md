# Architecture

## Overview

The application is a server-rendered PHP monolith with no runtime Composer dependencies. Requests enter through one front controller and flow one way:

```text
Browser -> backend/public/index.php -> Http\Application -> Controller -> Domain
                                              |                |
                                          Security        View -> frontend/templates
```

## Module structure

```text
backend/src/
  Domain/
    Guess/               GuessEvaluator, GuessOutcome
    HashRecovery/        Md5PinRecovery, RecoveryResult
    RockPaperScissors/   Move, RoundJudge, RoundOutcome, MoveSource, RandomMoveSource
  Http/
    Application.php      Route table, CSRF enforcement, security headers
    Request.php          Typed access to input; rejects non-string values
    Response.php         Immutable response value
    Controller/          Auth, Game, Guess, and HashRecovery controllers
  Security/
    CredentialVerifier   Argon2id or bcrypt verification, fails closed
    CsrfGuard            Per-session token with rotation at login
    LoginThrottle        File-locked failure counter per client address
    NativeSessionStore   Lazy, hardened PHP sessions
    SecurityHeaders      CSP and related response headers
  View/
    TemplateRenderer     Isolated template scope with an escaping helper
frontend/
  assets/app.css
  templates/
```

## Design rules

- `Domain` depends on no other module and has no I/O, so it is tested directly.
- Controllers translate between HTTP and the domain and hold no game rules.
- `SessionStore` and `MoveSource` are interfaces so tests can run deterministically.
- Only `backend/public` is web-accessible. Source, configuration, and templates stay outside it.
- Sessions start lazily, so public pages set no cookies.

## Frontend and backend separation

`frontend/` holds presentation only: templates and CSS, with no JavaScript. `backend/` holds all behavior. The backend serves the stylesheet through a fixed route so the document root can remain limited to the front controller.

## Persistence

The application is stateless apart from PHP sessions and login throttle files. `db/schema.sql` is an optional SQLite design and is not used by the running application.

## Recommended deployment

This configuration has not been deployed by this project. For a real deployment:

1. Serve PHP-FPM behind nginx or Caddy with TLS, and set the document root to `backend/public`.
2. Rewrite every request to `index.php`.
3. Run `composer install --no-dev --no-scripts --no-plugins --classmap-authoritative`.
4. Provide `PHP_LABS_PASSWORD_HASH` through a secret manager.
5. Keep the throttle directory private and persistent.
6. Pass the HTTPS state through FastCGI. The application deliberately ignores `X-Forwarded-*` headers, so behind a reverse proxy the proxy must set the real client address (for example with nginx `real_ip`), or all clients share one throttle bucket.
7. Add edge rate limiting for `/login` and `/hash-recovery`.
