=== MrDemonWolf ===
Contributors: mrdemonwolf
Tags: divi, child-theme, custom-post-type, breadcrumbs, portfolio
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
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
  WooCommerce, project, and archive support.
* **Tags Shortcode** — `[mrdemonwolf_tags]` displays post/project tags inline.
* **Social Share Shortcode** — `[mrdemonwolf_social_share]` for Facebook,
  X (Twitter), and LinkedIn.
* **Video Popup** — Magnific Popup lightbox bundled locally; no CDN dependency.
* **Security Hardening** — SVG uploads restricted to admins, login error
  messages obscured, WordPress version hidden from the front end.
* **Cleanup Notice** — after theme deactivation a mu-plugin surfaces an
  admin notice offering one-click removal of all theme data or a safe
  dismiss that leaves data intact.

== Installation ==

1. Install and activate the **Divi** parent theme.
2. Upload `mrdemonwolf.zip` via **Appearance → Themes → Add New → Upload Theme**.
3. Activate **MrDemonWolf**.
4. Import the bundled Divi Theme Builder templates and global presets via
   **Divi → Theme Builder** and **Divi → Theme Customizer → Presets**.

== Frequently Asked Questions ==

= Does this work without Divi? =

No. MrDemonWolf is a Divi child theme; the Divi parent theme must be
installed and active.

= How do I use the shortcodes? =

Add `[mrdemonwolf_breadcrumbs]`, `[mrdemonwolf_tags]`, or
`[mrdemonwolf_social_share]` to any page, post, or Divi module that
accepts shortcodes.

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
* Service custom post type with icon metabox.
* Breadcrumbs, tags, and social share shortcodes.
* Magnific Popup video lightbox (bundled locally).
* Security hardening: SVG restriction, login error obscuring, version hiding.
* Cleanup notice mu-plugin with Remove and Dismiss actions.
* CI: PHP lint, Nexus-string guard, zip smoke test.
* Release pipeline: GitHub Release on `v*` tag push.

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.
