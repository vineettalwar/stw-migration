# Audit and verification — 2026-11-18

This is a targeted code audit and remediation pass of an old migration engine,
not a penetration-test certificate or an assertion that every edge case is fixed.
The initial graph indexed 138 PHP files, 658 nodes, and 4,777 relationships.
Review focused on controller entrypoints, filesystem paths, upload/extraction,
SQL value rewriting, lifecycle, update/support integrations, and admin assets.

| Finding | Remediation | Evidence |
| --- | --- | --- |
| Permanent secret alone authorized new jobs and deletion | Admin + nonce for new jobs/deletion; per-archive continuation marker and 24-hour expiration | Regression suite and HTTP 403 checks |
| Requests could overlap unrelated migrations | Private job lease metadata with a MySQL/MariaDB advisory mutex; shared by browser and CLI | Concurrent job regression and round-trip |
| Storage `.` / `..` could resolve to dangerous cleanup targets | Strict bounded storage identifier; safe path checks | Path regressions |
| Archive names could select unrelated files | Single .wpress filename, symlink checks, canonical destination construction | Path regressions |
| Archive member paths could escape the extraction root | Reject absolute paths, parent traversal, wrappers and symlinks before mkdir/write | Malicious archive regressions |
| Truncated/invalid sizes, footer placement, failed writes could be accepted | Strict header/data bounds, terminal footer checks, resume bounds and explicit I/O failures | Malformed archive and streaming regressions |
| Retried upload chunks could append twice | Offset-based locked streaming; identical retry acknowledged, conflicting retry rejected | Retry, partial-write, order and bounds regressions |
| Archive-provided objects were unserialized | Byte-length-aware serialized-string rewriting without class instantiation | Wakeup gadget, Unicode and nested serialization regressions |
| Empty/malformed metadata failed late | Bounded JSON metadata and required field/URL validation before restore | Metadata regressions |
| Browser request logs included secrets | Redact secret/nonce; disable per-step request logs unless engine debug is enabled | Source review |
| Backups were served directly from public paths | Authorized streaming download; Apache/IIS deny rules for backup and staging directories | Authorized 200 and static 403 on Apache |
| Fork branding version selected an old destructive DB conversion | Separate STW release version from upstream archive format version | Clean repeated CLI round-trip |
| Migration engine loaded on public frontend pages | Return before engine loading outside admin/CLI | One bootstrap file, no controller class loaded |
| Backup listing recursively traversed storage | Native single-directory iteration; skip links and unrelated files | Code review and browser library |
| File-size fallback read whole files on modern hosts | Native stat on 64-bit PHP | Source review; no throughput claim |
| Old provider/support/update surfaces remained | Remove 49 unused files and update/support hooks; file-only migration UI | Source/reference checks |
| Import AJAX retries could run forever | Bounded retries and immediate terminal authorization errors | Script review and browser smoke tests |
| wp-env transitive dependencies had advisories | Compatible patched dependency overrides and lockfile | Full npm audit: zero findings |

## Deployment requirements

Keep archives private. Apache must allow the generated .htaccess rules; IIS must
apply web.config authorization. Both temporary SQL/job files and final archives
need protection. With Nginx, configure these locations for the actual installation
paths (adapt the plugin path if it differs):

```nginx
location ^~ /wp-content/ai1wm-backups/ { deny all; }
location ^~ /wp-content/plugins/stw-migration/storage/ { deny all; }
```

Alternatively set `STW_MIGRATION_BACKUPS_PATH` in wp-config.php to an absolute,
writable directory outside the web root for final backups. Temporary storage
still needs server protection. Verify unauthenticated HTTP access is denied;
Apache/IIS rules cannot control Nginx or a CDN serving cached old archives.
No IIS or Nginx runtime verification was performed in this session.

Only restore trusted archives. Restoring intentionally executes imported SQL and
installs PHP files. A filesystem attacker with write access can alter job state;
this plugin is not a boundary against another compromised PHP process.

## Limits and follow-up testing

- Only WordPress 7.1.2 / PHP 8.3.35 received runtime migration tests. PHP 8.5 CLI
  syntax passed; PHP 8.1 is declared minimum but not runtime-tested here.
- No >2 GB fixture, disk-full fault injection, Windows host, persistent object
  cache, multisite, third-party extension or modern exporter compatibility matrix.
- A restore replaces data incrementally; abrupt process termination is not
  transactionally rolled back. Keep an independent backup and use maintenance
  windows. Interrupted jobs may need explicit administrator cleanup.
- Custom `C:` serialized object payloads are preserved opaquely; URLs inside
  custom serialization are not rewritten. No magic methods are executed.
- Symlink content is intentionally excluded; nested backup folders are not listed.
- The legacy engine and browser bundles remain. A new frontend framework was not
  added; the responsive workspace uses native HTML/CSS and a small keyboard helper.
- Changes reduce frontend work, file count and logging I/O. No end-to-end migration
  speed percentage is claimed without representative large-site benchmarks.

## 1.1.0-beta.1 follow-up

New exports use a real .stwm ZIP/ZIP64 container with manifest v1 and per-file
SHA-256 checksums. The native import adapter verifies every byte before exposing
a private stream to the restore pipeline. Wrong versions, inventories, sizes,
compression methods, hashes, encrypted entries, symlinks and unsafe paths fail
before restore. Existing output files are preserved on rejected conversion.
Legacy .wpress imports remain supported and were tested with an original archive
created before the native format existed, as well as generated round-trip data.

61 security assertions and 34 native-format assertions pass. Both native and
legacy cross-site restores pass eight data invariants each. Native browser export
and upload/restore pass. New STW modules pass the scoped PHPCS ruleset; the legacy
engine is not claimed to be fully coding-standard compliant.

Independent-worker testing exposed ineffective filesystem advisory exclusion on
the development bind mount. Coordination now uses prepared MySQL/MariaDB
GET_LOCK/RELEASE_LOCK calls, scoped by database/prefix and job/stage. Independent
database-session exclusion is verified. The database must support these calls;
drop-in/replica routing configurations remain unverified. Job state is bounded
and schema-checked; symlinked metadata is rejected. Existing path protections
were retained.

Native stages add I/O and are not yet time-sliced or persistently resumable after
process death. This is a testing release, with no migration speed improvement
percentage claimed. Read ARCHIVE-FORMAT.md before using large native archives.
