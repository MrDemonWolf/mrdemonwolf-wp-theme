# Colors: where they live and how to change them

The theme ships the **Nexus vendor defaults** (teal). Nothing here is a
MrDemonWolf brand palette — it is the demo baseline, kept intact so a rebrand
from the mockup is a deliberate, one-pass job.

A color lives in **six** places. A rebrand that misses one looks half-done,
which is exactly how the last drift happened.

## The current palette

| Role | Hex | Set in |
| - | - | - |
| Accent | `#1e8a8a` | Customizer `accent_color`, `link_color`; SVG fills |
| Dark / headings | `#0c1e21` | Customizer `secondary_accent_color`, `header_color` |
| Dark 2 | `#18292c` | global color `gcid-hhvnnvrog9` |
| Body text | `#364e52` | Customizer `font_color` |
| Muted text / icons | `#67787a` | `style.css`, SVG fills |
| Light background | `#ecf0f0` | `style.css` `--mdw-bg`, global color `gcid-xsweq3oku6` |
| Background 2 | `#d8e5e5` | global color `gcid-qn8h12q0c7` |
| Borders / muted | `#c9d1d1` | `style.css` `--mdw-border` |
| Text 2 / card bg | `#a9b8b8` | `style.css`, global color `gcid-0ny19batqe` |
| Fixed nav accents | `#2ea3f2` | Customizer `fixed_secondary_nav_bg`, `fixed_menu_link_active` (Divi stock blue, vendor never changed it) |

## 1. Divi global colors

**Divi > Theme Customizer**, or the Variable Manager in the Visual Builder.
Five exist; the ids are generated, so they read as gibberish:

| Id | Label | Value |
| - | - | - |
| `gcid-xsweq3oku6` | Light background | `#ecf0f0` |
| `gcid-qn8h12q0c7` | Background | `#d8e5e5` |
| `gcid-hhvnnvrog9` | Dark color 2 | `#18292c` |
| `gcid-0ny19batqe` | Text 2 | `#a9b8b8` |
| `gcid-ysy0v1g3u1` | Dark color | `#36ff00`, **inactive** |

`theme/style.css` references these as `var(--gcid-…)` with **no fallback**, the
same as the vendor. Delete a global color and re-create it and its id changes,
which silently breaks every rule referencing the old one.

## 2. Customizer keys

Stored in `wp_options` under `et_divi`:

`accent_color` `#1e8a8a` · `secondary_accent_color` `#0c1e21` ·
`header_color` `#0c1e21` · `font_color` `#364e52` · `link_color` `#1e8a8a`

Edit through **Appearance > Customize**, not the database, so Divi flushes its
CSS cache.

## 3. `theme/style.css`

Literal values that no Divi setting controls:

- `:root` — `--mdw-bg: #ecf0f0`, `--mdw-border: #c9d1d1`. Most background and
  border rules go through these two, so they are the cheapest lever.
- `#67787a` (×2), `#a9b8b8` (×1), `#ffffff` (×5)
- `rgba(30, 138, 138, …)` — the accent at `.3`, `.15`, and `0` alpha, ×8.
  A hex search will not find these.
- `%23ecf0f0` — the light background, URL-encoded inside an inline SVG
  `data:` URI. A hex search will not find this either.

## 4. `theme/assets/*.svg`

The bundled icons carry their fills in the file: `#1e8a8a` (×3), `#67787a`,
`#0c1e21`. CSS pseudo-elements draw them, so they are invisible to any search
of the stylesheet. The vendor hotlinks these from its own server; we bundle
them, which is why they need recoloring by hand.

## 5. `supplementary/` exports

`All Content.xml` and the four JSON files each carry their own copy of every
color, baked into the Divi layouts. Importing them **overwrites** whatever is
in the Customizer, so re-import first and recolor second, never the reverse.

## 6. Content already in the database

Colors sit inside `wp_posts` builder data. WP-CLI, one line per color:

```bash
wp search-replace "#1e8a8a" "#yournew" --precise --all-tables
```

Run `--dry-run` first, and remember `%23`-encoded copies inside SVG data URIs
need their own pass.

## Rebranding later, from the mockup

Work the six locations in this order: exports (or skip if already imported) →
global colors → Customizer keys → `style.css` → SVG assets → database
search-replace. Then hard-refresh with Divi's **Clear CSS Cache**.

For reference, the recolor that was reverted out of this repo mapped:
`#364e52`→`#3b4f66`, `#0c1e21`→`#091533`, `#e9eded`/`#d8e5e5`→`#ecf0f0`,
`#313d3d`→`#3b4f66`, plus a blue accent `#0FACED`/`#3aaee3`. It missed the SVG
fills and the `rgba()` set, which is what made the site look inconsistent.
