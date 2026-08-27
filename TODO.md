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
6. **Connect the newsletter** — Divi > Theme Options > API > Email Account, add a
   Brevo (Sendinblue) account named `MrDemonWolf`. The footer signup module ships
   with that name as a placeholder so the form renders; until a real account is
   connected the fields display but submissions will not reach a list. Renaming
   the account to something else means updating the module to match, because
   Divi hides the entire form when the name does not resolve.

## 5. Colors (nothing to do unless rebranding)

The theme and the supplementary exports ship the same teal default palette,
verified color-for-color against each other. Importing the exports sets the
Divi global colors and Customizer values to match, so the site is consistent
out of the box.

| Role | Value |
|------|-------|
| Accent (`accent_color`, `link_color`) | `#1e8a8a` |
| Dark / headings (`secondary_accent_color`, `header_color`) | `#0c1e21` |
| Body text (`font_color`) | `#364e52` |
| Light background (`gcid-xsweq3oku6`) | `#ecf0f0` |
| Background 2 (`gcid-qn8h12q0c7`) | `#d8e5e5` |
| Dark color 2 (`gcid-hhvnnvrog9`) | `#18292c` |

To rebrand from your mockup, work all six color locations in
[COLORS.md](COLORS.md) — Divi global colors, Customizer keys, `style.css`
(including an `rgba()` set and a `%23`-encoded hex), the `theme/assets/*.svg`
fills, the `supplementary/` exports, and the database.

## 5b. Media

Demo page media is self-hosted from this repository under
`supplementary/media/`, so imports do not depend on any third-party site
staying online. Imported posts and projects still carry their
`www.mrdemonwolf.com` image URLs. When you reupload images with proper alt
text and folder structure, swap each in with:

```bash
wp search-replace "https://www.mrdemonwolf.com/wp-content/uploads/OLD.jpg" "https://your-site/wp-content/uploads/NEW.jpg" --precise
```

Project featured images are not attachments at all — each project carries the
URL in a `_mdw_featured_image_url` meta field, so the 16 get set by hand.

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
