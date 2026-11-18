# Handoff — 2026-11-18

Implemented STW Migration 1.0.0-beta.1 from GPL-3.0-or-later upstream 6.77.
Added native WordPress app-style backup/restore/library workspace; hardened
paths, archive extraction, metadata, object rewriting, chunk retries, job
permissions/leases, authenticated downloads and uninstall. Removed 49 unused
upstream provider/support/update files. Public frontend skips the engine.

wp-env runs source localhost:8897 and destination localhost:8898 (admin/password).
58 security regressions, eight cross-site restore invariants, browser export/
upload/restore, deactivate/reactivate and HTTP access controls passed. npm audit
is clean. ZIP builds with scripts/package.py; artifacts and runtime state ignored.

Initial repository snapshot targets github.com/vineettalwar/stw-migration, main.
Original upstream Git metadata is preserved at /tmp/stw-migration-upstream-git-20261118;
provenance and licenses are committed in source/docs.

Read docs/AUDIT.md before deployment: Nginx needs explicit static deny rules;
large archives, Windows, multisite/extensions, cache-hosting matrix and abrupt
process termination remain unverified. Restores are incremental, without rollback.
Next: expand compatibility/fault-injection testing before production release.
