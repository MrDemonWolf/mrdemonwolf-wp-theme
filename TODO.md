# MrDemonWolf Divi Child Theme — Import & Setup Checklist

## 1. Install WordPress + Divi

1. Install WordPress on your hosting environment
2. Install and activate the **Divi** parent theme (requires Elegant Themes license)
3. Confirm Divi is active and the Visual Builder loads on a test page

## 2. Upload the Child Theme

1. Build the theme zip: `./build.sh`
2. In WP Admin: **Appearance > Themes > Add New > Upload Theme**
3. Upload `build/mrdemonwolf.zip` and activate it
4. Confirm the child theme is active (check **Appearance > Themes**)

## 3. Import Supplementary Files (Order Matters)

Import these in the **exact order** listed. Files are in the `supplementary/` folder of the repo (not in the zip).

1. **Theme Options** — Divi > Theme Options > Import/Export > Import
   - File: `MrDemonWolf Divi Theme Options.json`
2. **Theme Builder Templates** — Divi > Theme Builder > Import/Export > Import
   - File: `MrDemonWolf Divi Theme Builder Templates.json`
3. **Customizer Settings** — Divi > Theme Customizer > Import/Export > Import
   - File: `MrDemonWolf Divi Theme Customizer Settings.json`
4. **Divi Library Layouts** — Divi > Divi Library > Import/Export > Import
   - File: `MrDemonWolf Divi Theme Builder Layouts.json`
5. **All Content (XML)** — Tools > Import > WordPress > Run Importer
   - File: `All Content.xml`
   - Check "Download and import file attachments"
   - Map the author to your WP admin user

## 4. Post-Import Setup

1. **Set Homepage** — Settings > Reading > Static page > select "Home"
2. **Set Blog Page** — Settings > Reading > Posts page > select "Blog" (or "Blog grid")
3. **Assign Menu** — Appearance > Menus > assign "Navigation" to Primary Menu location
4. **Check Service CPT** — Confirm service posts appear under the Services menu
5. **Verify Media** — Check that imported images loaded correctly (re-upload any missing ones)

## 5. Set the Brand Colors

Brand colors are controlled by **Divi Global Colors**, not in theme files.

1. Go to **Divi > Theme Customizer > General Settings > Design Variable Manager**
   (Divi 5: Visual Builder > Variable Manager > Colors).
2. Set the global colors to Brand Blues v6:

| Divi variable | Set to |
|---------------|--------|
| `--gcid-primary-color` | `#3AAEE3` |
| `--gcid-secondary-color` | `#0A1633` |
| `--gcid-heading-color` | `#0A1633` |
| `--gcid-body-color` | `#1F2A40` |
| `--gcid-qn8h12q0c7` (background) | `#E6EAF1` |
| `--gcid-hhvnnvrog9` (dark 2) | `#0D2A56` |

The imported Theme Options still carry the older teal demo palette
(`#1e8a8a`, `#ecf0f0`, `#c9d1d1`), so this step is required after importing,
not optional. Full palette, type, and the dark-mode reference table:
[BRAND.md](BRAND.md).

Hardcoded colors remaining in `style.css`: `#EEF2F7` (background),
`#C8D3E0` (borders), `#5B6E8A` / `#8FA0B8` (muted text).

## 6. Keeping the Theme Updated

The theme updates itself from GitHub Releases (`Update URI:` header +
`update_themes_github.com` filter). After the first install, updates arrive as
a normal theme update under **Appearance > Themes**.

1. Bump `Version:` in `theme/style.css`, add the `CHANGELOG.md` entry.
2. `git tag vX.Y.Z && git push --tags` — CI builds and publishes the zip.
3. On the site: **Dashboard > Updates > Check again**, then update the theme.

If `DISALLOW_FILE_MODS` is set (some security plugins do), WordPress hides all
one-click updates; upload the release zip by hand instead.

Note: the `project` post type and its taxonomies are provided by Divi, not by
this theme, so no extra step is needed for the portfolio import.
