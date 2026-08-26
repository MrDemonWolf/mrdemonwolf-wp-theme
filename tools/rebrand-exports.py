#!/usr/bin/env python3
"""Rebuild supplementary/ from the pristine vendor exports in tmp/.

The repository stylesheet renamed every vendor CSS class from the `nexus-`
prefix to `mdw-`, but the vendor exports were never updated to match. Importing
them untransformed yields a site with no layout styling at all, because the
Divi module classes no longer resolve to any selector.

This script is the transform. It is deterministic and re-runnable: delete
supplementary/*.json and supplementary/*.xml, run it again, get the same bytes.

    python3 tools/rebrand-exports.py
    python3 tools/test_rebrand.py      # asserts the output is correct

Order is load-bearing. The theme directory slug also begins with the vendor
prefix, so it must be rewritten before the blanket prefix replace, or it turns
into a nonsense slug and orphans the custom_css and wp_global_styles items.

Never rename a `gcid-*` id. They are opaque keys referenced ~950 times across
four files; only their `label` and `color` fields are safe to touch.
"""
import os
import re
import sys

SRC = "tmp/Nexus-multipurpose-divi-child-theme-supplementary-files"
DST = "supplementary"

RAW = ("https://raw.githubusercontent.com/MrDemonWolf/"
       "mrdemonwolf-wp-theme/main/supplementary/media")
VENDOR_HOST = "lightyellow-mole-660888.hostingersite.com"

FILES = {
    "All Content.xml": "All Content.xml",
    "Nexus Divi Theme Options.json": "MrDemonWolf Divi Theme Options.json",
    "Nexus Divi Theme Builder Layouts.json": "MrDemonWolf Divi Theme Builder Layouts.json",
    "Nexus Divi Theme Builder Templates.json": "MrDemonWolf Divi Theme Builder Templates.json",
    "Nexus Divi Theme Customizer Settings.json": "MrDemonWolf Divi Theme Customizer Settings.json",
}

# (pattern, replacement, flags, label) applied with re.sub, in this order.
STEPS = [
    # 1. Theme directory slug. MUST precede the prefix replace below.
    (r"nexus-multipurpose-divi-child-theme", "mrdemonwolf", re.I, "theme slug"),

    # 2. CSS classes. A blind prefix swap is correct: all 65 vendor tokens map
    #    onto an existing `mdw-` selector, a JS hook, or module-level custom CSS.
    (r"nexus-", "mdw-", re.I, "css class prefix"),

    # 3-5. Shortcode tags, which are case sensitive -- note the capital N.
    (r"\[Nexus_breadcrumbs", "[mrdemonwolf_breadcrumbs", 0, "breadcrumbs shortcode"),
    (r"\[nexus_tags", "[mrdemonwolf_tags", re.I, "tags shortcode"),
    (r"\[nexus_social_share", "[mrdemonwolf_social_share", re.I, "social share shortcode"),

    # 6. Service icon post meta key, read by functions.php and by Divi dynamic
    #    content. Also appears as a <wp:meta_key> row on the 6 service items.
    (r"_nexus_service_image", "_mrdemonwolf_service_image", re.I, "service image meta"),

    # 7. Dead vendor Brevo/Sendinblue list. Blank the account, do not rename it.
    (r'("account"\s*:\s*)"nexus\|2"', r'\1""', re.I, "sendinblue optin"),
    (r'(\\"account\\"\s*:\s*)\\"nexus\|2\\"', r'\1\\"\\"', re.I, "sendinblue optin (escaped)"),

    # 8. Contact address in the demo contact module.
    (r"info@nexus\.com", "hello@mrdemonwolf.com", re.I, "contact email"),
    (r"nexus\.com", "mrdemonwolf.com", re.I, "vendor domain"),

    # 9. Everything left is prose, global-color labels, saved-layout titles and
    #    module preset names. The demo sentences stay generic placeholder copy;
    #    only the brand word changes. Real copy gets written in Divi later.
    #    A word boundary is not usable here: the footer credit link is stored as
    #    `\u003eNexus\u003c`, so the character before the brand word is a letter.
    #    By this point every structural identifier is already converted, so an
    #    unconditional sweep can only touch prose, labels and titles.
    (r"nexus", "MrDemonWolf", re.I, "brand word"),
]


def rewrite_media_urls(text):
    """Point every vendor-host upload at this repository's own media folder."""
    pattern = re.compile(
        r"https?:(?:\\?/){2}" + re.escape(VENDOR_HOST).replace(r"\.", r"\.") +
        r"(?:\\?/)wp-content(?:\\?/)uploads(?:(?:\\?/)[0-9]{4}(?:\\?/)[0-9]{2})?"
        r"(?:\\?/)([A-Za-z0-9_.%-]+\.[A-Za-z0-9]{2,5})"
    )

    def sub(m):
        escaped = "\\/" in m.group(0)
        base = RAW.replace("/", "\\/") if escaped else RAW
        return f"{base}{'\\/' if escaped else '/'}{m.group(1)}"

    return pattern.subn(sub, text)


