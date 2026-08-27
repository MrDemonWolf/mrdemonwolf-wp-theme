# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-08-27

First public release of the theme as a self-contained product. Earlier tags
(1.0.0 through 1.1.2) were incremental work on a layout that has since been
rebuilt, and were withdrawn rather than carried forward.

### Features

- **Service custom post type** with an icon metabox, served from `/services/`
  with `with_front => false` so it survives a `/blog/%postname%/` permalink
  structure.
- **Three shortcodes** — `[mrdemonwolf_breadcrumbs]`, `[mrdemonwolf_tags]`,
  `[mrdemonwolf_social_share]`. Breadcrumbs handle WooCommerce, the Divi
  `project` post type, pages, categories and archives, and emit
  BreadcrumbList JSON-LD unless an SEO plugin already does.
- **Magnific Popup 1.1.0 bundled locally** for the video lightbox. No CDN
  dependency and no third-party request at runtime.
- **Self-updating from GitHub Releases** through the `Update URI` header. The
  updater requires a non-draft, non-prerelease release carrying an asset named
  `mrdemonwolf.zip`, and caches the lookup for 12 hours.
- **Security hardening** — login errors made generic, the WordPress generator
  version removed from the front end, and year/month upload folders disabled on
  theme activation.
- **Cleanup notice** — deactivating the theme writes a temporary mu-plugin that
  offers one-click removal of all theme data, or a dismiss that leaves it intact.
  It writes through `WP_Filesystem`, is skipped entirely when
  `DISALLOW_FILE_MODS` is set, and deletes itself afterwards.
- **Divi Theme Builder exports** in `supplementary/` — theme options, builder
  layouts, builder templates, customizer settings, and site content. All demo
  media is self-hosted from this repository rather than hotlinked to a
  third-party host, so imports do not depend on an external site staying up.
- **7 SVG icons** bundled in `theme/assets/`, referenced from CSS pseudo-elements.
  No Media Library upload required.

### Notes

- Both logo rules use explicit pixel widths with `!important`: Divi ships an
  inline `img[src*=".svg"] { width: auto }` rule after the child stylesheet,
  which otherwise wins the cascade on order and collapses the size-less SVG
  wordmark to nothing.
- The header logo module is constrained to hug its wordmark. The brand SVG
  carries only a `viewBox`, so it has no intrinsic size and the module absorbed
  the header's spare width — squeezing the menu until the search icon overlapped
  "Contact" and the call-to-action wrapped onto two lines.
- The footer newsletter ships a placeholder Brevo account name. Divi hides the
  entire signup form when that name is empty, so a blank value rendered the
  heading with no email field beneath it. See TODO.md for connecting a real
  account.
- No vendor artwork is redistributed. The upstream wordmark PNG and its
  attachment record are stripped from the exports, and the branding guard now
  checks image hashes as well as text — a logo file contains none of the strings
  a text scan looks for.
- Layout modules no longer reference the upstream logo's attachment ID. It was
  never re-registered for our own assets, and after a content import that ID
  belongs to an unrelated post.
- The child stylesheet cache-busts on the **child** theme version. Divi enqueues
  it with the parent's version by default, which meant browsers served a stale
  copy after every update.
- The accordion handler is delegated and the blog-loop featured-image check runs
  on a `MutationObserver`, because Divi 5 renders modules after page load and a
  one-shot binding misses them.
- Category and tag chips are constrained so long term names neither overflow
  their container nor clip their descenders.
- The palette is the theme's default teal. Every place a color lives — including
  the ones that do not grep cleanly — is documented in COLORS.md.

### Requirements

- WordPress 6.5 or newer, tested to 7.1
- PHP 8.1 or newer, tested to 8.4
- The Divi parent theme, tested against Divi 5
- The SVG Support plugin, required before importing `supplementary/`
