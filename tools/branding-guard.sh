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
     theme/ supplementary/ .github/ ./*.md ./*.txt build.sh 2>/dev/null; then
  echo "" >&2
  echo "Error: upstream vendor reference found (matches above)." >&2
  exit 1
fi

# The text scan above is blind to images: a PNG of the vendor wordmark contains
# no vendor string, so `supplementary/media/logo.png` shipped undetected until
# it was removed by hand. Vendor demo photography is fine to redistribute and is
# deliberately kept, so this is a denylist of specific artwork rather than a
# blanket rule. Add a hash here whenever vendor branding is removed.
VENDOR_ART_SHA256='
9831d005647163210c4087d9451638d204b71ee2fff3b72fa41617f75f05550b  Nexus wordmark (was supplementary/media/logo.png)
'

if [ -d supplementary/media ]; then
  while read -r banned label; do
    [ -z "$banned" ] && continue
    while IFS= read -r f; do
      if [ "$(shasum -a 256 "$f" | cut -d' ' -f1)" = "$banned" ]; then
        echo "Error: vendor artwork is back: $f" >&2
        echo "       matches $label" >&2
        exit 1
      fi
    done < <(find supplementary/media -type f)
  done <<< "$VENDOR_ART_SHA256"
fi

echo "Branding guard: clean."
