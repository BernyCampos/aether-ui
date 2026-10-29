import argparse
import hashlib
import json
from pathlib import Path
import zipfile

ROOT = Path(__file__).resolve().parents[1]
EXCLUDED = {'.git', 'vendor', 'node_modules', 'dist', '__pycache__'}
RUNTIME = {'src', 'resources', 'lang', 'docs', 'presets', 'plugin.json', 'README.md', 'LICENSE', 'CHANGELOG.md'}


def build(destination):
    manifest = json.loads((ROOT / 'plugin.json').read_text(encoding='utf-8'))
    assert manifest['id'] == ROOT.name == 'aether-ui'
    assert manifest['category'] == 'theme' and manifest['api_version'] == 1
    assert 'meta' not in manifest, 'Never ship installed plugin metadata'
    destination.mkdir(parents=True, exist_ok=True)
    files = sorted(p for p in ROOT.rglob('*') if p.is_file()
                   and not any(part in EXCLUDED for part in p.relative_to(ROOT).parts)
                   and not p.name.startswith('.env') and not p.is_symlink())
    hashes = []
    for source in (False, True):
        name = f"aether-ui-{manifest['version']}{'-source' if source else ''}.zip"
        path = destination / name
        with zipfile.ZipFile(path, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
            for file in files:
                relative = file.relative_to(ROOT)
                if not source and relative.parts[0] not in RUNTIME:
                    continue
                info = zipfile.ZipInfo('aether-ui/' + relative.as_posix(), (2026, 1, 1, 0, 0, 0))
                info.create_system = 3
                info.external_attr = (0o100644 << 16)
                info.compress_type = zipfile.ZIP_DEFLATED
                archive.writestr(info, file.read_bytes(), compresslevel=9)
        with zipfile.ZipFile(path) as archive:
            assert archive.testzip() is None
            names = archive.namelist()
            assert 'aether-ui/plugin.json' in names
            assert all(n.startswith('aether-ui/') and '..' not in n and '\\' not in n for n in names)
            assert 'meta' not in json.loads(archive.read('aether-ui/plugin.json'))
        digest = hashlib.sha256(path.read_bytes()).hexdigest()
        hashes.append(f'{digest}  {name}')
        print(f'{name}: {len(names)} files, {path.stat().st_size} bytes, SHA256 {digest}')
    (destination / 'SHA256SUMS.txt').write_text('\n'.join(hashes) + '\n', encoding='utf-8')


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--output', type=Path, default=ROOT / 'dist')
    build(parser.parse_args().output.resolve())
