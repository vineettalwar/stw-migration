# Handoff — 2026-11-18

STW Migration 1.2.0-beta.1: independent admin layer and Settings added.
Stw_Admin owns canonical STW page routes and adapts engine assets. Old ai1wm
bookmarks redirect at admin_menu priority 0, before WordPress access validation.
Stw_Settings uses the Settings API for optional spam/revision/cache exclusions;
defaults preselect browser backup options and remain overridable. CLI unchanged.
Dot/hostname and eyebrow are absent. Vendor artwork/promotions removed; original
copyright/GPL lines verified intact. Engine internals still retain ai1wm names.

102 assertions pass (61 security, 34 native format, seven settings). New modules
pass PHPCS. Browser settings save/default propagation/reset and route redirects
verified. Native format remains .stwm with .wpress import support. wp-env:
source 8897, destination 8898, admin/password. Build scripts/package.py reads
version from header; runtime and archives are ignored by Git.

Read docs/ARCHIVE-FORMAT.md and AUDIT.md: native stages add I/O and are not yet
time-sliced for very large archives. PHP ZIP, 64-bit PHP and DB advisory locks
required. Nginx needs static deny rules. No production/large-host certification.

Latest steering: displayed plugin Author is STW; upstream copyright/GPL notices
remain. docs/ROADMAP.md proposes next-release features with multisite as P0 across
whole-network restore and individual-site transfers in both directions. This is
a proposal, not implemented multisite support; current guards remain enabled.
