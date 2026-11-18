# Testing STW Migration

## wp-env

`npm ci` installs the locked development toolchain. Start Docker, then run
`npm run env:start`. The configuration mounts the plugin and test scripts into
isolated source and destination WordPress sites on ports 8897 and 8898.
WordPress is pinned to 7.1.2; PHP to 8.3. Use admin/password only on these local
sites. wp-env's native database ports are dynamically assigned.

`npm run test:security` runs archive, symlink, path, authentication, upload retry,
serialized-data, metadata and job concurrency regressions. It creates temporary
files and restores the migration secret it changes during testing.

`npm run test:roundtrip` creates a Unicode post, media fixture and serialized
option in the source; exports database/media; transfers the archive; adds data
to the destination; restores there; checks all eight invariants. It intentionally
replaces the destination database. Runtime fixtures live in tests/.runtime and
are excluded from Git and the plugin ZIP.

`npm run env:stop` stops this project's environments. `wp-env destroy` removes
their data; do that only when you intend to delete these disposable sites.

## Observed results (2026-11-18)

- PHP syntax checks across plugin and regression files.
- 58 security/integrity assertions passed.
- Cross-site CLI restore: all eight content/data assertions passed; clean output.
- Browser export: completed with a 52 MB full archive and a download action.
- Browser upload and restore: explicit confirmation and successful completion.
- Authenticated download: HTTP 200, 150,085 bytes in the checked fixture.
- Anonymous export, direct archive URL, deletion without nonce: HTTP 403.
- Deactivate/reactivate: passed, plugin active in a fresh process.
- Mobile at 390 px: document width 390 px, no horizontal overflow on export.
- Public frontend: one plugin file loaded, migration engine not loaded.
- Full npm audit: zero known advisories with the committed dependency overrides.

Screenshots and browser checks are manual session evidence, not committed E2E
assertions. The automated round-trip intentionally excludes plugins/themes to
avoid replacing bind-mounted development code. Full browser export included
plugins/themes; full cross-host plugin/theme restore is not yet certified.

## Manual checklist

Test the backup options and text replacements, keyboard access, cancellation,
empty/malformed uploads, backup download/delete, and restoration into a second
site. After restore, sign in using the source account if prompted, check media,
settings, pages, plugins, themes, and permalinks. Review WordPress/PHP logs.

A second migration is rejected while a job is active. Finish or stop the first
job. If a process crashed and its browser cannot cancel, confirm no worker is
running before an administrator removes that job's storage subdirectory; the
lease becomes reusable when its marker is gone. Jobs otherwise expire after
24 hours. This is not an automatic rollback mechanism.
