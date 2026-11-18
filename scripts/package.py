#!/usr/bin/env python3
"""Build an installable source ZIP without Git, runtime data, or local tooling."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
root = Path(__file__).resolve().parents[1]
plugin = root / 'stw-migration'
output = root / 'dist/stw-migration-1.0.0-beta.1.zip'
output.parent.mkdir(exist_ok=True)
with ZipFile(output, 'w', ZIP_DEFLATED) as archive:
    for path in sorted(plugin.rglob('*')):
        relative = path.relative_to(plugin)
        if path.is_file() and not any(part in {'.git', '.code-review-graph', 'storage', 'tests', 'node_modules', '.DS_Store'} for part in relative.parts):
            archive.write(path, Path('stw-migration') / relative)
with ZipFile(output) as archive:
    assert archive.testzip() is None
    assert 'stw-migration/stw-migration.php' in archive.namelist()
    assert 'stw-migration/LICENSE' in archive.namelist()
print(output)
