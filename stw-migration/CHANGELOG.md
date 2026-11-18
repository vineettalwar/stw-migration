# STW Migration changes

## 1.2.0-beta.1 — 2026-11-18

- Display STW as the fork author; retain upstream copyright and GPL notices.

- Add an STW-owned admin controller, canonical STW routes and compatible redirects.
- Add Settings for default exclusions of spam comments, revisions and cache files;
  keep per-backup overrides and verify Settings API sanitization/persistence.
- Remove hostname/status dot, legacy header artwork and vendor purchase prompts;
  retain all original copyright and license notices.
- Keep migration compatibility actions internal; add no frontend framework.

## 1.1.0-beta.1 — 2026-11-18

- Introduce real .stwm ZIP/ZIP64 containers with versioned manifests and per-file
  SHA-256 checksums; preserve legacy .wpress imports through the existing parser.
- Verify native payloads before passing data to the tested restore pipeline.
- Add format/inventory/version/path/integrity tests and both-format round trips.
- Serialize native packaging/verification against overlapping retries.
- Centralize archive-name validation and split archive, job and transfer concerns
  into explicit modules; reject malformed or symlinked job metadata.
- Replace patched/minified workspace CSS with readable, consolidated rules; use
  understated colors, compact typography, simple labels and no decorative hero.
- Read the release version from the plugin header when packaging.

## 1.0.0-beta.1 — 2026-11-18

- Fork All-in-One WP Migration 6.77 and retain GPL-3.0-or-later, original notices
  and bundled licenses. Source revision: 2919b1b1c3e5bc7ee9ba61d8effcea2498859d22.
- Rename plugin entrypoint, slug, text domain and product to STW Migration.
- Keep archive format version separate from the fork release version.
- Add responsive WordPress admin workspace for backup, restore and library;
  progressive backup options, accessible labels and modal keyboard handling.
- Require admin/nonce to start jobs/delete backups; bind continuations to an
  authorized archive with expiration; block overlapping browser/CLI jobs.
- Reject storage traversal, unsafe archive paths, symlinks, malformed headers,
  misplaced footers, invalid resume offsets and malformed package metadata.
- Make chunk upload retries idempotent and detect conflicting/out-of-order writes.
- Rewrite serialized database strings without instantiating PHP classes.
- Serve downloads through authenticated WordPress authorization and protect local
  archives/staging on Apache/IIS. Nginx needs explicit server rules.
- Remove upstream feedback/report/update routes and unused provider views/assets.
- Skip engine loading on the public frontend, use native 64-bit file statistics,
  simplify backup listing, reduce request logging and bound AJAX retries.
- Fix PHP iterator deprecations, fail on I/O errors, preserve upstream options on
  uninstall when the original plugin is active, and guard direct entrypoint access.

This is a testing release. See the repository audit and test guide for limits.
