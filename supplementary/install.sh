#!/usr/bin/env bash
# Full clean install of the theme's content onto a WordPress site.
#
# Wipes all existing content, then performs the five supplementary imports in
# the order Divi requires, sideloads the media, and sets the front page and
# menu. Safe to re-run: it always starts from an empty site.
#
#   WP="wp" ./supplementary/install.sh
#   WP="/path/to/wp-wrapper" ./supplementary/install.sh
#
# Requires: Divi active as the parent theme, the mrdemonwolf child theme
# active, and the SVG Support and WordPress Importer plugins active.
set -euo pipefail

WP="${WP:-wp}"
HERE="$(cd "$(dirname "$0")" && pwd)"

step() { printf '\n== %s\n' "$1"; }

step "Preflight"
$WP core is-installed || { echo "WordPress is not installed." >&2; exit 1; }
theme="$($WP theme list --status=active --field=name)"
[ "$theme" = "mrdemonwolf" ] || { echo "Active theme is '$theme', expected 'mrdemonwolf'." >&2; exit 1; }
for p in svg-support wordpress-importer; do
  $WP plugin is-active "$p" || { echo "Plugin '$p' is not active." >&2; exit 1; }
done
echo "  ok"

step "Emptying the site"
$WP site empty --uploads --yes >/dev/null
# --uploads leaves the directory tree behind, and a stale file makes WordPress
# rename the next import to name-1.ext.
rm -rf "$($WP eval 'echo wp_upload_dir()["basedir"];')"
echo "  content and uploads cleared"

step "Divi theme options and customizer settings"
MDW_SUPP="$HERE" $WP eval-file "$HERE/apply-divi-options.php"

step "Site content"
# --authors=skip assigns everything to the current user instead of recreating
# the upstream author account.
$WP import "$HERE/All Content.xml" --authors=skip --skip=attachment 2>&1 | tail -1

step "Attaching Theme Builder templates"
$WP eval-file "$HERE/attach-theme-builder.php"

step "Media"
WP="$WP" "$HERE/import-media.sh"

step "Front page, posts page and menu"
front="$($WP post list --post_type=page --name=home --format=ids)"
posts="$($WP post list --post_type=page --name=blog --format=ids)"
$WP option update show_on_front page >/dev/null
[ -n "$front" ] && $WP option update page_on_front "$front" >/dev/null
[ -n "$posts" ] && $WP option update page_for_posts "$posts" >/dev/null
$WP menu location assign main-menu primary-menu >/dev/null 2>&1 || true
$WP rewrite flush >/dev/null 2>&1 || true
echo "  front=$front posts=$posts"

step "Clearing caches"
rm -rf "$($WP eval 'echo WP_CONTENT_DIR;')/et-cache"/* 2>/dev/null || true
$WP transient delete --all >/dev/null 2>&1 || true
$WP cache flush >/dev/null 2>&1 || true
echo "  done"

step "Result"
for t in page project service attachment; do
  printf '  %-12s %s\n' "$t" "$($WP post list --post_type=$t --post_status=any --format=count)"
done

cat <<'NOTE'

== One manual step remains

The Theme Builder templates cannot be imported from the command line. Divi's
importer for them is a chunked AJAX flow behind a nonce, and a WXR import
brings the posts in without the wiring the Theme Builder needs, so the header,
footer and archive templates do not render.

In WP Admin:

  Divi > Theme Builder > Portability (the up/down arrow, top right) > Import
  Choose  supplementary/MrDemonWolf Divi Theme Builder Templates.json
  Tick    "Import Presets"
  Import

Then, for the saved layouts used inside those templates:

  Divi > Divi Library > Import
  Choose  supplementary/MrDemonWolf Divi Theme Builder Layouts.json
  Tick    "Import Presets"

Verify with:  node tools/check-parity.js
NOTE
