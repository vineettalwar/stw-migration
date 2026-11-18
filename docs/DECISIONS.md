# Decisions

## 2026-11-18: Preserve supplied GPL v3-or-later license
Repository LICENSE and source headers specify GPL v3, despite the initial
GPL v2 assumption. Keep original notices, document provenance and changes.

## 2026-11-18: Branding fork with retained migration internals
Use a unique slug/entrypoint and block coexistence with upstream; retain
ai1wm hooks/options/archive format to avoid untested migration rewrites.
Disable upstream updater/support routes. Separate full security review and
migration compatibility validation remain necessary before production use.

## 2026-11-18: Native WordPress workspace
User selected modern app-style UX inside wp-admin. Preserve the tested pipeline
and use native HTML/CSS with a small keyboard helper, without adding a frontend
framework or external service. Hide secondary backup options behind disclosure.

## 2026-11-18: Preserve archive version independently
Changing AI1WM_VERSION to the new fork release triggered a pre-4.10 database
conversion path during restore. Keep AI1WM_VERSION at 6.77 and expose the fork's
release through STW_MIGRATION_VERSION. Repeated clean round-trip verified.

## 2026-11-18: Authentication across restore and whole-job serialization
Require admin/nonce for initiation and deletion; retain bearer continuation only
for an authorized expiring job/archive so replacing users cannot strand restore.
A per-site file lease prevents interleaved browser and CLI migrations.

## 2026-11-18: wp-env and initial repository
Use source/destination wp-env environments for regression tests. Store plugin
in stw-migration/ and development tooling at root; package only the plugin folder.
Keep GPL/provenance; publish the initial snapshot to the user-provided repository.
