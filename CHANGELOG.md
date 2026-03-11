# Changelog

All notable changes to MrDemonWolf will be documented in this file.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versions correspond to git tags (`v*`) and GitHub Releases.

---

## [1.1.0] — 2026-03-11

### Added
- **7 bundled SVG icons** — `icon_circles`, `icon_arrow_btn`, `icon_arrow_btn_2`,
  `icon_home`, `icon_arrow_header`, `separator`, `icon_square` now live in
  `theme/assets/` with updated brand fill colors; no WP Media upload required.
- **`icon_arrow_btn_2.svg`** — new icon for `.mdw-btn-3` (arrow on light
  background, fill `#0FACED`).
- **`CLAUDE.md`** — repository guidance for Claude Code (commands, architecture,
  conventions, brand color reference).
- **`DESIGN.md`** — WP Admin and Divi UI reference for global colors, typography,
  and per-section content guidance.

### Changed
- **Brand palette rebrand** — replaced all teal/old-brand values in `style.css`:
  - `#1e8a8a` rgba variants → `#0FACED` / `rgba(15, 172, 237, …)`
  - `#0c1e21` → `#091533`
  - `#ecf0f0` → `#EEF2F7` (including SVG data URI)
  - `#c9d1d1` → `#C8D3E0`
  - `#a9b8b8` → `#8FA0B8`
  - `#67787a` → `#5B6E8A`
- **SVG CSS references** — all `/wp-content/uploads/*.svg` paths replaced with
  `url(assets/...)` relative paths.
- **Conditional Magnific Popup loading** — CSS and JS now only enqueued on pages
  whose content contains `mdw-video-popup`; eliminates wasted assets on all
  other pages.
- **Dynamic script version** — `script.js` now uses `wp_get_theme()->get('Version')`
  for automatic cache busting on every release.
- **Theme version** bumped to `1.1.0` in `style.css` and `readme.txt`.

### Removed
- **`preview/cleanup-notice.html`** — development preview file removed from repo.

---

## [1.0.0] — 2026-03-09

### Added
- **Service CPT** — `mrdemonwolf_service` custom post type with admin
  icon metabox (`_mrdemonwolf_service_image`).
- **Breadcrumbs shortcode** — `[mrdemonwolf_breadcrumbs]` with
  WooCommerce, project, and archive support.
- **Tags shortcode** — `[mrdemonwolf_tags]` for inline post/project tags.
- **Social share shortcode** — `[mrdemonwolf_social_share]` for Facebook,
  X (Twitter), and LinkedIn.
- **Video lightbox** — Magnific Popup bundled locally (`theme/assets/`);
  no CDN dependency.
- **Security hardening** — SVG uploads restricted to admins, login error
  messages obscured, WordPress version hidden from front end.
- **Cleanup notice mu-plugin** — written on `switch_theme`; surfaces an
  admin notice with:
  - **Remove all MrDemonWolf data** — deletes Service posts and post meta,
    then removes itself.
  - **Dismiss** — removes the mu-plugin without touching data.
- **CI workflow** (`ci.yml`) — PHP lint, Nexus-string guard, zip smoke
  test on push/PR to `main` or `dev`.
- **Release workflow** (`release.yml`) — builds theme zip and creates a
  GitHub Release on `v*` tag push.
- **Frontend preview** (`preview/cleanup-notice.html`) — standalone
  browser preview of the cleanup admin notice; not included in the
  distributable zip.
- **Migration script** (`migrate.sh`) — WP-CLI–based Nexus → MrDemonWolf
  data migration.

[1.1.0]: https://github.com/MrDemonWolf/mrdemonwolf-wp-theme/releases/tag/v1.1.0
[1.0.0]: https://github.com/MrDemonWolf/mrdemonwolf-wp-theme/releases/tag/v1.0.0
