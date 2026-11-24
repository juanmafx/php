# Release Notes: 1.0.1

Documentation-only patch release. Code behaviour is unchanged from 1.0.0.

## Changes

- The changelog, notice, security audit, and these notes now reflect that 1.0.0 is published and that the public Git history was rewritten on 2026-11-24. The 2018 single-file scripts and `rps.zip` are no longer in any branch or tag.
- The audit item about removed files in Git history is marked resolved. The old course demo credential must still be treated as compromised and never reused.

## Upgrade notes

No action is needed when upgrading from 1.0.0. The breaking changes from the 2018 code, including the PHP 8.4.1 requirement and `PHP_LABS_PASSWORD_HASH`, are listed in [CHANGELOG.md](CHANGELOG.md) and the README.

## Known limitations

See the Open section of [docs/SECURITY_AUDIT.md](docs/SECURITY_AUDIT.md).
