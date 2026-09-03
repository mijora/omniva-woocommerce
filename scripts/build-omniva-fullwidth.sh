#!/usr/bin/env bash
#
# Build the shared Omniva full-width theme and synchronize only its packaged
# output into the WooCommerce plugin.
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SUBMODULE_DIR="$REPO_ROOT/omniva-mapping-v2"
PACKAGER="$SUBMODULE_DIR/scripts/package-theme.sh"
SOURCE_DIR="$SUBMODULE_DIR/packaged/omniva-fullwidth"
PLUGIN_ASSETS_DIR="$REPO_ROOT/omniva-woocommerce/assets"
TARGET_DIR="$PLUGIN_ASSETS_DIR/terminal-mapping"
TMP_DIR="$PLUGIN_ASSETS_DIR/.terminal-mapping.sync.$$"
BACKUP_DIR="$PLUGIN_ASSETS_DIR/.terminal-mapping.backup.$$"
TARGET_WAS_PRESENT=0
SYNC_STARTED=0
SYNC_COMPLETED=0

if [ ! -d "$SUBMODULE_DIR" ] || [ ! -x "$PACKAGER" ]; then
  echo "Missing terminal-mapping submodule or executable packager: $PACKAGER" >&2
  exit 1
fi

if [ ! -d "$PLUGIN_ASSETS_DIR" ]; then
  echo "Missing WooCommerce assets directory: $PLUGIN_ASSETS_DIR" >&2
  exit 1
fi

if [ "$TARGET_DIR" != "$REPO_ROOT/omniva-woocommerce/assets/terminal-mapping" ]; then
  echo "Refusing to synchronize an unexpected target: $TARGET_DIR" >&2
  exit 1
fi

case "$TMP_DIR" in
  "$PLUGIN_ASSETS_DIR"/.terminal-mapping.sync.*) ;;
  *)
    echo "Refusing to use an unsafe temporary directory: $TMP_DIR" >&2
    exit 1
    ;;
esac

case "$BACKUP_DIR" in
  "$PLUGIN_ASSETS_DIR"/.terminal-mapping.backup.*) ;;
  *)
    echo "Refusing to use an unsafe backup directory: $BACKUP_DIR" >&2
    exit 1
    ;;
esac

if [ -L "$TARGET_DIR" ]; then
  echo "Refusing to synchronize a symbolic-link target: $TARGET_DIR" >&2
  exit 1
fi

if [ -e "$TARGET_DIR" ] && [ ! -d "$TARGET_DIR" ]; then
  echo "Refusing to synchronize a non-directory target: $TARGET_DIR" >&2
  exit 1
fi

if [ -d "$TARGET_DIR" ]; then
  TARGET_WAS_PRESENT=1
fi

restore_target_on_failure() {
  if [ -d "$BACKUP_DIR" ]; then
    rm -rf -- "$TARGET_DIR" || true
    mv "$BACKUP_DIR" "$TARGET_DIR" || true
  elif [ "$SYNC_STARTED" = "1" ] && [ "$TARGET_WAS_PRESENT" = "0" ] && [ -d "$TARGET_DIR" ]; then
    rm -rf -- "$TARGET_DIR" || true
  fi
}

cleanup() {
  if [ "$SYNC_COMPLETED" != "1" ]; then
    restore_target_on_failure
  fi

  if [ -d "$TMP_DIR" ]; then
    rm -rf -- "$TMP_DIR"
  fi
}
trap cleanup EXIT

"$PACKAGER" omniva-fullwidth

required_paths=(
  "terminal-mapping.omniva-fullwidth.js"
  "terminal-mapping.omniva-fullwidth.css"
  "images"
  "fonts"
)

for required_path in "${required_paths[@]}"; do
  if [ ! -e "$SOURCE_DIR/$required_path" ]; then
    echo "Packaged theme is incomplete: missing $required_path" >&2
    exit 1
  fi
done

if [ ! -s "$SOURCE_DIR/terminal-mapping.omniva-fullwidth.js" ] \
  || [ ! -s "$SOURCE_DIR/terminal-mapping.omniva-fullwidth.css" ]; then
  echo "Packaged theme contains an empty JS or CSS bundle." >&2
  exit 1
fi

if [ -z "$(find "$SOURCE_DIR/images" -type f -print -quit)" ] \
  || [ -z "$(find "$SOURCE_DIR/fonts" -type f -print -quit)" ]; then
  echo "Packaged theme must contain both image and font assets." >&2
  exit 1
fi

if [ -n "$(find "$SOURCE_DIR" -type l -print -quit)" ]; then
  echo "Packaged theme contains a symbolic link; refusing to synchronize it." >&2
  exit 1
fi

if [ -e "$TMP_DIR" ] || [ -e "$BACKUP_DIR" ]; then
  echo "Temporary synchronization paths already exist; refusing to overwrite them." >&2
  exit 1
fi

mkdir "$TMP_DIR"
cp -R "$SOURCE_DIR/." "$TMP_DIR/"

if ! diff -qr "$SOURCE_DIR" "$TMP_DIR" >/dev/null; then
  echo "The copied package does not match its source." >&2
  exit 1
fi

SYNC_STARTED=1
if [ -d "$TARGET_DIR" ]; then
  mv "$TARGET_DIR" "$BACKUP_DIR"
fi

if ! mv "$TMP_DIR" "$TARGET_DIR"; then
  if [ -d "$BACKUP_DIR" ]; then
    mv "$BACKUP_DIR" "$TARGET_DIR"
  fi
  echo "Failed to install the synchronized terminal-mapping package." >&2
  exit 1
fi

if ! diff -qr "$SOURCE_DIR" "$TARGET_DIR" >/dev/null; then
  echo "Installed terminal-mapping package failed verification." >&2
  exit 1
fi

# The package was installed atomically above, but some file watchers (including
# the local FTP uploader) do not report a directory rename as changes to the
# files inside it. Rewrite only files whose content differs from the previous
# package so watchers receive ordinary per-file write events without uploading
# unchanged assets on every build.
UPDATED_FILES=0
while IFS= read -r -d '' source_file; do
  relative_path="$(realpath --relative-to="$SOURCE_DIR" "$source_file")"
  target_file="$TARGET_DIR/$relative_path"
  previous_file="$BACKUP_DIR/$relative_path"

  if [ -f "$previous_file" ] && cmp -s "$source_file" "$previous_file"; then
    continue
  fi

  cp -- "$source_file" "$target_file"
  chmod 0644 "$target_file"
  UPDATED_FILES=$((UPDATED_FILES + 1))
done < <(find "$SOURCE_DIR" -type f -print0 | sort -z)

if ! diff -qr "$SOURCE_DIR" "$TARGET_DIR" >/dev/null; then
  echo "Per-file synchronization failed verification." >&2
  exit 1
fi

if [ -d "$BACKUP_DIR" ]; then
  rm -rf -- "$BACKUP_DIR"
fi

SYNC_COMPLETED=1

trap - EXIT

echo "Synchronized: $SOURCE_DIR"
echo "           -> $TARGET_DIR"
echo "  per-file writes for FTP watcher: $UPDATED_FILES"
