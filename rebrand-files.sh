#!/usr/bin/env bash
#
# MrDemonWolf File-Based Color Rebrand Script (pre-import)
#
# Updates hex color values in the supplementary Divi export
# files BEFORE importing them into WordPress. Run this when
# setting up a fresh site with your own brand colors.
#
# Usage:
#   ./rebrand-files.sh --primary "#ff6600" --secondary "#1a1a2e"
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SUPP_DIR="$SCRIPT_DIR/supplementary"

# ---------------------------------------------------------------------------
# Default brand colors (current MrDemonWolf values)
# ---------------------------------------------------------------------------
OLD_PRIMARY="#1e8a8a"
OLD_SECONDARY="#0c1e21"

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
NEW_PRIMARY=""
NEW_SECONDARY=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --primary)   NEW_PRIMARY="$2"; shift 2 ;;
    --secondary) NEW_SECONDARY="$2"; shift 2 ;;
    *)
      echo "Unknown option: $1"
      echo "Usage: ./rebrand-files.sh --primary \"#hex\" --secondary \"#hex\""
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
  echo "Usage: ./rebrand-files.sh --primary \"#hex\" --secondary \"#hex\""
  exit 1
fi

validate_hex "$NEW_PRIMARY" "--primary"
validate_hex "$NEW_SECONDARY" "--secondary"

# ---------------------------------------------------------------------------
# Normalize to lowercase
# ---------------------------------------------------------------------------
OLD_PRIMARY=$(echo "$OLD_PRIMARY" | tr '[:upper:]' '[:lower:]')
OLD_SECONDARY=$(echo "$OLD_SECONDARY" | tr '[:upper:]' '[:lower:]')
NEW_PRIMARY=$(echo "$NEW_PRIMARY" | tr '[:upper:]' '[:lower:]')
NEW_SECONDARY=$(echo "$NEW_SECONDARY" | tr '[:upper:]' '[:lower:]')

# ---------------------------------------------------------------------------
# Target files
# ---------------------------------------------------------------------------
FILES=(
  "$SUPP_DIR/MrDemonWolf Divi Customizer Settings.json"
  "$SUPP_DIR/MrDemonWolf Divi Library.json"
  "$SUPP_DIR/MrDemonWolf Divi Theme Options.json"
  "$SUPP_DIR/MrDemonWolf Theme Builder.json"
  "$SUPP_DIR/All Content.xml"
)

# Verify files exist
for f in "${FILES[@]}"; do
  if [[ ! -f "$f" ]]; then
    echo "Error: File not found: $f"
    exit 1
  fi
done

echo "=== MrDemonWolf File-Based Color Rebrand ==="
echo ""
echo "  Primary:   $OLD_PRIMARY -> $NEW_PRIMARY"
echo "  Secondary: $OLD_SECONDARY -> $NEW_SECONDARY"
echo ""

# ---------------------------------------------------------------------------
# Replacements: map old hex -> new hex
# ---------------------------------------------------------------------------
declare -A COLOR_MAP=(
  ["$OLD_PRIMARY"]="$NEW_PRIMARY"
  ["$OLD_SECONDARY"]="$NEW_SECONDARY"
  ["$OLD_BODY_TEXT"]="$NEW_SECONDARY"
  ["$OLD_BACKGROUND"]="$NEW_PRIMARY"
  ["$OLD_LIGHT_BG"]="$NEW_PRIMARY"
  ["$OLD_DARK2"]="$NEW_SECONDARY"
  ["$OLD_TEXT2"]="$NEW_PRIMARY"
  ["$OLD_EXTRA1"]="$NEW_PRIMARY"
  ["$OLD_EXTRA2"]="$NEW_SECONDARY"
  ["$OLD_EXTRA3"]="$NEW_SECONDARY"
  ["$OLD_EXTRA4"]="$NEW_PRIMARY"
)

# ---------------------------------------------------------------------------
# Perform replacements (case-insensitive via both lower and upper variants)
# ---------------------------------------------------------------------------
TOTAL=0

for f in "${FILES[@]}"; do
  fname=$(basename "$f")
  file_count=0

  for old in "${!COLOR_MAP[@]}"; do
    new="${COLOR_MAP[$old]}"
    old_upper=$(echo "$old" | tr '[:lower:]' '[:upper:]')

    # Count occurrences (lowercase)
    matches=$(grep -o -i "${old//\#/\\#}" "$f" 2>/dev/null | wc -l | tr -d ' ')
    file_count=$((file_count + matches))

    # Replace lowercase
    if [[ "$(uname)" == "Darwin" ]]; then
      sed -i '' "s/${old}/${new}/gi" "$f"
    else
      sed -i "s/${old}/${new}/gi" "$f"
    fi
  done

  echo "  $fname: $file_count replacement(s)"
  TOTAL=$((TOTAL + file_count))
done

echo ""
echo "=== Summary ==="
echo "Total replacements: $TOTAL"
echo "Files updated successfully. You can now import them into WordPress."
