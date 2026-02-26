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
2. Download `mrdemonwolf.zip` from the latest release (or
   build it from source — see Development below).
3. Go to **Appearance > Themes > Add New > Upload Theme**
   and upload `mrdemonwolf.zip`.
4. Activate the **MrDemonWolf** child theme.
5. Follow the import steps below to load the pre-built
   content and layouts.

## Usage

### Shortcodes

| Shortcode                     | Description                      |
| ----------------------------- | -------------------------------- |
| `[mrdemonwolf_breadcrumbs]`   | Renders breadcrumb navigation    |
| `[mrdemonwolf_tags]`          | Displays current post/project tags |
| `[mrdemonwolf_social_share]`  | Renders social share links       |

### Importing Supplementary Files (Follow This Order)

The `supplementary/` directory contains pre-built Divi
configuration exports. **Import them in this exact order**
to avoid broken references:

1. **All Content** - Go to **Tools > Import > WordPress**
   and upload `All Content.xml`. This creates posts,
   pages, media, and custom post types that the layouts
   reference. When prompted, check "Download and import
   file attachments."
2. **Theme Options** - Go to **Divi > Theme Options >
   Import & Export > Import** and upload
   `MrDemonWolf Divi Theme Options.json`. This sets
   global colors, fonts, button styles, and header/footer
   defaults.
3. **Customizer Settings** - Go to **Divi > Theme
   Customizer > Export & Import > Import** and upload
   `MrDemonWolf Divi Customizer Settings.json`. This
   applies color palette, typography, and spacing
   overrides.
4. **Divi Library** - Go to **Divi > Divi Library >
   Import & Export > Import** and upload
   `MrDemonWolf Divi Library.json`. This loads reusable
   layout modules and sections.
5. **Theme Builder** - Go to **Divi > Theme Builder >
   Portability > Import** and upload
   `MrDemonWolfThemeBuilder.json`. This assigns header,
   footer, and page templates that reference the Library
   items from step 4.

### Migrating from Nexus

If you are switching from the Nexus Divi Child Theme,
run the included migration script or execute the
commands manually. Requires
[WP-CLI](https://wp-cli.org/).

> **WARNING:** This will overwrite existing Nexus theme
> data in your database. **Back up your database before
> running any of these commands.** Run at your own risk.

#### Option A: Run the script

```bash
# Preview changes (no writes)
./migrate.sh --dry-run

# Apply the migration
./migrate.sh
```

#### Option B: Run the commands manually

```bash
# 1. Shortcodes
wp search-replace "[Nexus_breadcrumbs" "[mrdemonwolf_breadcrumbs" --precise
wp search-replace "[nexus_tags" "[mrdemonwolf_tags" --precise
wp search-replace "[nexus_social_share" "[mrdemonwolf_social_share" --precise

# 2. CSS classes (Divi builder data)
wp search-replace "nexus-" "mdw-" --precise

# 3. Post meta keys
wp db query "UPDATE $(wp db prefix)postmeta SET meta_key = '_mrdemonwolf_service_image' WHERE meta_key = '_nexus_service_image';"

# 4. Options table
wp db query "UPDATE $(wp db prefix)options SET option_name = REPLACE(option_name, 'nexus_', 'mrdemonwolf_') WHERE option_name LIKE 'nexus\_%';"
```

All commands are idempotent and safe to run multiple
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

2. Symlink or copy the `theme/` directory into your
   WordPress themes directory:

```bash
ln -s /path/to/mrdemonwolf-wp-theme/theme /path/to/wordpress/wp-content/themes/mrdemonwolf
```

3. Activate the theme in wp-admin.

### Building the Zip

To create an installable zip from the repo:

```bash
cd theme && zip -r ../mrdemonwolf.zip . -x "*.DS_Store" && cd ..
```

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
├── theme/                     # WordPress child theme
│   ├── assets/                # Bundled third-party assets
│   │   ├── icon_portfolio.svg
│   │   ├── jquery.magnific-popup.min.js
│   │   └── magnific-popup.min.css
│   ├── functions.php          # Theme functions and shortcodes
│   ├── license.txt            # GPL v2 license
│   ├── screenshot.jpg         # Theme screenshot
│   ├── script.js              # Frontend JavaScript
│   └── style.css              # Theme stylesheet
├── supplementary/             # Divi Builder import files
│   ├── All Content.xml
│   ├── MrDemonWolf Divi Customizer Settings.json
│   ├── MrDemonWolf Divi Library.json
│   ├── MrDemonWolf Divi Theme Options.json
│   └── MrDemonWolfThemeBuilder.json
├── migrate.sh                 # WP-CLI migration script
└── mrdemonwolf.zip            # Installable theme zip
```

## License

![GitHub license](https://img.shields.io/github/license/mrdemonwolf/mrdemonwolf-wp-theme.svg?style=for-the-badge&logo=github)

## Contact

Have questions or feedback?

- Discord: [Join my server](https://mrdwolf.net/discord)

---

Made with love by [MrDemonWolf, Inc.](https://www.mrdemonwolf.com)
