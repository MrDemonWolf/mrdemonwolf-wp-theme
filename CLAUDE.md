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
./build.sh

# Output goes to build/mrdemonwolf.zip
# Note: the zip contains only the installable theme files; supplementary/ Divi
# export files are distributed separately and must be imported manually.

# Migrate from Nexus (WP-CLI required, run from WordPress root)
./migrate.sh --dry-run   # preview
./migrate.sh             # apply
```

CI runs on push/PR to `main` or `dev`: PHP lint → Nexus check → zip build. Tagged `v*` pushes trigger a release that attaches the zip.

## Architecture

### Directory Structure

```
mrdemonwolf-wp-theme/
├── .github/workflows/         # CI/CD pipelines
│   ├── ci.yml                 # Lint, validate, and build
│   └── release.yml            # Tagged release publisher
├── theme/                     # Child theme
│   ├── assets/                # Bundled assets (Magnific Popup 1.1.0)
│   │   ├── jquery.magnific-popup.min.js
│   │   └── magnific-popup.min.css
│   ├── functions.php          # Theme functions and shortcodes
│   ├── license.txt            # GPL v2 license
│   ├── screenshot.jpg         # Theme screenshot
│   ├── script.js              # Frontend JavaScript
│   └── style.css              # Theme stylesheet
├── supplementary/             # Divi Builder import files
│   ├── All Content.xml
│   ├── MrDemonWolf Divi Theme Builder Layouts.json
│   ├── MrDemonWolf Divi Theme Builder Templates.json
│   ├── MrDemonWolf Divi Theme Customizer Settings.json
│   └── MrDemonWolf Divi Theme Options.json
├── build.sh                   # Build script
├── migrate.sh                 # WP-CLI migration script
├── TODO.md                    # Import & setup checklist
└── CLAUDE.md                  # This file
```

### Theme Layer Split

The visual layout lives almost entirely in **Divi's database** (builder JSON stored in `wp_posts`, global colors in `wp_options`). The files in this repo handle only what Divi can't:

- **`theme/style.css`** — All custom CSS. Uses `--gcid-*` CSS custom properties for brand colors (these are set in Divi's UI, not in code). Also contains hardcoded hex/rgba values.
- **`theme/functions.php`** — Enqueues styles/scripts, registers the `service` CPT, defines three shortcodes, disables year/month upload folders, and installs a mu-plugin cleanup notice on theme deactivation.
- **`theme/script.js`** — jQuery: accordion close behavior, Magnific Popup init for `.mdw-video-popup`, and blog loop no-image detection.
- **`theme/assets/`** — Bundled Magnific Popup 1.1.0 (CSS + JS) and SVG icons used by CSS pseudo-elements.

### Required Plugins

- **[SVG Support](https://wordpress.org/plugins/svg-support/)** — Required for SVG upload and rendering in the Media Library. The theme does not include its own SVG upload handling; this plugin must be installed and activated.

### Supplementary Exports

`supplementary/` contains Divi Builder JSON/XML exports that must be imported into WP Admin in this order:

1. **Theme Options** — `MrDemonWolf Divi Theme Options.json`
2. **Theme Builder Templates** — `MrDemonWolf Divi Theme Builder Templates.json`
3. **Customizer Settings** — `MrDemonWolf Divi Theme Customizer Settings.json`
4. **Theme Builder Layouts** — `MrDemonWolf Divi Theme Builder Layouts.json`
5. **All Content** — `All Content.xml`

See `TODO.md` for the full step-by-step import and setup checklist.

### CSS Class & PHP Naming Conventions

- CSS utility classes: `mdw-` prefix (e.g. `mdw-card-1`, `mdw-btn-2`, `mdw-service-icon`)
- PHP functions/hooks: `mrdemonwolf_` prefix
- Post meta keys: `_mrdemonwolf_*`
- Shortcodes: `[mrdemonwolf_breadcrumbs]`, `[mrdemonwolf_tags]`, `[mrdemonwolf_social_share]`

### Brand Colors

Divi CSS variables drive most colors — change them in Divi's UI (Design Variable Manager or Customizer), not in code:

The brand is **Brand Blues v6**, defined in the private `MrDemonWolf/website`
repo (`apps/website/src/styles/site.css`). Full palette, the Divi variable
mapping, and the dark-mode reference table live in [BRAND.md](BRAND.md), which
is the reference to use from inside this repo.

| Divi variable | Role | Set to |
|----------|------|---------------|
| `--gcid-primary-color` | Primary accent | `#3AAEE3` |
| `--gcid-secondary-color` | Dark / surfaces | `#0A1633` |
| `--gcid-heading-color` | Heading text | `#0A1633` |
| `--gcid-body-color` | Body text | `#1F2A40` |

Every `var(--gcid-*)` in `theme/style.css` carries a Brand Blues hex fallback.
The `supplementary/` exports still carry the old teal demo palette (`#1e8a8a`,
`#ecf0f0`, `#c9d1d1`), so reset the global colors in the Divi UI after
importing Theme Options. Dark mode exists in the Astro reference only; the
child theme ships no dark CSS.

### Post Types

`project` (with `project_category` and `project_tag`) is registered by **Divi**,
not by this theme. The breadcrumb and tag shortcodes branch on it; nothing here
registers it and nothing should.

### Service CPT

Registered as post type `service` (slug: `services`). Has a custom "Icon" metabox in the admin sidebar that stores an image URL in `_mrdemonwolf_service_image`. No taxonomy — icon display is handled by Divi module layout.

### Cleanup mu-plugin

On theme deactivation, `functions.php` writes a temporary mu-plugin (`mdw-cleanup-notice.php`) to `wp-content/mu-plugins/`. This shows a one-time admin notice offering to clean up theme data. It deletes itself after the user clicks "Clean Up" or "Dismiss."

## Import Notes

- The `<wp:comment_author>` tags in `All Content.xml` do **not** create WordPress users on import — they are only metadata on comments. Map the content author to your existing WP admin user during import.
- See `TODO.md` for the complete import and post-import setup checklist.
