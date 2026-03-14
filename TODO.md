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

## 5. Customize Colors (Optional)

Brand colors are controlled by **Divi Global Colors**, not in theme files.

To change colors in the UI:
1. Go to **Divi > Theme Customizer > General Settings > Design Variable Manager**
2. Edit the global color variables to your preferred palette

Current theme ships with a neutral teal palette (`#1e8a8a`). Update Divi global colors to your preferred palette.

Hardcoded colors in `style.css` (change manually if rebranding):
`#ecf0f0` (background), `#c9d1d1` (borders/muted).

### Recommended Color Shades

Based on `#00ACED` and `#091533` (from the MrDemonWolf logo):

**Blue `#00ACED` shades:**

| Shade | Hex | Use case |
|-------|-----|----------|
| Light | `#6BC8F6` | Highlights, light backgrounds, tags |
| Base | `#00ACED` | Primary buttons, links, accents |
| Hover | `#008ABD` | Button hover (darker), active states |

**Dark navy `#091533` shades:**

| Shade | Hex | Use case |
|-------|-----|----------|
| Base | `#091533` | Headings, dark backgrounds, overlays |
| Hover | `#0F2147` | Hover-lighten on dark elements |
