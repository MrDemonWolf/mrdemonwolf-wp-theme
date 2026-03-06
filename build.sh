#!/usr/bin/env bash
set -euo pipefail
mkdir -p build
cd theme
zip -r ../build/mrdemonwolf.zip . -x "*.DS_Store"
echo "Built build/mrdemonwolf.zip"
