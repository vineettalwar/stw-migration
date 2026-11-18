# Handoff — 2026-11-18

STW Migration 1.1.0-beta.1: understated admin workspace and code-quality pass.
New exports are real .stwm ZIP/ZIP64 containers with manifest v1 and SHA-256
per-file integrity. Legacy .wpress import support remains. Native imports verify
all payloads into private staging before using the established restore pipeline.

Security split into explicit lib/stw modules; shared filename policy, bounded
job-state checks and symlink rejection. MySQL/MariaDB advisory locks coordinate
workspace, uploads and native stages across workers; independent session test
passed. Filesystem flock alone was unreliable on the local bind mount.

Verified: 61 security assertions, 34 native-format assertions, native and legacy
cross-site restores (eight data checks each), original pre-native WPress restore,
45 MB full native browser export, native browser upload/restore, mobile 390px,
PHP/JS syntax and scoped PHPCS for new modules. wp-env: 8897/8898 admin/password.
Package: scripts/package.py reads version from header. Runtime/artifacts ignored.

See docs/ARCHIVE-FORMAT.md and AUDIT.md: native packaging/verification adds I/O
and is not yet time-sliced for very large archives; ZIP, 64-bit PHP, and database
advisory locks required. Archive checksums are not signatures/encryption. Nginx
still requires static deny rules. No production/large-host compatibility claim.
Next: large-file, crash/proxy timeout and deployment matrix before stable release.
