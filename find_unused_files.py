#!/usr/bin/env python3
"""
Script to find unused images and files in the website project.
"""
import os
import re
from pathlib import Path
from collections import defaultdict

def get_all_media_files(root_dir):
    """Get all image, video, and other media files."""
    media_extensions = {'.jpg', '.jpeg', '.png', '.gif', '.svg', '.webp', '.mp4', '.mov', '.avi', '.pdf'}
    media_files = []
    
    for root, dirs, files in os.walk(root_dir):
        # Skip certain directories
        dirs[:] = [d for d in dirs if d not in {'.git', 'node_modules', '__pycache__'}]
        
        for file in files:
            if any(file.lower().endswith(ext) for ext in media_extensions):
                full_path = os.path.join(root, file)
                rel_path = os.path.relpath(full_path, root_dir)
                media_files.append(rel_path)
    
    return media_files

def get_all_source_files(root_dir):
    """Get all HTML, CSS, JS, and PHP files."""
    source_extensions = {'.html', '.css', '.js', '.php', '.scss'}
    source_files = []
    
    for root, dirs, files in os.walk(root_dir):
        dirs[:] = [d for d in dirs if d not in {'.git', 'node_modules', '__pycache__'}]
        
        for file in files:
            if any(file.lower().endswith(ext) for ext in source_extensions):
                full_path = os.path.join(root, file)
                source_files.append(full_path)
    
    return source_files

def normalize_path(path):
    """Normalize path for comparison."""
    # Replace backslashes with forward slashes
    path = path.replace('\\', '/')
    # Remove leading ./
    if path.startswith('./'):
        path = path[2:]
    # Remove leading /
    if path.startswith('/'):
        path = path[1:]
    return path.lower()

def search_file_references(source_file, media_files):
    """Search for references to media files in a source file."""
    try:
        with open(source_file, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
    except Exception as e:
        print(f"Error reading {source_file}: {e}")
        return set()
    
    found_files = set()
    
    # Patterns to search for file references
    patterns = [
        r'src=["\']([^"\']+)["\']',  # src="..."
        r'href=["\']([^"\']+)["\']',  # href="..."
        r'url\(["\']?([^"\'()]+)["\']?\)',  # url(...)
        r'background["\']?\s*:\s*["\']?([^"\'();]+)',  # background: ...
        r'data-background=["\']([^"\']+)["\']',  # data-background="..."
        r'data-displacement=["\']([^"\']+)["\']',  # data-displacement="..."
    ]
    
    for pattern in patterns:
        matches = re.findall(pattern, content, re.IGNORECASE)
        for match in matches:
            # Clean up the match
            match = match.strip()
            if not match or match.startswith('http') or match.startswith('//'):
                continue
            
            # Normalize the path
            normalized = normalize_path(match)
            
            # Check if it matches any media file
            for media_file in media_files:
                media_normalized = normalize_path(media_file)
                # Check exact match or if media file ends with the reference
                if normalized == media_normalized or media_normalized.endswith(normalized):
                    found_files.add(media_file)
                # Also check if just the filename matches
                if os.path.basename(media_normalized) == os.path.basename(normalized):
                    found_files.add(media_file)
    
    return found_files

def main():
    root_dir = os.path.dirname(os.path.abspath(__file__))
    
    print("Scanning for media files...")
    media_files = get_all_media_files(root_dir)
    print(f"Found {len(media_files)} media files")
    
    print("Scanning for source files...")
    source_files = get_all_source_files(root_dir)
    print(f"Found {len(source_files)} source files")
    
    print("Searching for file references...")
    used_files = set()
    
    for source_file in source_files:
        found = search_file_references(source_file, media_files)
        used_files.update(found)
    
    # Also check for files referenced by their basename in common directories
    # This handles cases where paths might be slightly different
    for media_file in media_files:
        basename = os.path.basename(media_file).lower()
        for source_file in source_files:
            try:
                with open(source_file, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read().lower()
                    if basename in content:
                        used_files.add(media_file)
            except:
                pass
    
    unused_files = set(media_files) - used_files
    
    print(f"\n{'='*60}")
    print(f"Total media files: {len(media_files)}")
    print(f"Used files: {len(used_files)}")
    print(f"Unused files: {len(unused_files)}")
    print(f"{'='*60}\n")
    
    if unused_files:
        print("Unused files found:")
        for file in sorted(unused_files):
            print(f"  {file}")
        
        # Write to a file
        with open('unused_files.txt', 'w') as f:
            for file in sorted(unused_files):
                f.write(f"{file}\n")
        print(f"\nList saved to unused_files.txt")
    else:
        print("No unused files found!")

if __name__ == '__main__':
    main()
