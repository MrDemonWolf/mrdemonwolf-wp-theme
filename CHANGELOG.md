# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-08-23

### Added

- One-click theme updates from GitHub Releases: `Update URI:` header in
  `style.css` plus an `update_themes_github.com` filter in `functions.php`.
  No plugin and no server credentials; the release check is cached for 12
  hours and fails closed when GitHub is unreachable.
- `BreadcrumbList` JSON-LD from `[mrdemonwolf_breadcrumbs]`, suppressed when
  Rank Math's own breadcrumbs are enabled and filterable via
  `mrdemonwolf_breadcrumbs_schema`.
- `BRAND.md`: Brand Blues v6 palette, the Divi global-color mapping, and the
  dark-mode token table (reference only, no dark CSS ships).
- CI runs `php -l` on PHP 8.1, 8.3, and 8.4, and asserts the built zip unpacks
  to a single `mrdemonwolf/` folder. Release CI verifies the `style.css`
  version matches the tag.

### Fixed

- `build.sh` produced a zip with no root folder, which WordPress rejects when
  updating a theme in place. The archive now contains `mrdemonwolf/`.
- The cleanup mu-plugin is written through `WP_Filesystem` and skipped when
  `DISALLOW_FILE_MODS` is set or `mu-plugins` is not writable, instead of
  calling `file_put_contents()` on a hardened install.
- The accordion handler is delegated, so it survives Divi re-rendering a
  module, and the blog-loop featured-image check now runs from a
  `MutationObserver` rather than jQuery's `ajaxComplete`, which never fires
  for Divi 5 module scripts.

### Changed

- Theme headers: added `Text Domain`, `Requires at least: 6.5`,
  `Tested up to: 6.9`, `Requires PHP: 8.1`, `License`, `Theme URI`, and
  `Update URI`. `readme.txt` updated to match.
- Every `var(--gcid-*)` in `style.css` now carries a Brand Blues hex fallback,
  so a missing Divi global color renders on-brand instead of nothing.
- Breadcrumbs are assembled as data once and rendered to both markup and
  schema; the `mrdemonwolf_breadcrumb_link`/`_current`/`_primary_term_link`
  helpers were replaced by `mrdemonwolf_breadcrumb_trail()` and
  `mrdemonwolf_primary_term_crumb()`.

## [1.0.1] - 2026-06-11

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
