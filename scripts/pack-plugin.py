import os
import sys
import shutil
import tempfile
import fnmatch
import zipfile

def main():
    if len(sys.argv) < 2:
        print("Usage: python scripts/pack-plugin.py <plugin-slug> [output-dir]")
        sys.exit(1)

    slug = sys.argv[1]
    monorepo_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    plugin_dir = os.path.join(monorepo_root, "plugins", slug)
    
    if not os.path.isdir(plugin_dir):
        print(f"Plugin directory not found: {plugin_dir}")
        sys.exit(1)

    output_dir = sys.argv[2] if len(sys.argv) > 2 else monorepo_root
    
    distignore_path = os.path.join(plugin_dir, ".distignore")
    patterns = []
    if os.path.isfile(distignore_path):
        with open(distignore_path, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith("#"):
                    continue
                patterns.append(line.rstrip('/'))

    # Always ignore .git
    if ".git" not in patterns:
        patterns.append(".git")

    def ignore_func(directory, contents):
        ignored = []
        for item in contents:
            full_path = os.path.join(directory, item)
            rel_path = os.path.relpath(full_path, plugin_dir).replace(os.sep, '/')
            
            # Check if rel_path matches any pattern
            matched = False
            for pat in patterns:
                if rel_path == pat or rel_path.startswith(pat + '/') or fnmatch.fnmatch(rel_path, pat):
                    matched = True
                    break
            if matched:
                ignored.append(item)
        return ignored

    # Create a staging root directory
    staging_root = tempfile.mkdtemp()
    staging_dir = os.path.join(staging_root, slug)

    print(f"Staging plugin '{slug}' (excluding ignored files)...")
    # Copy plugin_dir to staging_dir using ignore_func
    shutil.copytree(plugin_dir, staging_dir, ignore=ignore_func)

    # Create the zip
    zip_name = f"{slug}.zip"
    zip_path = os.path.join(output_dir, zip_name)
    if os.path.exists(zip_path):
        os.remove(zip_path)

    print(f"Creating zip file at {zip_path}...")
    with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(staging_dir):
            for file in files:
                file_path = os.path.join(root, file)
                # Store in zip relative to staging_root so that it has the root folder 'slug'
                arcname = os.path.relpath(file_path, staging_root)
                zipf.write(file_path, arcname)

    # Clean up staging root
    shutil.rmtree(staging_root)
    
    size_bytes = os.path.getsize(zip_path)
    size_mb = size_bytes / (1024 * 1024)
    print(f"Successfully created zip: {zip_path} ({size_mb:.4f} MB / {size_bytes} bytes)")

if __name__ == "__main__":
    main()
