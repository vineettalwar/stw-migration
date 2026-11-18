# STW Migration changes

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
