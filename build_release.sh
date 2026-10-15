#!/bin/bash
set -eu

config_file=omniva-woocommerce/omniva-woocommerce.php
plugin_file=omniva-woocommerce/omniva-woocommerce.php

if ! git cat-file -e "HEAD:$config_file"; then
  echo "Update configuration file is missing from HEAD: $config_file" >&2
  exit 1
fi

asset_name=$(git show "HEAD:$config_file" | sed -n "s/^[[:space:]]*\$update_asset_name[[:space:]]*=[[:space:]]*'\([^']*\)'.*/\1/p" | head -n 1)
if [ -z "$asset_name" ]; then
  echo "Could not read the release asset name from HEAD:$config_file; commit the update configuration before building a release" >&2
  exit 1
fi

if ! printf '%s' "$asset_name" | grep -Eq '^[A-Za-z0-9._-]+\.zip$'; then
  echo "Release asset must be a safe ZIP filename: $asset_name" >&2
  exit 1
fi

if ! git cat-file -e "HEAD:$plugin_file"; then
  echo "Plugin entry point is missing from HEAD: $plugin_file" >&2
  exit 1
fi

file="$asset_name"
rm -f -- "$file"
git archive --format=zip --output="$file" HEAD

if [ ! -s "$file" ]; then
  echo "Release archive was not created: $file" >&2
  exit 1
fi

if ! unzip -Z1 "$file" | grep -Fxq "$plugin_file"; then
  echo "Release archive does not contain the plugin entry point: $plugin_file" >&2
  exit 1
fi
