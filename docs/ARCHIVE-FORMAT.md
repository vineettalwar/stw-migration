# STW archive format v1

Extension: `.stwm`. Media type: `application/zip` (downloads use octet-stream).
The container is standard ZIP, with automatic ZIP64 support from libzip when
needed. It is a new container format, not a renamed `.wpress` file. Native ZIP
support, 64-bit PHP and MySQL/MariaDB advisory locks are required to create or import it. Legacy `.wpress`
imports continue to use the existing parser and do not require ZIP support.

## Layout

```text
backup.stwm
├── manifest.json
└── site/
    ├── package.json
    ├── database.sql       # absent if database export was excluded
    ├── uploads/...
    ├── themes/...
    └── plugins/...
```

Use any standard ZIP reader to inspect the contents; extracting through external
tools does not perform STW's validation. No ZIP entry may be absent from the
manifest except manifest.json itself. Directories are implicit. Duplicate names,
symlinks, encrypted entries, absolute paths, `..`, `.` path components, backslash,
control characters, and stream/drive prefixes are rejected.

## Manifest schema

The JSON root is an object with these fields:

| Field | Type | Meaning |
| --- | --- | --- |
| format | string | Exactly `stw-migration` |
| version | integer | Exactly `1`; unknown versions fail before restore |
| created_at | string | Creation time in UTC, ISO 8601 (informational) |
| generator | string | Product/release that created the archive (informational) |
| entries | array | Complete inventory of payload files |

Each entry is an object with `path` (UTF-8, prefixed `site/`), `size` (nonnegative
integer), `mtime` (nonnegative Unix timestamp), and `sha256` (64 lowercase hex
digits). SHA-256 is computed over the exact uncompressed file bytes. Empty files
have size zero and the standard SHA-256 digest of the empty string.

V1 writes payload entries with ZIP method STORE. Other payload compression
methods are currently rejected, so repacking the container with default ZIP
compression changes its validity. The manifest may be compressed by libzip.

Limits: 16 MiB manifest, 100,000 payload entries, 255-byte basename, 4,096-byte
parent path, 14-digit size, and 12-digit timestamp. The last four reflect the
current restore adapter's record representation, not inherent ZIP restrictions.
The importer checks integer overflow and available staging space when the host
reports free space. `site/package.json` must satisfy the legacy package metadata
schema before any database restoration; database.sql is optional.

## Export and import

The migration pipeline first gathers its tested intermediate stream. Export
indexes its validated records, hashes each file using bounded streams, and asks
libzip to add slices of the source stream directly as ordinary ZIP entries. It
does not extract a second temporary copy of every file. The completed container
is atomically renamed into the backup library; retries reuse the finished file.

Native import checks the manifest, file inventory, sizes, methods and attributes,
then streams and verifies every payload checksum into a private intermediate
restore stream. Only a fully verified result is renamed into usable staging.
The original saved archive is preserved. The established content and SQL restore
pipeline then runs. Legacy .wpress imports bypass this adapter.

Database-backed per-job stage locks prevent overlapping browser retries from changing the same
conversion files. A failed validation removes only output it created; it cannot
overwrite or delete a pre-existing destination file.

## Security and operational limits

Checksums detect damage; they do not authenticate the sender. Someone able to
replace an archive can also replace its manifest. Archives are not encrypted and
contain sensitive site data. Restore only trusted archives and protect server
storage as described in AUDIT.md.

This beta adds an integrity pass and intermediate disk I/O. It is not a speed
claim. Native packaging and integrity conversion currently run as complete
stages rather than time-sliced per-file requests; very large archives may exceed
hosting/proxy timeouts even though stream memory stays bounded. A retry is locked
against a worker still running, but there is no persistent mid-stage resume after
process death. Large-file, ZIP64-threshold, crash and low-disk fault-injection
matrices remain future release gates. Keep a separate backup for deployment.
