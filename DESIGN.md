# MrDemonWolf Theme — Design Reference

## Color Palette

| Role | Hex | RGB | Usage |
|------|-----|-----|-------|
| Primary (Electric Blue) | `#0FACED` | rgb(15, 172, 237) | Buttons, highlights, icon fills, hover states |
| Secondary (Deep Navy) | `#091533` | rgb(9, 21, 51) | Dark backgrounds, button circles, overlays |
| Accent (Periwinkle) | `#6B8BF5` | rgb(107, 139, 245) | Optional third accent for hover/active states |
| Page background | `#EEF2F7` | rgb(238, 242, 247) | Body background, mobile menu icon bg |
| Border / muted | `#C8D3E0` | rgb(200, 211, 224) | Borders, dividers, pagenavi |
| Timeline / icon gray | `#5B6E8A` | rgb(91, 110, 138) | Timeline dots, breadcrumb arrow fill |
| Person card bg | `#8FA0B8` | rgb(143, 160, 184) | Team member image background |

---

## A. WP Admin: Divi Global Colors

**Path:** WP Admin → Divi → Theme Customizer → General Settings → Global Colors

Set the following Divi global color variables:

| Variable ID | UI Label | Value |
|-------------|----------|-------|
| `--gcid-primary-color` | Primary Color | `#0FACED` |
| `--gcid-secondary-color` | Secondary Color | `#091533` |
| `--gcid-heading-color` | Heading Color | `#091533` |
| `--gcid-body-color` | Body Text Color | `#3B4F66` |
| `--gcid-hhvnnvrog9` | Overlay Tint | `#091533` (matches secondary) |
| `--gcid-qn8h12q0c7` | Subtle Fill | `#EEF2F7` (matches page bg) |

> **Note:** These are Divi CSS custom properties — they are set in the Divi Customizer UI, not in `style.css`. Do not hardcode them.

### Header & Sticky Header Colors

- WP Admin → Divi → Theme Builder → Edit Header Template
- Select the header Section → Design → Background → set to `#091533` or transparent as needed
- For sticky header background: same Section → Scroll Effects → Sticky Background Color → `#091533`

### Body Background Color

- WP Admin → Divi → Theme Customizer → General Settings → Layout → Site Background Color
- Set to `#EEF2F7`
- (Also hardcoded in `theme/style.css` as a fallback)

---

## B. Typography

**Path:** WP Admin → Divi → Theme Customizer → General Settings → Typography

Recommended font pairing:

| Use | Font | Weight |
|-----|------|--------|
| Headings | `Syne` or `DM Sans` | 700 / 800 |
| Body text | `Plus Jakarta Sans` | 400 / 500 |

The live preview uses **Mona Sans** — if keeping that, set it for both headings and body.

To load Google Fonts, add them via Divi → Theme Customizer → Additional CSS or enqueue in `functions.php`.

---

## C. SVG Icon Assets

All icons are stored in `theme/assets/` and referenced via relative `url(assets/...)` paths in `style.css`. No WP Media upload required.

| File | Used In | Fill Color |
|------|---------|-----------|
| `icon_circles.svg` | List bullets (`.et_pb_text_inner li:before`) | `#0FACED` |
| `icon_arrow_btn.svg` | Button arrow on dark bg (`.mdw-btn-1`, `.mdw-btn-2`) | `#ffffff` |
| `icon_arrow_btn_2.svg` | Button arrow on light bg (`.mdw-btn-3`) | `#0FACED` |
| `icon_home.svg` | Breadcrumb home icon | `#0FACED` |
| `icon_arrow_header.svg` | Breadcrumb separator arrow | `#5B6E8A` |
| `separator.svg` | Numbers section column divider | `white` |
| `icon_square.svg` | Post pagination center mark | `#091533` |
| `icon_portfolio.svg` | Portfolio hover arrow | black (filtered via `brightness(0)`) |

---

## D. Content Sections: Page-by-Page Guidance

### Hero Sections
- Background overlay: set Section Background Overlay Color to `#091533` at 70–80% opacity in Divi
- CTA buttons: primary button uses `.mdw-btn-1` class (navy circle arrow) — no CSS change needed
- Secondary CTA on light background: use `.mdw-btn-3` class (blue circle arrow)

### Services Section (`.mdw-service-icon`)
- Icon container uses `rgba(15, 172, 237, 0.3)` gradient and `rgba(15, 172, 237, 0.15)` border — these are now correct
- On hover, the icon bg turns white; text turns white via `--gcid-primary-color` on the parent section bg
- If the service section background is set globally via Divi, ensure `--gcid-primary-color` is `#0FACED`

### Cards
| Class | Hover Behavior | Color Driver |
|-------|---------------|-------------|
| `.mdw-card-1` | Overlay darkens, glow appears | `--gcid-secondary-color` overlay, `--gcid-primary-color` glow |
| `.mdw-card-2` | Arrow circle fades in, image scales | `--gcid-primary-color` on arrow hover |
| `.mdw-card-3` | Icon bg fills with primary color | `--gcid-primary-color` |

### Blog Loop (`.mdw-blog-loop`)
- Category badge (`.mdw-blog-cat`) border: `#C8D3E0` — already updated
- Badge hover: fills with `--gcid-primary-color` (`#0FACED`)
- Post title hover underline: uses `--gcid-heading-color` (`#091533`)

### Footer CTA Section
- Background set in Divi builder on the footer CTA row
- Recommend: `#091533` background with white text
- The CTA image uses `.mdw-footer-cta-img` (full-height cover)

### Header Navigation
- Active/hover link color: `--gcid-primary-color` → `#0FACED` (set via global color)
- Mobile menu pill background: `#EEF2F7` (hardcoded in `style.css`)
- Submenu border radius: 12px, no border — set in `style.css`, no Divi change needed

### Timeline / Company History
- Dashed line color: `#C8D3E0`
- Timeline dot border: `#5B6E8A`
- Both updated in `style.css`

### Pagination & Post Navigation
- Current page: `--gcid-secondary-color` fill (`#091533`)
- Hover: `--gcid-primary-color` fill (`#0FACED`)
- Border: `#C8D3E0`

---

## E. Divi Module-Specific Notes

### Accordion (`.mdw-accordion`)
- Toggle icon ring color: `--gcid-primary-color`
- Divider between title and content: `1px dashed #C8D3E0`

### Testimonial Slider (`.mdw-testimonial-slider`)
- Active slide border: `--gcid-primary-color`

### Team Member (`.mdw-person`)
- Photo background: `#8FA0B8`
- On hover: rotated `--gcid-primary-color` glow behind photo
- Social link hover: `--gcid-primary-color` fill

### Blockquote
- Border and quote mark: `--gcid-primary-color`
- Background: `--gcid-qn8h12q0c7` (subtle fill → `#EEF2F7`)

---

## F. Adding the Periwinkle Accent (`#6B8BF5`)

To use the periwinkle accent as a third Divi global color:

1. WP Admin → Divi → Theme Customizer → General Settings → Global Colors
2. Click "+" to add a new global color
3. Set value to `#6B8BF5`, label it "Accent"
4. Note the generated `--gcid-*` variable ID
5. Apply to any Divi module property you want to use it for (e.g. button borders, tag backgrounds)
