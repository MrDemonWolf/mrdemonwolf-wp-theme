# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
