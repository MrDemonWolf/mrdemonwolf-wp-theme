#!/usr/bin/env bash
#
# MrDemonWolf Theme Migration Script
#
# Migrates data from the Nexus Divi Child Theme to MrDemonWolf.
# Requires WP-CLI (wp) to be installed and configured.
#
# Usage:
#   ./migrate.sh              # live run
#   ./migrate.sh --dry-run    # preview only
#
set -euo pipefail

DRY_RUN=false
if [[ "${1:-}" == "--dry-run" ]]; then
  DRY_RUN=true
fi

if ! command -v wp &>/dev/null; then
  echo "Error: WP-CLI (wp) is not installed or not in PATH."
  exit 1
fi

if $DRY_RUN; then
  echo "=== DRY RUN MODE — no changes will be made ==="
  echo ""
else
  echo "=== LIVE MODE — changes will be applied ==="
  echo "WARNING: Back up your database before proceeding!"
  echo ""
fi

TOTAL=0

# --------------------------------------------------------------------------
# 1. Shortcodes in post content
# --------------------------------------------------------------------------
echo "--- Shortcode migration ---"

declare -A SHORTCODES=(
  ["Nexus_breadcrumbs"]="mrdemonwolf_breadcrumbs"
  ["nexus_tags"]="mrdemonwolf_tags"
  ["nexus_social_share"]="mrdemonwolf_social_share"
)

for old in "${!SHORTCODES[@]}"; do
  new="${SHORTCODES[$old]}"
  count=$(wp search-replace "[$old" "[$new" --precise --dry-run --format=count 2>/dev/null || echo "0")
  echo "  [$old] -> [$new]: $count replacement(s)"
  TOTAL=$((TOTAL + count))

  if ! $DRY_RUN && [[ "$count" -gt 0 ]]; then
    wp search-replace "[$old" "[$new" --precise --quiet
  fi
done

# --------------------------------------------------------------------------
# 2. CSS classes nexus- -> mdw- in post content (Divi builder data)
# --------------------------------------------------------------------------
echo ""
echo "--- CSS class migration (nexus- -> mdw-) ---"

count=$(wp search-replace "nexus-" "mdw-" --precise --dry-run --format=count 2>/dev/null || echo "0")
echo "  nexus- -> mdw-: $count replacement(s)"
TOTAL=$((TOTAL + count))

if ! $DRY_RUN && [[ "$count" -gt 0 ]]; then
  wp search-replace "nexus-" "mdw-" --precise --quiet
fi

# --------------------------------------------------------------------------
# 3. Post meta keys _nexus_service_image -> _mrdemonwolf_service_image
# --------------------------------------------------------------------------
echo ""
echo "--- Post meta migration ---"

meta_count=$(wp db query "SELECT COUNT(*) FROM $(wp db prefix)postmeta WHERE meta_key = '_nexus_service_image';" --skip-column-names 2>/dev/null | tr -d '[:space:]')
echo "  _nexus_service_image -> _mrdemonwolf_service_image: ${meta_count} row(s)"
TOTAL=$((TOTAL + meta_count))

if ! $DRY_RUN && [[ "$meta_count" -gt 0 ]]; then
  wp db query "UPDATE $(wp db prefix)postmeta SET meta_key = '_mrdemonwolf_service_image' WHERE meta_key = '_nexus_service_image';"
fi

# --------------------------------------------------------------------------
# 4. wp_options entries with nexus_ prefix
# --------------------------------------------------------------------------
echo ""
echo "--- Options migration ---"

options=$(wp db query "SELECT option_name FROM $(wp db prefix)options WHERE option_name LIKE 'nexus\_%';" --skip-column-names 2>/dev/null || true)
opt_count=$(echo "$options" | grep -c . 2>/dev/null || echo "0")
echo "  nexus_* options found: $opt_count"
TOTAL=$((TOTAL + opt_count))

if ! $DRY_RUN && [[ "$opt_count" -gt 0 ]]; then
  while IFS= read -r opt; do
    [[ -z "$opt" ]] && continue
    new_opt="${opt/nexus_/mrdemonwolf_}"
    wp db query "UPDATE $(wp db prefix)options SET option_name = '$new_opt' WHERE option_name = '$opt';"
    echo "    Renamed: $opt -> $new_opt"
  done <<< "$options"
fi

# --------------------------------------------------------------------------
# Summary
# --------------------------------------------------------------------------
echo ""
echo "=== Summary ==="
echo "Total items to migrate: $TOTAL"

if $DRY_RUN; then
  echo "No changes were made (dry-run mode)."
  echo "To apply changes, run: ./migrate.sh"
else
  echo "All changes applied successfully."
  echo "This script is idempotent — safe to run again."
fi
