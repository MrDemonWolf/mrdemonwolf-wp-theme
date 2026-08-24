# Brand reference: Brand Blues v6

Source of truth for the palette is the Astro reference build in the private
`MrDemonWolf/website` repo: `apps/website/src/styles/global.css` (Tailwind
theme) and `site.css` (tokens). This file mirrors it so the values are at hand
while building in Divi, and so this repo stands on its own.

**Nothing here is implemented as CSS in this theme.** Divi owns the colors:
set them once in the Divi UI and every module inherits them. The child theme
only references `var(--gcid-*)` and now carries matching hex fallbacks.

## Core palette

| Role | Token (Astro) | Hex |
| - | - | - |
| Accent / CTA | `--blue-400` | `#3AAEE3` |
| Accent hover | `--blue-300` | `#6BC8F6` |
| Accent text on white (AAA) | `--navy-700` | `#0D4D8C` |
| Navy, headings + dark surfaces | `--navy-900` | `#0A1633` |
| Navy soft, alt hero bg | `--navy-800` | `#0D2A56` |
| Near black, hero bg | `--navy-950` | `#02050C` |
| Body text | `--ink-700` | `#1F2A40` |
| Emphasis text | `--ink-900` | `#0C1220` |
| Muted text | `--ink-500` | `#4A5568` |
| Hairlines / borders | `--ink-200` | `#CFD5E1` |
| Alt section background | `--ink-100` | `#E6EAF1` |
| Page wash | `--ink-050` | `#F3F5F9` |

Blue ramp: `#EFF7FF` `#D7EFFF` `#90DDFC` `#6BC8F6` `#3AAEE3` `#1795D2` `#1178B8`
`#0D4D8C`. Semantic: success `#2FAB66`, warning `#E3A008`, danger `#D33A3A`,
info `#1795D2`.

Type: **DM Sans** everywhere (body and display). Headings are extra bold.
Rounding is macOS-soft: 12 / 20 / 28 / 36 px.

## Mapping onto Divi

Divi 5 keeps the Divi 4 `--gcid-*` CSS variables and adds Design Variables
(Divi > Variable Manager). Set these in the Divi UI, not in code:

| Divi variable | Set to | Was (demo import) |
| - | - | - |
| `--gcid-primary-color` | `#3AAEE3` | `#1e8a8a` |
| `--gcid-secondary-color` | `#0A1633` | `#091533` |
| `--gcid-heading-color` | `#0A1633` | `#091533` |
| `--gcid-body-color` | `#1F2A40` | `#3B4F66` |
| `--gcid-hhvnnvrog9` ("MrDemonWolf dark color 2") | `#0D2A56` | `#18292c` |
| `--gcid-qn8h12q0c7` ("MrDemonWolf background") | `#E6EAF1` | `#d8e5e5` |

**The `supplementary/` exports still carry the old teal demo palette**
(`#1e8a8a`, `#ecf0f0`, `#c9d1d1`, `#67787a`). Importing Theme Options restores
those values, so reset the global colors above in the Divi UI afterwards. Every
`var(--gcid-*)` in `theme/style.css` now has a Brand Blues hex fallback, so a
missing variable renders on-brand instead of transparent.

The two hashed IDs are Divi-generated. If a global color is ever deleted and
re-created, its ID changes and the matching rules in `theme/style.css` fall back
to the hex above. Re-point them if that happens.

## Dark mode: reference only

The Astro build ships a full dark theme ("Brand Blues after dark", activated by
`data-theme="dark"`, Shift+Alt+D on the mockup). **The child theme implements
none of it** and Divi has no native dark switch. The table is here so the target
is known if it ever gets built.

| Token | Dark value |
| - | - |
| Page bg | `#0A1424` |
| Alt section | `#0E1B33` |
| Card | `#12213D` |
| Hero | `#071022` |
| Heading | `#E9F1FB` |
| Body text | `#C6D2E4` |
| Muted text | `#93A8C8` |
| Border | `#24395C` |
| Accent text | `#6BC8F6` (`--blue-300`) |
| Text on accent | `#0A1633` |
| Danger | `#E05B5B` |

Rule of the dark palette: surfaces are navy-tinted, never neutral gray. Buttons
flip from navy-on-white to `#3AAEE3` with navy text (7.1:1); white on
`#3AAEE3` is only 2.5:1 and must not be used.

## Notes on the exports

- `All Content.xml` contains a `wp-global-styles-mdw-multipurpose-divi-child-theme`
  entry, an artifact of the blind Nexus to MrDemonWolf rename. Harmless.
- The `project` post type and its `project_category` / `project_tag` taxonomies
  come from **Divi**, not this theme. Nothing here registers them, and nothing
  should.
- Class coverage was checked against the live demo (6 pages): every `nexus-*`
  class in use has an `mdw-*` rule except `bs-contain` / `logo-slider` /
  `clients-number` (the logo wall and client counter, which
  `docs/divi-cut-list.md` deletes), `video-popup` (behaviour
  only, styled by Magnific Popup), and `sidebar-category` (unstyled in the
  original too; the styled wrapper is `sidebar-cat`).
