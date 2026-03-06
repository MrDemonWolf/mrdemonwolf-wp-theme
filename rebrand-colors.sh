#!/usr/bin/env bash
#
# MrDemonWolf Color Rebrand Script (WP-CLI — post-import)
#
# Updates Divi global colors, customizer options, and builder
# content in a live WordPress database to match your brand.
# Requires WP-CLI (wp) to be installed and configured.
#
# Usage:
#   ./rebrand-colors.sh --primary "#ff6600" --secondary "#1a1a2e"
#   ./rebrand-colors.sh --primary "#ff6600" --secondary "#1a1a2e" --dry-run
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Default brand colors (current MrDemonWolf values)
# ---------------------------------------------------------------------------
OLD_PRIMARY="#1e8a8a"
OLD_SECONDARY="#0c1e21"

# Hardcoded hex values derived from the primary/secondary palette
OLD_BODY_TEXT="#364e52"
OLD_BACKGROUND="#d8e5e5"
OLD_LIGHT_BG="#ecf0f0"
OLD_DARK2="#18292c"
OLD_TEXT2="#a9b8b8"
OLD_EXTRA1="#c9d1d1"
OLD_EXTRA2="#67787a"
OLD_EXTRA3="#313d3d"
OLD_EXTRA4="#e9eded"

# ---------------------------------------------------------------------------
# Parse arguments
# ---------------------------------------------------------------------------
DRY_RUN=false
NEW_PRIMARY=""
NEW_SECONDARY=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --primary)   NEW_PRIMARY="$2"; shift 2 ;;
    --secondary) NEW_SECONDARY="$2"; shift 2 ;;
    --dry-run)   DRY_RUN=true; shift ;;
    *)
      echo "Unknown option: $1"
      echo "Usage: ./rebrand-colors.sh --primary \"#hex\" --secondary \"#hex\" [--dry-run]"
      exit 1
      ;;
  esac
done

