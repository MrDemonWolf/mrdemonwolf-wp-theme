# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-04-17

Initial public release.

### Added
- `service` custom post type with icon metabox.
- Shortcodes: `[mrdemonwolf_breadcrumbs]`, `[mrdemonwolf_tags]`, `[mrdemonwolf_social_share]`.
- Bundled Magnific Popup 1.1.0 for video popups.
- Cleanup mu-plugin notice on theme deactivation.
- CI (PHP lint, phpcs with WordPress Coding Standards, Nexus reference check, zip build) and tagged release workflow.
- CSS custom properties `--mdw-bg` and `--mdw-border` for neutral palette.
- Dedicated `theme/assets/admin-service-metabox.js` with `wp_localize_script` for i18n strings; scoped to the service edit screen via `admin_enqueue_scripts`.
- Breadcrumb helpers `mrdemonwolf_breadcrumb_link()`, `mrdemonwolf_breadcrumb_current()`, and `mrdemonwolf_primary_term_link()`.
- `mrdemonwolf_mu_dir()` helper for mu-plugins path resolution.
- README documentation for the Magnific Popup pin and upgrade path.

### Changed
- Breadcrumbs shortcode refactored to use the new helpers — removes duplicated anchor/span escaping across WooCommerce, post, project, page, category, and archive branches.
- Social share shortcode uses `rawurlencode()` instead of `urlencode()` for query-string URL components.
- Hardcoded `#EEF2F7` and `#C8D3E0` values in `style.css` replaced with `var(--mdw-bg)` / `var(--mdw-border)`.

### Security / Robustness
- `file_put_contents()` return value is now checked when writing the cleanup mu-plugin; failures are reported via `error_log()`.
