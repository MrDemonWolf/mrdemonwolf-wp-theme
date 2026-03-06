# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

MrDemonWolf is a WordPress child theme for Divi (Elegant Themes). There is no Node.js toolchain, no Composer, no bundler. The entire build is zipping directories.

- **WordPress** 6.0+, **PHP** 7.4+, **Divi** parent theme required
- Theme JS uses jQuery (from WordPress/Divi)

## Build & Lint Commands

```bash
# Build distributable zip (outputs to build/)
./build.sh

# PHP syntax check (what CI runs)
php -l theme/functions.php

# Check for leftover Nexus references (CI rejects any matches)
grep -ri "nexus" theme/ supplementary/ --include="*.php" --include="*.css" --include="*.js" --include="*.json" --include="*.xml"
```

No test suite exists. CI only lints PHP syntax and checks for forbidden "nexus" strings.

## CI/CD

- **ci.yml**: Push/PR to `main` or `dev` — PHP lint, Nexus check, zip smoke test
- **release.yml**: Push of `v*` tag — builds theme zip, creates GitHub Release via `softprops/action-gh-release@v2`

## Naming Conventions

| Context | Prefix | Example |
|---------|--------|---------|
| PHP functions, shortcodes, options | `mrdemonwolf_` | `mrdemonwolf_enqueue_styles()` |
| CSS classes | `mdw-` | `.mdw-breadcrumbs` |
| Post meta keys | `_mrdemonwolf_` | `_mrdemonwolf_service_image` |
| Theme text domain | `mrdemonwolf` | `__('...', 'mrdemonwolf')` |

CI actively rejects any file under `theme/` or `supplementary/` containing "nexus" (case-insensitive).

## Architecture

### Theme (`theme/`)

Single `functions.php` with all PHP — hooks, shortcodes, a `service` CPT with metabox, enqueues. No class hierarchy, no includes, no autoloading. `style.css` is plain CSS (no preprocessor). `script.js` is a jQuery IIFE. Third-party libs (Magnific Popup) are committed in `theme/assets/`.

On `switch_theme`, a mu-plugin (`mdw-cleanup-notice.php`) is written to offer one-click data cleanup.

### Color System

11 brand colors: 2 base (primary `#1e8a8a`, secondary `#0c1e21`) + 9 derived. Some use Divi CSS custom properties (`--gcid-*`), others are hardcoded hex in `style.css`.

### Migration

`migrate.sh` handles Nexus → MrDemonWolf migration via WP-CLI.

## Key Constraints

1. **No build toolchain** — no npm, composer, webpack, Vite
2. **PHP 7.4 compat** — no named arguments, match expressions, readonly properties, fibers
3. **Security required** — every AJAX handler needs `check_ajax_referer()` + `current_user_can()`, all output escaped
4. **`style.css` header is sacred** — `Theme Name: MrDemonWolf`, `Template: Divi` must stay in the header comment
