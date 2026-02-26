# MrDemonWolf - Custom Divi Child Theme

A custom WordPress child theme built on the Divi Theme
Builder by MrDemonWolf, Inc. It includes pre-built page
layouts, a "Service" custom post type, breadcrumbs,
social sharing, and a migration path for sites previously
running the Nexus Divi child theme.

Your WordPress site, your brand.

## Features

- **Divi Child Theme** - Extends the Divi Theme Builder
  with custom styles, layouts, and components.
- **Service Custom Post Type** - Register and manage
  services with a dedicated icon metabox in the admin.
- **Breadcrumbs Shortcode** - Automatic breadcrumb
  navigation with WooCommerce, project, and archive
  support via `[mrdemonwolf_breadcrumbs]`.
- **Tags Shortcode** - Display post or project tags
  inline with `[mrdemonwolf_tags]`.
- **Social Share Shortcode** - One-click sharing to
  Facebook, X, and LinkedIn via
  `[mrdemonwolf_social_share]`.
- **Video Popup** - Magnific Popup integration for
  lightbox video embeds (bundled locally).
- **Security Hardened** - SVG uploads restricted to
  admins, login error messages obscured, WordPress
  version number hidden, capability checks on all saves.
- **Pre-built Layouts** - Supplementary Divi Builder JSON
  and XML exports for Theme Builder, Library, Theme
  Options, and Customizer settings.
- **Migration Script** - WP-CLI script to migrate an
  existing Nexus-based site to MrDemonWolf with dry-run
  support.

## Getting Started

1. Install and activate the
   [Divi Theme](https://www.elegantthemes.com/gallery/divi/)
   on your WordPress site.
2. Download or clone this repository.
3. Upload the theme folder to
   `wp-content/themes/mrdemonwolf-wp-theme`.
4. Activate the **MrDemonWolf** child theme from
   **Appearance > Themes** in wp-admin.
5. Import the supplementary files (see Usage below).

## Usage

### Shortcodes

| Shortcode                     | Description                      |
| ----------------------------- | -------------------------------- |
| `[mrdemonwolf_breadcrumbs]`   | Renders breadcrumb navigation    |
| `[mrdemonwolf_tags]`          | Displays current post/project tags |
| `[mrdemonwolf_social_share]`  | Renders social share links       |

### Importing Supplementary Files

The `supplementary/` directory contains pre-built Divi
configuration exports:

1. **Theme Builder** - Go to **Divi > Theme Builder >
   Portability > Import** and upload
   `MrDemonWolfThemeBuilder.json`.
2. **Divi Library** - Go to **Divi > Divi Library >
   Import & Export > Import** and upload
   `MrDemonWolf Divi Library.json`.
3. **Theme Options** - Go to **Divi > Theme Options >
   Import & Export > Import** and upload
   `MrDemonWolf Divi Theme Options.json`.
4. **Customizer Settings** - Go to **Divi > Theme
   Customizer > Export & Import > Import** and upload
   `MrDemonWolf Divi Customizer Settings.json`.
5. **All Content** - Go to **Tools > Import > WordPress**
   and upload `All Content.xml`.

### Migrating from Nexus

If you are switching from the Nexus Divi Child Theme,
a WP-CLI migration script is included. **Back up your
database first.**

```bash
# Preview changes without applying them
wp eval-file migrate.php --dry-run

# Apply the migration
wp eval-file migrate.php
```

**WARNING:** This will overwrite existing Nexus theme
data. Run at your own risk. Back up your database first.

The script migrates:

- Shortcodes in post content
- CSS classes (`nexus-` to `mdw-`)
- Post meta keys (`_nexus_service_image` to
  `_mrdemonwolf_service_image`)
- HTML IDs in Divi builder data
- `wp_options` entries with the `nexus_` prefix

The migration is idempotent and safe to run multiple
times.

## Tech Stack

| Layer       | Technology                        |
| ----------- | --------------------------------- |
| CMS         | WordPress                         |
| Theme       | Divi (parent) + MrDemonWolf (child) |
| Language    | PHP, CSS, JavaScript (jQuery)     |
| Lightbox    | Magnific Popup 1.1.0 (bundled)    |
| Migration   | WP-CLI                            |

## Development

### Prerequisites

- WordPress 6.0+
- Divi Theme (latest version)
- PHP 7.4+
- WP-CLI (for migration script only)

### Setup

1. Clone the repository:

```bash
git clone https://github.com/mrdemonwolf/mrdemonwolf-wp-theme.git
```

2. Symlink or copy into your WordPress themes directory:

```bash
ln -s /path/to/mrdemonwolf-wp-theme /path/to/wordpress/wp-content/themes/mrdemonwolf-wp-theme
```

3. Activate the theme in wp-admin.

### Code Quality

- All PHP functions are prefixed with `mrdemonwolf_`
  to avoid namespace collisions.
- CSS classes use the `mdw-` prefix.
- Nonce verification and capability checks on all
  form handlers.
- SVG uploads restricted to administrators only.
- Translatable strings use the `mrdemonwolf` text
  domain.

## Project Structure

```
mrdemonwolf-wp-theme/
├── assets/                    # Bundled third-party assets
│   ├── icon_portfolio.svg
│   ├── jquery.magnific-popup.min.js
│   └── magnific-popup.min.css
├── supplementary/             # Divi Builder import files
│   ├── All Content.xml
│   ├── MrDemonWolf Divi Customizer Settings.json
│   ├── MrDemonWolf Divi Library.json
│   ├── MrDemonWolf Divi Theme Options.json
│   └── MrDemonWolfThemeBuilder.json
├── functions.php              # Theme functions and shortcodes
├── license.txt                # GPL v2 license
├── migrate.php                # WP-CLI migration script
├── screenshot.jpg             # Theme screenshot
├── script.js                  # Frontend JavaScript
└── style.css                  # Theme stylesheet
```

## License

![GitHub license](https://img.shields.io/github/license/mrdemonwolf/mrdemonwolf-wp-theme.svg?style=for-the-badge&logo=github)

## Contact

Have questions or feedback?

- Discord: [Join my server](https://mrdwolf.net/discord)

---

Made with love by [MrDemonWolf, Inc.](https://www.mrdemonwolf.com)
