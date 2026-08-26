#!/usr/bin/env bash
# Fails if any upstream-vendor reference survives anywhere in the repo.
#
# This script is the single home for the pattern, which is why it excludes
# itself from the scan: any file that spells the pattern out would otherwise
# match itself and the guard could never pass.
set -uo pipefail

cd "$(cd "$(dirname "$0")/.." && pwd)"

# supplementary/ must exist. `grep -r` over a missing directory exits 2, and an
# inverted exit-2 reads as success -- the guard would pass without scanning.
if [ ! -d supplementary ]; then
  echo "Error: supplementary/ is missing; the guard would pass vacuously." >&2
  exit 1
fi

PATTERN='nexus|hostingersite'

if grep -rniE "$PATTERN" \
     theme/ supplementary/ tools/ .github/ ./*.md ./*.txt build.sh \
     --exclude=branding-guard.sh 2>/dev/null; then
  echo "" >&2
  echo "Error: upstream vendor reference found (matches above)." >&2
  exit 1
fi

echo "Branding guard: clean."
