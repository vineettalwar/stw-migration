# STW Migration

A focused WordPress workspace for creating a portable site backup, restoring a
.wpress archive, and managing local backups. Runs inside WordPress admin with no
cloud account, external fonts, frontend framework, or JavaScript build step.

**Testing beta: 1.0.0-beta.1.** Use disposable sites while evaluating migrations.
Requires PHP 8.1+; verified on WordPress 7.1.2 and PHP 8.3.35.

## Install

Run `python3 scripts/package.py`, then upload the generated
`dist/stw-migration-1.0.0-beta.1.zip` in WordPress → Plugins → Add Plugin.
The installable plugin lives in `stw-migration/`; the repository root contains
development tools and is not itself an installable plugin.
Deactivate All-in-One WP Migration and its extensions before activating STW.

## Develop and test

Docker, Node.js/npm, and Python 3 are required. Node 24.18+ and npm 11.16+ are
recommended by the current wp-env transitive packages; the Docker workflow was
also exercised on Node 24.10/npm 11.6 with engine warnings for unused WASM tools.

```sh
npm ci
npm run env:start
npm run test:security
npm run test:roundtrip
npm run package
```

Source: http://localhost:8897/wp-admin/
Destination: http://localhost:8898/wp-admin/
Both use wp-env's disposable default credentials: `admin` / `password`.
The round-trip test replaces the destination database and media. Never point
these tests at a real site. See [TESTING.md](TESTING.md) for details.

## Security and compatibility

New web migration jobs and backup deletion require administrator permission and
a nonce. Jobs are serialized per site, bound to an archive, and expire after
24 hours. Authorized continuation requests can finish after a restored database
replaces WordPress login sessions. Downloads pass through WordPress permission
checks. Archive extraction rejects traversal and symlinks; upload retries do not
duplicate chunks; serialized database replacements do not instantiate classes.

Apache and IIS deny rules protect temporary data and archives. **Nginx requires
explicit deny rules**, or an administrator-configured private backup path; see
[docs/AUDIT.md](docs/AUDIT.md). PHP cannot enforce Nginx static-file permissions.
Use only trusted archives: a site restore intentionally installs code and SQL.

This beta is not a claim that every hosting setup or archive is safe. Multisite,
third-party extensions, >2 GB archives, forced process termination, and Windows
hosting remain outside the verified matrix. See the audit for exact limits.

## Provenance and license

Based on All-in-One WP Migration 6.77, fetched from
https://github.com/dev071/all-in-one-wp-migration-unlimited at
`2919b1b1c3e5bc7ee9ba61d8effcea2498859d22`.
The supplied license and source headers are **GPL-3.0-or-later**, not GPL v2.
Original copyright notices and third-party licenses are retained. STW Migration
is an independent fork, not affiliated with or supported by ServMask.

See [stw-migration/CHANGELOG.md](stw-migration/CHANGELOG.md) for dated changes.
The internal archive version stays at 6.77 to preserve format/conversion rules;
STW's release version is tracked separately.
