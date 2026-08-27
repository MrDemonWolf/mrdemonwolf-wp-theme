#!/usr/bin/env bash
# Install this theme's content onto a WordPress site, from a clean slate.
#
# Resets the database, then runs every import in the order Divi needs. The demo
# content is always included -- it is the layout the theme was designed around,
# and the pages, projects and services in it are what the Theme Builder
# templates reference.
#
#   WP="wp" ./supplementary/install.sh
#   WP="/path/to/wp-wrapper" ./supplementary/install.sh
#
# Options (environment variables):
#   WITH_CONTENT=1   also import the real blog posts and portfolio projects
#                    from the website repo (default 0, demo content only)
#   CONTENT_REPO=    path to that repo (default ~/Developer/mrdemonwolf/website)
#   NO_RESET=1       skip the database reset and import over what is there
#
# Requires: Divi active as the parent theme, this child theme active, and the
# SVG Support, WordPress Importer and WP Reset plugins active.
set -euo pipefail

WP="${WP:-wp}"
HERE="$(cd "$(dirname "$0")" && pwd)"
REPO="$(cd "$HERE/.." && pwd)"
WITH_CONTENT="${WITH_CONTENT:-0}"
NO_RESET="${NO_RESET:-0}"
CONTENT_REPO="${CONTENT_REPO:-$HOME/Developer/mrdemonwolf/website}"

step() { printf '\n== %s\n' "$1"; }

step "Preflight"
$WP core is-installed || { echo "WordPress is not installed." >&2; exit 1; }
for p in svg-support wordpress-importer; do
  if ! $WP plugin is-active "$p" >/dev/null 2>&1; then
    $WP plugin activate "$p" >/dev/null 2>&1 \
      || { echo "Plugin '$p' is missing. Install it:  wp plugin install $p --activate" >&2; exit 1; }
  fi
done
theme="$($WP theme list --status=active --field=name)"
[ "$theme" = "mrdemonwolf" ] || { echo "Active theme is '$theme', expected 'mrdemonwolf'." >&2; exit 1; }
echo "  theme and plugins ready"

if [ "$NO_RESET" = "1" ]; then
  step "Skipping reset (NO_RESET=1)"
else
  step "Resetting the database"
  if $WP plugin is-active wp-reset 2>/dev/null; then
    # Keeps the admin user, the active theme and the active plugins; drops
    # everything else. Far more thorough than `wp site empty`, which leaves
    # options, terms and Theme Builder state behind.
    $WP reset reset --yes --reactivate-theme --reactivate-plugins >/dev/null
    echo "  database reset via WP Reset"
  else
    echo "  WP Reset is not active; falling back to 'wp site empty'." >&2
    echo "  Install it for a true clean slate:  wp plugin install wp-reset --activate" >&2
    $WP site empty --uploads --yes >/dev/null
  fi
  # A leftover file makes WordPress rename the next import to name-1.ext.
  rm -rf "$($WP eval 'echo wp_upload_dir()["basedir"];')"

  # --reactivate-plugins does not reliably survive the reset, and a missing
  # importer or SVG handler fails several steps below in ways that look
  # unrelated. Re-assert them explicitly.
  $WP theme activate mrdemonwolf >/dev/null 2>&1 || true
  for p in svg-support wordpress-importer; do
    $WP plugin is-active "$p" >/dev/null 2>&1 || $WP plugin activate "$p" >/dev/null
  done
  echo "  theme and required plugins reactivated"
fi

step "Permalinks"
# Every internal link in the exports assumes this, and it must be set before
# any content is imported.
$WP option update permalink_structure '/blog/%postname%/' >/dev/null
$WP rewrite flush --hard >/dev/null 2>&1 || true
echo "  /blog/%postname%/"

step "Divi theme options, customizer settings and global colours"
MDW_SUPP="$HERE" $WP eval-file "$HERE/apply-divi-options.php"

