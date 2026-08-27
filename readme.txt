=== MrDemonWolf ===
Contributors: mrdemonwolf
Tags: divi, child-theme, custom-post-type, breadcrumbs, portfolio
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A custom Divi child theme by MrDemonWolf, Inc. featuring a Service CPT,
breadcrumbs, social sharing, video lightbox, and security hardening.

== Description ==

MrDemonWolf is a WordPress child theme built on the Divi Theme Builder.
It ships pre-built page layouts, a "Service" custom post type with icon
metabox, shortcodes for breadcrumbs, tags, and social sharing, a bundled
Magnific Popup video lightbox, and a suite of security hardening measures.

**Requires the Divi parent theme.**

= Features =

* **Service Custom Post Type** — manage services with a dedicated icon
  metabox in the WordPress admin.
* **Breadcrumbs Shortcode** — `[mrdemonwolf_breadcrumbs]` with
  WooCommerce, project, and archive support, and BreadcrumbList schema.
* **Tags Shortcode** — `[mrdemonwolf_tags]` displays post/project tags inline.
* **Social Share Shortcode** — `[mrdemonwolf_social_share]` for Facebook,
  X (Twitter), and LinkedIn.
* **Video Popup** — Magnific Popup lightbox bundled locally; no CDN dependency.
* **Security Hardening** — login error messages obscured, WordPress
  version hidden from the front end.
* **Self-updating** — new versions arrive through Appearance > Themes,
  served from GitHub Releases. No update plugin required.
* **Cleanup Notice** — after theme deactivation a mu-plugin surfaces an
  admin notice offering one-click removal of all theme data or a safe
  dismiss that leaves data intact.

== Installation ==

1. Install and activate the **Divi** parent theme.
2. Install and activate the **SVG Support** plugin (https://wordpress.org/plugins/svg-support/).
   Do this *before* importing content — several bundled assets are SVG and will
   fail to import without it.
3. Upload `mrdemonwolf.zip` via **Appearance → Themes → Add New → Upload Theme**.
4. Activate the **MrDemonWolf** child theme. Activate it *before* importing
   content; it registers the Service post type.
5. Set **Settings → Permalinks** to your final structure before importing.
6. Import the five files from the repository's `supplementary/` folder, in this
   exact order. Wrong order leaves broken references:
   1. **Divi → Theme Options** — Theme Options JSON
   2. **Divi → Divi Library** — Theme Builder Layouts JSON ("Import Presets" checked)
   3. **Divi → Theme Builder** — Theme Builder Templates JSON ("Import Presets" checked)
   4. **Appearance → Customize** — Customizer Settings JSON
   5. **Tools → Import → WordPress** — `All Content.xml`, with
      "Download and import file attachments" ticked
7. Go to **Settings → Reading** and set your Homepage and Posts page.
8. Go to **Appearance → Menus** and assign the Primary Menu.
9. (Optional) Install **WP-PageNavi** for cleaner pagination.

== Frequently Asked Questions ==

= Does this work without Divi? =

No. MrDemonWolf is a Divi child theme; the Divi parent theme must be
installed and active.

= How do I use the shortcodes? =

Add `[mrdemonwolf_breadcrumbs]`, `[mrdemonwolf_tags]`, or
`[mrdemonwolf_social_share]` to any page, post, or Divi module that
accepts shortcodes.

= How do I change the colors? =

The palette lives in six places, only some of which are in this repository.
See COLORS.md for the full list and the order to change them in.

= What happens to my data if I switch themes? =

When the theme is deactivated WordPress writes a lightweight mu-plugin
that shows an admin notice on the Dashboard. You can remove all Service
posts and post meta with one click, or dismiss the notice to keep your
data.

== Screenshots ==

1. Theme preview — MrDemonWolf branding on a Divi-powered site.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Header and footer logo rules carry explicit pixel widths, so Divi's inline
  SVG width rule cannot collapse them on load order.
* Service custom post type with icon metabox, served from `/services/`.
* Breadcrumbs, tags, and social share shortcodes.
* BreadcrumbList schema from the breadcrumbs shortcode, unless an SEO plugin emits its own.
* Magnific Popup video lightbox, bundled locally — no CDN dependency.
* Security hardening: login error obscuring, generator version hiding.
* Cleanup notice mu-plugin with Remove and Dismiss actions, using WP_Filesystem
  and skipped when file modifications are disallowed.
* One-click updates from GitHub Releases via the Update URI header.
* Child stylesheet cache-busts on every theme update.
* Accordion handler delegated; blog-loop check runs on a MutationObserver for Divi 5.
* 7 SVG icons bundled in theme/assets/ — no Media Library upload required.
* Divi Theme Builder exports shipped in `supplementary/`, with all demo media
  self-hosted rather than hotlinked.
* CI: PHP lint on 8.1/8.3/8.4, WordPress-Core phpcs, branding guard,
  version-sync check, zip smoke test.
* Release pipeline: GitHub Release on `v*` tag push.
* Tested against WordPress 7.1, PHP 8.4, and Divi 5.

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required. Requires PHP 8.1 and
WordPress 6.5 or newer, plus the Divi parent theme.
