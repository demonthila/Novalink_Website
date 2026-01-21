#!/usr/bin/env python3
import os
import re
from pathlib import Path

def get_all_files(root, extensions):
    files = []
    for root_dir, dirs, filenames in os.walk(root):
        dirs[:] = [d for d in dirs if d not in {'.git', 'node_modules', '__pycache__', '.vscode'}]
        for f in filenames:
            if any(f.lower().endswith(ext) for ext in extensions):
                files.append(os.path.relpath(os.path.join(root_dir, f), root))
    return files

def normalize_path(p):
    p = p.replace('\\', '/').lower()
    if p.startswith('./'): p = p[2:]
    if p.startswith('/'): p = p[1:]
    return p

def find_references(content):
    refs = set()
    patterns = [
        r'src=["\']([^"\']+)["\']',
        r'href=["\']([^"\']+)["\']',
        r'url\(["\']?([^"\'()]+)["\']?\)',
        r'background["\']?\s*:\s*["\']?([^"\'();]+)',
        r'data-background=["\']([^"\']+)["\']',
        r'data-displacement=["\']([^"\']+)["\']',
    ]
    for pattern in patterns:
        for m in re.finditer(pattern, content, re.I):
            ref = m.group(1).strip()
            if ref and not ref.startswith(('http', '//', '#')):
                refs.add(normalize_path(ref))
    return refs

root = os.path.dirname(os.path.abspath(__file__))
media_files = get_all_files(root, ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.webp', '.mp4', '.mov', '.pdf'])
source_files = get_all_files(root, ['.html', '.css', '.js', '.php', '.scss'])

print(f"Found {len(media_files)} media files")
print(f"Found {len(source_files)} source files")

used = set()
for sf in source_files:
    try:
        with open(os.path.join(root, sf), 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
            refs = find_references(content)
            for mf in media_files:
                mf_norm = normalize_path(mf)
                mf_base = os.path.basename(mf_norm)
                if any(mf_norm == r or mf_norm.endswith(r) or os.path.basename(r) == mf_base for r in refs):
                    used.add(mf)
    except Exception as e:
        print(f"Error reading {sf}: {e}")

unused = sorted(set(media_files) - used)
print(f"\nUsed: {len(used)}, Unused: {len(unused)}")

if unused:
    with open('unused_files.txt', 'w') as f:
        for u in unused:
            f.write(f"{u}\n")
    print(f"\nUnused files written to unused_files.txt")
    print("\nFirst 20 unused files:")
    for u in unused[:20]:
        print(f"  {u}")