step "Divi module presets"
# These carry the design classes (mdw-blurb-1, mdw-btn-1, ...). Without them
# modules render with no class and the stylesheet has nothing to hook onto.
MDW_SUPP="$HERE" $WP eval-file "$HERE/apply-divi-presets.php"

step "Divi Library layouts"
MDW_SUPP="$HERE" $WP eval-file "$HERE/apply-divi-layouts.php"

step "Demo content"
# --authors=skip assigns everything to the current user instead of recreating
# the upstream author account.
$WP import "$HERE/All Content.xml" --authors=skip --skip=attachment 2>&1 | tail -1

step "Attaching Theme Builder templates"
$WP eval-file "$HERE/attach-theme-builder.php"

step "Theme media"
WP="$WP" "$HERE/import-media.sh"

if [ "$WITH_CONTENT" = "1" ]; then
  step "Real blog posts and portfolio projects"
  posts="$CONTENT_REPO/docs/migration/blog/posts.xml"
  projects="$CONTENT_REPO/docs/migration/portfolio/portfolio.xml"
  for f in "$posts" "$projects"; do
    [ -f "$f" ] || { echo "  missing: $f" >&2; continue; }
    $WP import "$f" --authors=skip --skip=attachment 2>&1 | tail -1
  done

  step "Blog images"
  MEDIA_ROOT="$CONTENT_REPO/apps/website/public/media/blog" \
    $WP eval-file "$REPO/tools/import-blog-media.php"

  step "Removing the demo terms the real content replaced"
  # Done after the import, never before: the real posts match these terms by
  # slug where they overlap, so removing them first creates duplicates with
  # -2 slugs instead of reusing them.
  for t in branding consulting management marketing; do
    id="$($WP term list category --slug="$t" --field=term_id 2>/dev/null | head -1)"
    if [ -n "$id" ] && [ "$($WP term get category "$id" --field=count 2>/dev/null)" = "0" ]; then
      $WP term delete category "$id" >/dev/null 2>&1 && echo "  removed empty category: $t"
    fi
  done
fi

step "Removing WordPress stock content"
# A reset recreates Hello World, Sample Page and the Privacy Policy draft.
for slug in hello-world sample-page privacy-policy; do
  id="$($WP post list --post_type=post,page --post_status=any --name="$slug" --format=ids 2>/dev/null | head -1)"
  [ -n "$id" ] && $WP post delete "$id" --force >/dev/null 2>&1 && echo "  removed $slug"
done
id="$($WP term list category --slug=uncategorized --field=term_id 2>/dev/null | head -1)"
[ -n "$id" ] && [ "$($WP term get category "$id" --field=count 2>/dev/null)" = "0" ] \
  && $WP term delete category "$id" >/dev/null 2>&1 && echo "  removed empty category: uncategorized"

step "Front page, posts page and menu"
front="$($WP post list --post_type=page --name=home --format=ids)"
blog="$($WP post list --post_type=page --name=blog --format=ids)"
$WP option update show_on_front page >/dev/null
[ -n "$front" ] && $WP option update page_on_front "$front" >/dev/null
[ -n "$blog" ] && $WP option update page_for_posts "$blog" >/dev/null
$WP menu location assign main-menu primary-menu >/dev/null 2>&1 || true
$WP rewrite flush >/dev/null 2>&1 || true
echo "  front=$front blog=$blog"

step "Clearing caches"
rm -rf "$($WP eval 'echo WP_CONTENT_DIR;')/et-cache"/* 2>/dev/null || true
$WP transient delete --all >/dev/null 2>&1 || true
$WP cache flush >/dev/null 2>&1 || true
echo "  done"

step "Result"
for t in page project service post attachment et_pb_layout; do
  printf '  %-14s %s\n' "$t" "$($WP post list --post_type=$t --post_status=any --format=count)"
done
printf '  %-14s %s\n' "categories" "$($WP term list category --format=count)"
printf '  %-14s %s\n' "tags" "$($WP term list post_tag --format=count)"

echo
echo "Verify against the upstream demo with:  node tools/check-parity.js"
