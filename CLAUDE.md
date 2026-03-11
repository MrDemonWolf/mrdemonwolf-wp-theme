# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Repo Is

A WordPress **Divi child theme** for MrDemonWolf, Inc. The repo contains only the theme source — no WordPress core, no Divi parent theme. It is deployed by symlinking or copying `theme/` into a live WordPress install's `wp-content/themes/mrdemonwolf/`.

## Commands

```bash
# Validate PHP syntax
php -l theme/functions.php

# Check for leftover Nexus references (must return 0 matches for CI to pass)
grep -ri "nexus" theme/ supplementary/ --include="*.php" --include="*.css" --include="*.js" --include="*.json" --include="*.xml"

# Build installable zip
cd theme && zip -r ../mrdemonwolf.zip . -x "*.DS_Store" && cd ..

# Migrate from Nexus (WP-CLI required, run from WordPress root)
./migrate.sh --dry-run   # preview
./migrate.sh             # apply
```

CI runs on push/PR to `main` or `dev`: PHP lint → Nexus check → zip build. Tagged `v*` pushes trigger a release that attaches the zip.

## Architecture

### Theme Layer Split

The visual layout lives almost entirely in **Divi's database** (builder JSON stored in `wp_posts`, global colors in `wp_options`). The files in this repo handle only what Divi can't:

- **`theme/style.css`** — All custom CSS. Uses `--gcid-*` CSS custom properties for brand colors (these are set in Divi's UI, not in code). Also contains hardcoded hex/rgba values and relative `url(assets/...)` paths for SVG icons.
- **`theme/functions.php`** — Enqueues styles/scripts, registers the `service` CPT, defines three shortcodes, and installs a mu-plugin cleanup notice on theme deactivation.
- **`theme/script.js`** — jQuery: accordion close behavior, Magnific Popup init for `.mdw-video-popup`, and blog loop no-image detection.
- **`theme/assets/`** — Bundled local assets (SVG icons + Magnific Popup 1.1.0). SVG `url()` references in `style.css` are relative to the stylesheet, resolving to `wp-content/themes/mrdemonwolf/assets/`.

### Supplementary Exports

`supplementary/` contains Divi Builder JSON/XML exports that must be imported into WP Admin in a specific order (Theme Options → Theme Builder → Customizer Settings → Divi Library → All Content XML). These are the source of truth for page layouts, global colors, and typography — not the theme files.

### CSS Class & PHP Naming Conventions

- CSS utility classes: `mdw-` prefix (e.g. `mdw-card-1`, `mdw-btn-2`, `mdw-service-icon`)
- PHP functions/hooks: `mrdemonwolf_` prefix
- Post meta keys: `_mrdemonwolf_*`
- Shortcodes: `[mrdemonwolf_breadcrumbs]`, `[mrdemonwolf_tags]`, `[mrdemonwolf_social_share]`

### Brand Colors

Divi CSS variables drive most colors — change them in Divi's UI (Design Variable Manager or Customizer), not in code:

| Variable | Role | Current value |
|----------|------|---------------|
| `--gcid-primary-color` | Primary accent | `#0FACED` |
| `--gcid-secondary-color` | Dark/headings | `#091533` |
| `--gcid-heading-color` | Heading text | `#091533` |
| `--gcid-body-color` | Body text | `#3B4F66` |
| `--gcid-hhvnnvrog9` | Overlay tint | `#091533` |
| `--gcid-qn8h12q0c7` | Subtle fill/bg | `#EEF2F7` |

Hardcoded values that live only in `style.css` (not in Divi variables): `#EEF2F7`, `#C8D3E0`, `#8FA0B8`, `#5B6E8A`, and rgba variants of the primary color.

### Service CPT

Registered as post type `service` (slug: `services`). Has a custom "Icon" metabox in the admin sidebar that stores an image URL in `_mrdemonwolf_service_image`. No taxonomy — icon display is handled by Divi module layout.

### Cleanup mu-plugin

On theme deactivation, `functions.php` writes a temporary mu-plugin (`mdw-cleanup-notice.php`) to `wp-content/mu-plugins/`. This shows a one-time admin notice offering to clean up theme data. It deletes itself after the user clicks "Clean Up" or "Dismiss."
