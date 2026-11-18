#!/usr/bin/env python3
"""Destructive test, scoped to wp-env's configured source and tests destination."""
from pathlib import Path
import re
import subprocess
root = Path(__file__).resolve().parents[1]
env = root / 'node_modules/.bin/wp-env'
def run(target, *args):
    result = subprocess.run([str(env), 'run', target, 'wp', *args], cwd=root, text=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, check=True)
    print(result.stdout, end='')
    return result.stdout
run('cli', 'eval-file', 'stw-tests/fixture.php', 'seed')
output = run('cli', 'ai1wm', 'backup', '--exclude-themes', '--exclude-plugins')
match = re.search(r'Backup location: .*/([^/\s]+\.stwm)', output)
if not match:
    raise SystemExit('No successful archive path returned')
run('cli', 'eval-file', 'stw-tests/transfer.php', 'stage', match[1])
for extension in ('stwm', 'wpress'):
    run('tests-cli', 'eval-file', 'stw-tests/fixture.php', 'destination')
    run('tests-cli', 'eval-file', 'stw-tests/transfer.php', 'receive', extension)
    run('tests-cli', 'ai1wm', 'restore', 'roundtrip.' + extension)
    run('tests-cli', 'eval-file', 'stw-tests/fixture.php', 'verify')
    print('PASS: ' + extension + ' cross-site restore')
print('PASS: native STW and legacy WPress migration compatibility')
