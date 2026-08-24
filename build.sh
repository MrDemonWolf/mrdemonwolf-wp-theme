#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")" && pwd)"
BUILD_DIR="$REPO_ROOT/build"
THEME_DIR="$REPO_ROOT/theme"
# The zip must contain a single root folder named after the stylesheet
# directory, or WordPress refuses the package on update.
SLUG="mrdemonwolf"
STAGE="$BUILD_DIR/stage"

echo "Building theme..."

rm -rf "$STAGE"
rm -f "$BUILD_DIR/$SLUG.zip"
mkdir -p "$STAGE/$SLUG"

cp -R "$THEME_DIR/." "$STAGE/$SLUG/"
find "$STAGE" -name ".DS_Store" -delete

cd "$STAGE"
zip -rq "$BUILD_DIR/$SLUG.zip" "$SLUG"
cd "$REPO_ROOT"
rm -rf "$STAGE"

echo "Built build/$SLUG.zip"
