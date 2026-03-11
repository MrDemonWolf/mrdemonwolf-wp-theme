# Changelog

All notable changes to MrDemonWolf will be documented in this file.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versions correspond to git tags (`v*`) and GitHub Releases.

---

## [1.0.0] — 2026-03-11

### Added
- **Service CPT** — `service` custom post type with admin icon metabox
  (`_mrdemonwolf_service_image`).
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
  admin notice with Remove and Dismiss actions.
- **7 bundled SVG icons** — `icon_circles`, `icon_arrow_btn`,
  `icon_arrow_btn_2`, `icon_home`, `icon_arrow_header`, `separator`,
  `icon_square` in `theme/assets/` with brand fill colors; no WP Media
  upload required.
- **CI workflow** (`ci.yml`) — PHP lint, Nexus-string guard, zip smoke
  test on push/PR to `main` or `dev`.
- **Release workflow** (`release.yml`) — builds theme zip and creates a
  GitHub Release on `v*` tag push.
- **Migration script** (`migrate.sh`) — WP-CLI–based Nexus → MrDemonWolf
  data migration.
- **`CLAUDE.md`** — repository guidance for Claude Code.
- **`DESIGN.md`** — WP Admin and Divi UI brand reference guide.

### Changed
- **Brand palette** — Electric Blue (`#0FACED`) primary, Deep Navy
  (`#091533`) secondary; all teal/old-brand hex and rgba values replaced
  in `style.css`.
- **SVG CSS references** — all `/wp-content/uploads/*.svg` paths use
  `url(assets/...)` relative paths pointing to the bundled theme assets.
- **Conditional Magnific Popup loading** — CSS and JS only enqueued on
  pages whose content contains `mdw-video-popup`.
- **Dynamic script version** — `script.js` uses
  `wp_get_theme()->get('Version')` for automatic cache busting.

[1.0.0]: https://github.com/MrDemonWolf/mrdemonwolf-wp-theme/releases/tag/v1.0.0