def main():
    if not os.path.isdir(SRC):
        sys.exit(f"Error: vendor exports not found at {SRC}")
    os.makedirs(DST, exist_ok=True)

    for src_name, dst_name in FILES.items():
        path = os.path.join(SRC, src_name)
        if not os.path.isfile(path):
            sys.exit(f"Error: missing vendor file {path}")
        text = open(path, encoding="utf-8", errors="strict").read()
        before = len(text)

        counts = []
        for pattern, repl, flags, label in STEPS:
            text, n = re.subn(pattern, repl, text, flags=flags)
            if n:
                counts.append(f"{label}={n}")

        text, n_url = rewrite_media_urls(text)
        if n_url:
            counts.append(f"media urls={n_url}")

        # Whatever still names the vendor host is structural WXR plumbing --
        # wp:base_site_url, <link>, <guid>, and the footer credit link. The
        # importer rewrites those relative to base_site_url, so any consistent
        # placeholder works. example.com is reserved for exactly this (RFC 2606)
        # and makes a failed remap obvious instead of silently pointing at a
        # real production site.
        text, n_host = re.subn(re.escape(VENDOR_HOST), "example.com", text)
        if n_host:
            counts.append(f"vendor host -> example.com={n_host}")

        text, n_logo = swap_brand_assets(text)
        if n_logo:
            counts.append(f"brand assets={n_logo}")

        text, n_footer = use_white_logo_in_footer(text)
        if n_footer:
            counts.append(f"footer logo -> white={n_footer}")

        if src_name == "All Content.xml":
            text, n_posts = drop_demo_posts(text)
            counts.append(f"demo posts dropped={n_posts}")

        out = os.path.join(DST, dst_name)
        open(out, "w", encoding="utf-8").write(text)
        print(f"{dst_name}\n    {before:>9,} -> {len(text):>9,} bytes")
        for c in counts:
            print(f"    {c}")


# The vendor logo and favicon are replaced outright rather than restyled -- a
# logo is a trademark regardless of the code license, so the vendor mark cannot
# ship here even though the theme itself can.
BRAND_SWAPS = {
    "logo.png": "brand/logo-text-brand.svg",
    "favicon.png": "brand/favico-border-white.png",
}


def swap_brand_assets(text):
    """Point every vendor logo/favicon reference at the MrDemonWolf assets."""
    total = 0
    for old, new_rel in BRAND_SWAPS.items():
        for sep in ("/", "\\/"):
            needle = f"media{sep}{old}"
            total += text.count(needle)
            text = text.replace(needle, f"media{sep}{new_rel.replace('/', sep)}")

    return text, total


def _json_object_span(text, start):
    """Return (start, end) of the JSON object beginning at `start`.

    Brace counting has to ignore braces inside strings, and these exports nest
    JSON inside JSON strings several levels deep, so escapes matter.
    """
    depth, i, in_str, esc = 0, start, False, False
    while i < len(text):
        c = text[i]
        if in_str:
            if esc:
                esc = False
            elif c == "\\":
                esc = True
            elif c == '"':
                in_str = False
        elif c == '"':
            in_str = True
        elif c == "{":
            depth += 1
        elif c == "}":
            depth -= 1
            if depth == 0:
                return start, i + 1
        i += 1
    raise ValueError("unbalanced JSON object")


def use_white_logo_in_footer(text):
    """The footer sits on #222222, so the dark-ink wordmark is invisible there.

    Scoped to footer layouts only -- the header keeps the brand-colour variant.
    """
    dark, white = "logo-text-brand.svg", "logo-text-white.svg"
    total = 0

    def recolour(block):
        nonlocal total
        for sep in ("/", "\\/"):
            n = block.count(f"brand{sep}{dark}")
            if n:
                total += n
                block = block.replace(f"brand{sep}{dark}", f"brand{sep}{white}")
        return block

    # WXR: the footer layout is its own <item>.
    text = re.sub(r"\t<item>.*?</item>\n",
                  lambda m: recolour(m.group(0)) if "et_footer_layout" in m.group(0) else m.group(0),
                  text, flags=re.S)

    # Theme Builder JSON: walk each layout object and recolour the footer one.
    # The layout id keys the object, so the marker sits inside it, not before it.
    out, cursor = [], 0
    for m in re.finditer(r'"\d+"\s*:\s*\{', text):
        brace = m.end() - 1
        if brace < cursor:
            continue
        try:
            a, b = _json_object_span(text, brace)
        except ValueError:
            continue
        block = text[a:b]
        if '"post_type":"et_footer_layout"' not in block.replace(" ", ""):
            continue
        out.append(text[cursor:a])
        out.append(recolour(block))
        cursor = b
    out.append(text[cursor:])
    return "".join(out), total


def drop_demo_posts(xml):
    """Remove the 9 vendor demo blog posts. Real posts import separately.

    Their category and tag terms are deliberately kept: the migration runbook
    renames those terms in place and then matches the real posts to them by
    slug, so removing them here would produce duplicates with -2 slugs.
    """
    items = re.findall(r"\t<item>.*?</item>\n", xml, re.S)
    dropped = 0
    for item in items:
        if re.search(r"<wp:post_type><!\[CDATA\[post\]\]></wp:post_type>", item):
            xml = xml.replace(item, "", 1)
            dropped += 1
    return xml, dropped


if __name__ == "__main__":
    main()
