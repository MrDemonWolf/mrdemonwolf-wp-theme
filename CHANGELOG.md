# Changelog

All notable changes to MrDemonWolf will be documented in this file.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versions correspond to git tags (`v*`) and GitHub Releases.

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

[1.0.0]: https://github.com/MrDemonWolf/mrdemonwolf-wp-theme/releases/tag/v1.0.0
