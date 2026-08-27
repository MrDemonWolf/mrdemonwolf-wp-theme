#!/usr/bin/env bash
# Sideload supplementary/media/ into the Media Library and repoint the imports.
#
# The Divi exports reference their media over HTTPS from this repository, which
# is what makes them importable on any site with no dependency on a third-party
# host staying online. That does require the files to be reachable, so this
# script is the offline path: it imports straight off disk and rewrites the URLs
# to wherever WordPress actually put them.
#
# Use it when the site cannot reach GitHub, when working from an unmerged
# branch, or simply to avoid 87 HTTP round-trips on a local rebuild. It is
# idempotent and safe to re-run after nuking and reimporting a site.
#
# Runs as an administrator, which is not optional: SVG uploads are gated on a
# user capability by the SVG Support plugin, and WP-CLI runs as no user by
# default, so every SVG silently fails with "not allowed to upload this file
# type". 24 of the bundled assets are SVG. Override with WP_USER= if the first
# administrator is not the account you want.
#
#   WP="wp" ./supplementary/import-media.sh
#   WP="/path/to/wp-wrapper" WP_USER=1 ./supplementary/import-media.sh
set -euo pipefail

WP="${WP:-wp}"
HERE="$(cd "$(dirname "$0")" && pwd)"
MEDIA="$HERE/media"
RAW="https://raw.githubusercontent.com/MrDemonWolf/mrdemonwolf-wp-theme/main/supplementary/media"
SOURCE_META="_mrdemonwolf_source_file"

[ -d "$MEDIA" ] || { echo "Error: $MEDIA not found." >&2; exit 1; }

WP_USER="${WP_USER:-$($WP user list --role=administrator --field=ID 2>/dev/null | head -1)}"
if ! [[ "${WP_USER:-}" =~ ^[0-9]+$ ]]; then
  echo "Error: no administrator found; SVG uploads would fail silently." >&2
  exit 1
fi
echo "Importing as user $WP_USER."

# `wp eval` does not accept positional arguments in every WP-CLI build, so
# values are escaped and interpolated into the snippet instead.
php_quote() { printf "%s" "$1" | sed "s/'/\\\\'/g"; }

imported=0
reused=0
swapped=0

while IFS= read -r -d '' file; do
  rel="${file#"$MEDIA/"}"
  remote="$RAW/$rel"
  q_remote="$(php_quote "$remote")"

  # Reuse an existing attachment rather than duplicating it on a re-run.
  #
  # Keyed on a source marker written at import time, not on the filename or the
  # remote URL. Neither of those works: a successful run rewrites the remote
  # URLs out of the database, and WordPress renames colliding uploads, so
  # icon_home.svg is stored as icon_home-1.svg and never matches its own name.
  q_rel="$(php_quote "$rel")"
  id="$($WP eval "
    global \$wpdb;
    echo (int) \$wpdb->get_var( \$wpdb->prepare(
      \"SELECT post_id FROM {\$wpdb->postmeta}
         WHERE meta_key = '$SOURCE_META' AND meta_value = %s LIMIT 1\",
      '$q_rel' ) );" 2>/dev/null || echo 0)"

  if [ "${id:-0}" -gt 0 ]; then
    reused=$((reused + 1))
  else
    id="$($WP media import "$file" --user="$WP_USER" --porcelain 2>/dev/null || true)"
    if ! [[ "${id:-}" =~ ^[0-9]+$ ]]; then
      echo "  skip (import failed): $rel" >&2
      continue
    fi
    $WP post meta update "$id" "$SOURCE_META" "$rel" >/dev/null 2>&1 || true
    imported=$((imported + 1))
  fi

  local_url="$($WP eval "echo wp_get_attachment_url( $id );" 2>/dev/null || true)"
  [ -n "$local_url" ] || { echo "  skip (no url): $rel" >&2; continue; }

  # --precise so serialized options and Divi builder payloads are rewritten
  # correctly instead of having their length prefixes corrupted.
  n="$($WP search-replace "$remote" "$local_url" --precise --format=count 2>/dev/null | tail -1 || echo 0)"
  [[ "$n" =~ ^[0-9]+$ ]] || n=0
  swapped=$((swapped + n))
done < <(find "$MEDIA" -type f ! -name '.*' -print0)

echo "imported $imported, reused $reused, rewrote $swapped reference(s)"

# WordPress imports every SVG with 0x0 metadata (it cannot read SVG
# dimensions), and Divi then stamps width="0" and a "0w" srcset on any image
# module resolving to one -- the footer logo collapsed to nothing exactly this
# way. Backfill real dimensions from each SVG's viewBox.
$WP eval-file "$(cd "$(dirname "$0")" && pwd)/fix-svg-dimensions.php"
