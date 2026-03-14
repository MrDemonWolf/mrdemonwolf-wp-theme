#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")" && pwd)"
BUILD_DIR="$REPO_ROOT/build"
mkdir -p "$BUILD_DIR"

THEME_DIR="$REPO_ROOT/theme"
echo "Building theme..."

# Build theme zip
rm -f "$BUILD_DIR/mrdemonwolf.zip"
cd "$THEME_DIR"
zip -r "$BUILD_DIR/mrdemonwolf.zip" . -x "*.DS_Store"

echo "Built build/mrdemonwolf.zip"
