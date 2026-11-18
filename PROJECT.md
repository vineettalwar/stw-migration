# STW Migration

Independent GPL-3.0-or-later fork of All-in-One WP Migration 6.77; source
revision 2919b1b1c3e5bc7ee9ba61d8effcea2498859d22. Product: 1.2.0-beta.1.
Repository: https://github.com/vineettalwar/stw-migration (main).

## Map
- stw-migration/stw-migration.php: bootstrap, frontend early return, UI assets.
- stw-migration/stw-security.php: explicit loader for lib/stw/archive-security.php,
  jobs.php and transfers.php. Shared filename policy, bounded state, database
  advisory locks, safe uploads/downloads and serialized replacements.
- stw-migration/lib/stw/class-stw-archive.php: ZIP/ZIP64 manifest v1, checksums,
  verified adapter; new exports use .stwm, legacy .wpress imports remain.
- stw-migration/lib/controller + model + vendor: retained migration pipeline.
- stw-migration/lib/view: native app-style admin workspace and legacy progress JS.
- tests/: security regressions, fixture/transfer helpers; .runtime is ignored.
- scripts/: ZIP packager and cross-site wp-env round-trip runner.
- .wp-env.json/package-lock.json: reproducible local test setup.
- docs/AUDIT.md: findings, evidence, server requirements and test limits.

## Conventions
Preserve original notices. Internal ai1wm classes/hooks/options remain; do not
activate alongside upstream/extensions. AI1WM_VERSION is archive format 6.77;
STW_MIGRATION_VERSION is the product version. Keep admin controls local and
framework-free; no frontend engine loading. New jobs require admin/nonce;
continuations use secret + expiring archive-bound job state. Preserve this to
finish restoring after login sessions are replaced. Job lease covers browser/CLI.

## Verification and maintenance
npm ci; npm run env:start; npm run test:security; npm run test:roundtrip.
Build: npm run package. ZIP is in dist/ and excluded from Git. Test endpoints
localhost:8897/8898, wp-env default admin/password. The previous Compose test
stack was stopped; wp-env is canonical. 61 security and 34 native assertions pass; native and legacy restores each
pass eight cross-site data checks. New modules pass the scoped PHPCS ruleset. Browser export/upload/restore and HTTP guards
verified. Public frontend loads one plugin file and skips engine. No speed %
claim. See TESTING.md and audit for precise verification and remaining limits.

Skills used: wp-plugin-development, wordpress-security-validation,
fable-methodology; frontend-design and agent-browser for the workspace/QA.

Native format: docs/ARCHIVE-FORMAT.md. Requires ZIP and 64-bit PHP; MySQL/MariaDB
advisory locks coordinate across workers. Large native conversion stages are not
yet time-sliced. User requested understated UI: consolidated readable CSS, compact
titles, muted colors and no marketing/illustrative hero.

1.2 admin ownership: Stw_Admin registers canonical STW routes, engine asset
adapters and early admin-menu legacy redirects. Stw_Settings owns the Settings
API option and default checkbox selection. Defaults are UI-only and optional.
The old controller no longer registers menus. Original legal text is retained;
trademark header artwork and vendor promotions were removed. New admin routes
are stw_backup/stw_restore/stw_library/stw_settings. Settings adds seven passing
checks to the prior 95. No dot/hostname/eyebrow in the live header.

Next-release proposals: docs/ROADMAP.md. Multisite is a user-mandated core
requirement (whole network and individual-site ↔ single-site/network transfers).
Not implemented or enabled in the current beta. Displayed Author is STW; original
copyright/GPL/third-party notices and provenance remain preserved.
