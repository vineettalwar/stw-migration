# STW Migration

Independent GPL-3.0-or-later fork of All-in-One WP Migration 6.77; source
revision 2919b1b1c3e5bc7ee9ba61d8effcea2498859d22. Product: 1.0.0-beta.1.
Repository: https://github.com/vineettalwar/stw-migration (main).

## Map
- stw-migration/stw-migration.php: bootstrap, frontend early return, UI assets.
- stw-migration/stw-security.php: safe paths, job authorization/serialization,
  upload idempotency, safe serialized replacements, download and package checks.
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
stack was stopped; wp-env is canonical. 58 security assertions and eight
cross-site restore checks pass. Browser export/upload/restore and HTTP guards
verified. Public frontend loads one plugin file and skips engine. No speed %
claim. See TESTING.md and audit for precise verification and remaining limits.

Skills used: wp-plugin-development, wordpress-security-validation,
fable-methodology; frontend-design and agent-browser for the workspace/QA.
