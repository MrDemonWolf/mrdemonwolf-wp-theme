#!/usr/bin/env bash
set -euo pipefail

BUILD_DIR="build"
mkdir -p "$BUILD_DIR"
rm -f "$BUILD_DIR/mrdemonwolf.zip" "$BUILD_DIR/mrdemonwolf-color-preview.zip"

echo "Building theme zip..."
(cd theme && zip -r "../$BUILD_DIR/mrdemonwolf.zip" . -x "*.DS_Store")

echo "Building plugin zip..."
(cd plugin/mrdemonwolf-color-preview && zip -r "../../$BUILD_DIR/mrdemonwolf-color-preview.zip" . -x "*.DS_Store")

echo "Done!"
ls -lh "$BUILD_DIR/mrdemonwolf.zip" "$BUILD_DIR/mrdemonwolf-color-preview.zip"
