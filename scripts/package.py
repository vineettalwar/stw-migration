#!/usr/bin/env python3
"""Build an installable source ZIP without runtime data or development tooling."""
from pathlib import Path
import re
from zipfile import ZIP_DEFLATED, ZipFile

root = Path(__file__).resolve().parents[1]
plugin = root / "stw-migration"
header = (plugin / "stw-migration.php").read_text()
version = re.search(r"^\s*\* Version:\s*([A-Za-z0-9.+-]+)\s*$", header, re.MULTILINE)
if not version:
    raise SystemExit("Plugin release version is missing or invalid")

output = root / "dist" / f"stw-migration-{version[1]}.zip"
output.parent.mkdir(exist_ok=True)
excluded_parts = {".git", ".code-review-graph", "storage", "tests", "node_modules", ".DS_Store"}
private_suffixes = {".stwm", ".wpress", ".log"}
with ZipFile(output, "w", ZIP_DEFLATED) as archive:
    for path in sorted(plugin.rglob("*")):
        relative = path.relative_to(plugin)
        if (
            not path.is_file()
            or path.is_symlink()
            or path.suffix.lower() in private_suffixes
            or any(part in excluded_parts for part in relative.parts)
        ):
            continue
        archive.write(path, Path("stw-migration") / relative)

with ZipFile(output) as archive:
    assert archive.testzip() is None
    assert "stw-migration/stw-migration.php" in archive.namelist()
    assert "stw-migration/LICENSE" in archive.namelist()
print(output)
