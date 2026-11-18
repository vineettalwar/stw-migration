# STW Migration 1.2.0-beta.1

Create a site backup, restore an STW or legacy WPress file, and manage local backups from an
app-style WordPress admin workspace. PHP 8.1+ required. Native .stwm archives require 64-bit PHP and the ZIP extension. Testing beta.

Install this folder as `wp-content/plugins/stw-migration` or upload the packaged
ZIP. Deactivate All-in-One WP Migration and its extensions first. Open STW
Migration in WordPress admin. Options include text replacement and choosing
which content to exclude from a backup. A restore replaces current site data;
keep a separate backup and use only trusted archives.

New jobs/deletion require administrator authorization and a nonce. Concurrent
migration jobs are blocked. Active browser continuations can finish after
restored users replace the login session; job authorization expires after 24h.
Archives and staging files require private server access: generated .htaccess
and web.config handle Apache/IIS; Nginx needs explicit deny rules for both
wp-content/ai1wm-backups and this plugin's storage directory. An absolute private
final backup directory can be set using STW_MIGRATION_BACKUPS_PATH in wp-config.

Source, reproducible wp-env tests, and deployment details:
https://github.com/vineettalwar/stw-migration

Based on All-in-One WP Migration 6.77 from
https://github.com/dev071/all-in-one-wp-migration-unlimited at commit
2919b1b1c3e5bc7ee9ba61d8effcea2498859d22. The supplied source is GPL-3.0-or-later,
not GPL v2. Original copyrights and vendor licenses are retained. Independent
fork, not affiliated with or supported by ServMask. See CHANGELOG.md for dated
modifications. Internal ai1wm identifiers, options and .wpress format remain.

Runtime tested on WordPress 7.1.2/PHP 8.3.35. Multisite, extensions, >2 GB
archives, Windows and modern exporter compatibility remain unverified. Custom
serialized object payloads are preserved without rewriting their internals.
Symlinks are excluded; backup files must be in the backup directory itself.
Abruptly interrupted restores are not automatically rolled back.

Native STW format v1 uses standard ZIP/ZIP64, a versioned manifest and SHA-256
file checksums. New exports use .stwm; .wpress imports remain supported. Native
verification adds staging I/O and is not yet time-sliced for very large archives.
The format specification and limitations are in the repository docs.
