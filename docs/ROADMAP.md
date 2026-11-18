# Next-release proposal

Status: proposed features, not implemented capabilities. The current beta remains
single-site only. STW is the displayed author of this fork. Original copyright,
GPL-3.0-or-later terms, third-party licenses, source availability and dated change
notices remain intact. No premium-extension code is assumed to be available.

## Release priorities

| Priority | Feature | User outcome |
| --- | --- | --- |
| P0 | Multisite migration | Back up/restore a whole network, or move individual sites into and out of a network |
| P0 | Resumable native archive stages | Continue packaging and verification after interrupted requests without restarting the entire stage |
| P0 | Preflight and migration plan | Verify archive integrity, capacity, permissions and site/domain mappings before changing a destination |
| P1 | Recovery snapshot before replacement | Save a verified destination backup and provide a recovery procedure before destructive restoration |
| P1 | Job history and diagnostics | See who started a job, what moved, verified results and actionable errors with sensitive data redacted |
| P1 | Selective restore | Restore chosen content or database scope with dependency-aware user/media mapping |
| P2 | Scheduled backups and retention | Run verified backups on a schedule with explicit storage budgets and safe cleanup |
| P2 | Offsite storage | Send/fetch archives through independently implemented S3-compatible and SFTP adapters |
| P2 | Password-protected archives | Add a versioned authenticated-encryption format with portable password-based recovery |

## Multisite is a core release requirement

The required source/destination matrix is:

| Source | Destination | Required behavior |
| --- | --- | --- |
| Entire multisite network | Multisite network | Restore network data, sites, shared users, uploads and network settings with explicit domain/path mappings |
| One network site | Single-site WordPress | Extract only that site's content and required users/media, rewriting its table and upload paths |
| Single-site WordPress | New network site | Create a destination site and map users, roles, URLs, tables and uploads without changing other sites |
| Single-site WordPress | Existing network site | Replace the selected site only after an explicit plan and recovery backup |
| One network site | Another network site | Transfer a selected site with ID/user conflicts handled and unrelated sites untouched |

Start network migration controls in network admin with super-admin authorization,
network capabilities, nonces and explicit target selection. Do not expose a whole
network's users or data to an individual site administrator. Network-wide and
site-specific operations need clearly separate permissions and job scopes.

A new STW-owned network adapter should handle:

- Enumerating network/site metadata and table ownership; shared tables must not
  be duplicated or accidentally replaced during a site-level operation.
- Mapping site IDs, table prefixes, domains/subdomains/subdirectories and upload
  locations, including serialized URL/path replacements.
- Mapping shared users and site roles while preserving content authorship and
  avoiding unintended super-admin privileges or unrelated user-data disclosure.
- Network-active versus site-active plugins, themes, mu-plugins and environment
  requirements; missing code/dependencies must appear in the preflight plan.
- Locking by destination network and selected site, with conservative exclusion
  of overlapping whole-network and per-site migrations.
- A versioned native manifest extension for network inventory and mappings, while
  continuing to import current STW v1 single-site and compatible legacy backups.

Do not enable the current multisite UI/CLI guards merely by defining an old
extension constant. Existing network-related engine code must be audited and
covered by integration tests; GPL attribution alone is not evidence of support.

## Multisite release gates

Use isolated network fixtures for both subdirectory and subdomain setups. Cover
all matrix entries above, including colliding site/user IDs, different prefixes,
shared users with different roles, URL serialization, media integrity, network
plugins, interrupted jobs and unavailable dependencies. For a site-level restore,
assert that unrelated sites' tables, users, settings and media remain unchanged.

The feature stays disabled until these fixtures pass. Large-network checkpoints,
private archive storage, capacity accounting and documented recovery procedures
are release requirements, not optional polish.

## Suggested delivery order

1. Build network inventory and read-only preflight/mapping screens.
2. Make native packaging/verification persistently resumable.
3. Add whole-network export and selected-site export with verified manifests.
4. Add selected-site ↔ single-site import into isolated destinations.
5. Add existing-site replacement and whole-network restore with recovery gates.
6. Expand to scheduling, offsite adapters and encryption after the migration
   matrix is stable.
