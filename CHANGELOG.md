# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- `migrate.sh` no longer crashes with an arithmetic error when zero `nexus_*`
  options remain (made re-runs actually idempotent); option names are now
  SQL-escaped before the rename query.
- Translations can now load: `load_child_theme_textdomain()` is registered on
  `after_setup_theme`, and the breadcrumbs "Home" default is translatable.
- Social share shortcode returns empty outside the loop instead of emitting
  share links with blank URLs.
- Accordion close handler no longer clears Divi's animation lock on no-op
  clicks.
- Release workflow fails loudly when `CHANGELOG.md` has no entry for the
  tagged version instead of publishing an empty release body.

### Changed

- Parent (Divi) stylesheet is enqueued with the parent theme version and
  `script.js` with its file modification time, so both cache-bust correctly.
- Video popup uses delegated Magnific Popup binding and the blog-loop
  no-featured-image check re-runs after AJAX pagination, so both keep working
  on dynamically loaded content.
- CI and the release workflow build the zip via `./build.sh` (single source of
  truth, artifact now at `build/mrdemonwolf.zip`).

## [1.0.0] - 2026-04-17

Initial public release with security hardening and code optimisation.

### Features

- `service` custom post type with icon metabox.
- Breadcrumbs, tags, and social share shortcodes.
- Magnific Popup video lightbox (bundled locally, v1.1.0).
- Security hardening: login error obscuring, version hiding, ABSPATH guard.
- Cleanup notice mu-plugin with Clean Up and Dismiss actions.
- 8 SVG icons bundled in `theme/assets/`.

### Required Plugins

- Divi Theme
- SVG Support

### Code Quality

- WordPress-Core coding standards enforced via phpcs (CI).
- CSS custom properties `--mdw-bg` / `--mdw-border` for neutral palette.
- Breadcrumb helpers extracted to eliminate duplicated escaping logic.
- Service metabox JS extracted to `assets/admin-service-metabox.js` and localised via `wp_localize_script`.
- `file_put_contents()` write errors logged via `error_log()`.
