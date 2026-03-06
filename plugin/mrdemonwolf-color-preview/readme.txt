=== MrDemonWolf Color Preview ===
Contributors: mrdemonwolf
Tags: divi, color, preview, branding, theme
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Live-preview and apply brand color changes to the MrDemonWolf Divi child theme.

== Description ==

MrDemonWolf Color Preview adds an admin-only color panel to the frontend of your site. It lets you:

* Live-preview all 11 brand colors in real time
* Auto-derive related colors when you change a base color
* Override individual derived colors manually
* Apply color changes permanently to the theme's style.css
* Export color values as JSON with shell commands for rebrand scripts

The panel only appears for administrators and does not affect the site for regular visitors.

**Requires the MrDemonWolf Divi child theme.**

== Installation ==

1. Download `mrdemonwolf-color-preview.zip` from the latest release.
2. Go to Plugins > Add New > Upload Plugin in your WordPress admin.
3. Upload the zip file and click "Install Now."
4. Activate the plugin.
5. Visit your site's frontend while logged in as an admin to see the color panel toggle on the right edge.

== Frequently Asked Questions ==

= Does this affect my site visitors? =

No. The color panel and preview styles only load for logged-in administrators.

= What happens when I click "Apply to Theme"? =

The plugin performs a find-and-replace on your child theme's `style.css`, updating hardcoded hex values, RGBA values, and SVG data URIs with your new colors.

= Can I undo applied changes? =

Applied changes modify `style.css` directly. To revert, restore the file from version control or a backup. The "Reset" button only clears the live preview.

= Does this update Divi Global Colors in the database? =

No. This plugin only modifies `style.css`. Use the rebrand shell scripts or the Divi admin UI to update database-stored colors.

== Screenshots ==

1. Color preview panel open on the frontend
2. Auto-derived colors updating in real time
3. Export modal with JSON and shell commands

== Changelog ==

= 1.0.0 =
* Initial release
* Live color preview panel with 11 brand colors
* HSL-based auto-derivation of related colors
* Apply colors permanently to theme style.css
* Export colors as JSON with shell commands