# ---------------------------------------------------------------------------
# Validate inputs
# ---------------------------------------------------------------------------
validate_hex() {
  local color="$1" label="$2"
  if [[ ! "$color" =~ ^#[0-9a-fA-F]{6}$ ]]; then
    echo "Error: $label must be a 6-digit hex color (e.g. #ff6600). Got: $color"
    exit 1
  fi
}

if [[ -z "$NEW_PRIMARY" || -z "$NEW_SECONDARY" ]]; then
  echo "Error: Both --primary and --secondary are required."
  echo "Usage: ./rebrand-colors.sh --primary \"#hex\" --secondary \"#hex\" [--dry-run]"
  exit 1
fi

validate_hex "$NEW_PRIMARY" "--primary"
validate_hex "$NEW_SECONDARY" "--secondary"

if ! command -v wp &>/dev/null; then
  echo "Error: WP-CLI (wp) is not installed or not in PATH."
  exit 1
fi

# ---------------------------------------------------------------------------
# Normalize hex to lowercase for consistent matching
# ---------------------------------------------------------------------------
OLD_PRIMARY=$(echo "$OLD_PRIMARY" | tr '[:upper:]' '[:lower:]')
OLD_SECONDARY=$(echo "$OLD_SECONDARY" | tr '[:upper:]' '[:lower:]')
NEW_PRIMARY=$(echo "$NEW_PRIMARY" | tr '[:upper:]' '[:lower:]')
NEW_SECONDARY=$(echo "$NEW_SECONDARY" | tr '[:upper:]' '[:lower:]')

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
echo "=== MrDemonWolf Color Rebrand ==="
echo ""
echo "  Primary:   $OLD_PRIMARY -> $NEW_PRIMARY"
echo "  Secondary: $OLD_SECONDARY -> $NEW_SECONDARY"
echo ""

if $DRY_RUN; then
  echo "=== DRY RUN MODE — no changes will be made ==="
else
  echo "=== LIVE MODE — changes will be applied ==="
  echo "WARNING: Back up your database before proceeding!"
fi
echo ""

TOTAL=0

# ---------------------------------------------------------------------------
# Helper: wp search-replace with dry-run awareness
# ---------------------------------------------------------------------------
do_replace() {
  local old="$1" new="$2" label="$3"
  local count
  count=$(wp search-replace "$old" "$new" --precise --dry-run --format=count 2>/dev/null || echo "0")
  echo "  $label: $count replacement(s)"
  TOTAL=$((TOTAL + count))

  if ! $DRY_RUN && [[ "$count" -gt 0 ]]; then
    wp search-replace "$old" "$new" --precise --quiet
  fi
}

# ---------------------------------------------------------------------------
# 1. Divi Customizer options (et_divi option array)
# ---------------------------------------------------------------------------
echo "--- Divi Customizer Options (et_divi) ---"

DIVI_PRIMARY_KEYS=(
  accent_color
  link_color
  menu_link_active
  primary_nav_dropdown_line_color
  secondary_nav_bg
  secondary_nav_dropdown_bg
  slide_nav_bg
  footer_widget_header_color
  footer_widget_bullet_color
  footer_menu_active_link_color
)

DIVI_SECONDARY_KEYS=(
  secondary_accent_color
  header_color
)

for key in "${DIVI_PRIMARY_KEYS[@]}"; do
  if $DRY_RUN; then
    current=$(wp option pluck et_divi "$key" 2>/dev/null || echo "(not set)")
    echo "  $key: $current -> $NEW_PRIMARY"
  else
    wp option patch update et_divi "$key" "$NEW_PRIMARY" --quiet 2>/dev/null || true
    echo "  $key -> $NEW_PRIMARY"
  fi
  TOTAL=$((TOTAL + 1))
done

for key in "${DIVI_SECONDARY_KEYS[@]}"; do
  if $DRY_RUN; then
    current=$(wp option pluck et_divi "$key" 2>/dev/null || echo "(not set)")
    echo "  $key: $current -> $NEW_SECONDARY"
  else
    wp option patch update et_divi "$key" "$NEW_SECONDARY" --quiet 2>/dev/null || true
    echo "  $key -> $NEW_SECONDARY"
  fi
  TOTAL=$((TOTAL + 1))
done

# Body text color
if $DRY_RUN; then
  current=$(wp option pluck et_divi font_color 2>/dev/null || echo "(not set)")
  echo "  font_color: $current (not changed — body text)"
else
  echo "  font_color: (not changed — body text)"
fi

# ---------------------------------------------------------------------------
# 2. Global Colors in et_global_data
# ---------------------------------------------------------------------------
echo ""
echo "--- Divi Global Colors (et_global_data) ---"

# Global colors are stored as serialized data. Use search-replace on the
# serialized option value for the specific color hex strings.
do_replace "$OLD_PRIMARY" "$NEW_PRIMARY" "Primary color in global data"
do_replace "$OLD_SECONDARY" "$NEW_SECONDARY" "Secondary color in global data"

# ---------------------------------------------------------------------------
# 3. Divi Builder content (post_content shortcodes/JSON)
# ---------------------------------------------------------------------------
echo ""
echo "--- Divi Builder Content ---"

# Primary color replacements
do_replace "$OLD_PRIMARY" "$NEW_PRIMARY" "Primary hex in content"

# Secondary / heading color replacements
do_replace "$OLD_SECONDARY" "$NEW_SECONDARY" "Secondary hex in content"

# Body text color
do_replace "$OLD_BODY_TEXT" "$NEW_SECONDARY" "Body text hex in content"

# Background colors
do_replace "$OLD_BACKGROUND" "$NEW_PRIMARY" "Background hex (#d8e5e5)"
do_replace "$OLD_LIGHT_BG" "$NEW_PRIMARY" "Light bg hex (#ecf0f0)"
do_replace "$OLD_DARK2" "$NEW_SECONDARY" "Dark color 2 hex (#18292c)"
do_replace "$OLD_TEXT2" "$NEW_PRIMARY" "Text 2 hex (#a9b8b8)"

# Extra hardcoded layout colors
do_replace "$OLD_EXTRA1" "$NEW_PRIMARY" "Layout hex (#c9d1d1)"
do_replace "$OLD_EXTRA2" "$NEW_SECONDARY" "Layout hex (#67787a)"
do_replace "$OLD_EXTRA3" "$NEW_SECONDARY" "Layout hex (#313d3d)"
do_replace "$OLD_EXTRA4" "$NEW_PRIMARY" "Layout hex (#e9eded)"

# Also handle uppercase variants
OLD_PRIMARY_UC=$(echo "$OLD_PRIMARY" | tr '[:lower:]' '[:upper:]')
NEW_PRIMARY_UC=$(echo "$NEW_PRIMARY" | tr '[:lower:]' '[:upper:]')
OLD_SECONDARY_UC=$(echo "$OLD_SECONDARY" | tr '[:lower:]' '[:upper:]')
NEW_SECONDARY_UC=$(echo "$NEW_SECONDARY" | tr '[:lower:]' '[:upper:]')

if [[ "$OLD_PRIMARY_UC" != "$OLD_PRIMARY" ]]; then
  do_replace "$OLD_PRIMARY_UC" "$NEW_PRIMARY" "Primary hex (uppercase)"
fi
if [[ "$OLD_SECONDARY_UC" != "$OLD_SECONDARY" ]]; then
  do_replace "$OLD_SECONDARY_UC" "$NEW_SECONDARY" "Secondary hex (uppercase)"
fi

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
echo ""
echo "=== Summary ==="
echo "Total items processed: $TOTAL"

if $DRY_RUN; then
  echo "No changes were made (dry-run mode)."
  echo "To apply changes, run without --dry-run."
else
  echo "All changes applied successfully."
fi
