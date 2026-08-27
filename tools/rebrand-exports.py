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
import base64
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

    # 7. Dead vendor Brevo/Sendinblue list. Rename it -- do NOT blank it.
    #    Divi's signup module gates the whole form on a non-empty account name:
    #    with "" it renders the heading and no <input>, so the footer advertises
    #    a newsletter with nothing to type into. The name is inert until someone
    #    connects a real Brevo account under Divi > Theme Options > API, so a
    #    placeholder is safe and keeps the vendor's layout intact.
    (r'("account"\s*:\s*)"nexus\|2"', r'\1"MrDemonWolf|1"', re.I, "sendinblue optin"),
    (r'(\\"account\\"\s*:\s*)\\"nexus\|2\\"', r'\1\\"MrDemonWolf|1\\"', re.I, "sendinblue optin (escaped)"),

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

        text, n_enc = reencode_brand_previews(text)
        if n_enc:
            counts.append(f"brand previews re-encoded={n_enc}")

        text, n_vlogo = drop_vendor_logo(text)
        if n_vlogo:
            counts.append(f"vendor logo.png refs dropped={n_vlogo}")

        if src_name == "All Content.xml":
            text, n_posts = drop_demo_posts(text)
            counts.append(f"demo posts dropped={n_posts}")

        out = os.path.join(DST, dst_name)
        open(out, "w", encoding="utf-8").write(text)
        print(f"{dst_name}\n    {before:>9,} -> {len(text):>9,} bytes")
        for c in counts:
            print(f"    {c}")


# Only the navbar and footer logos become the MrDemonWolf wordmark. The vendor
# reuses that same logo.png as the small eyebrow badge beside section headings
# ("CHOOSE THE BEST", "GET IN TOUCH") and in several other decorative slots, so
# a blanket swap puts a wolf wordmark in about sixty places it does not belong.
BRAND_HEADER = "brand/logo-text-brand.svg"
BRAND_FOOTER = "brand/logo-text-brand.svg"
VENDOR_LOGO = "logo.png"


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


def swap_brand_assets(text):
    """Swap the logo in the header and footer layouts only."""
    total = 0

    def swap_in(block, target):
        nonlocal total
        for sep in ("/", "\\/"):
            needle = f"media{sep}{VENDOR_LOGO}"
            n = block.count(needle)
            if n:
                total += n
                block = block.replace(needle, f"media{sep}{target.replace('/', sep)}")

        # The module still carries the vendor PNG's attachment id, 13. Neither
        # brand SVG is registered as an attachment, and once real content is
        # imported id 13 belongs to an unrelated blog post -- so Divi resolves
        # the logo against foreign metadata (544x153) or nothing at all. Blank
        # it; `src` alone is what we want it to honour.
        for quoted in ('"id":"13"', '\\"id\\":\\"13\\"'):
            n = block.count(quoted)
            if n:
                total += n
                block = block.replace(quoted, quoted.replace("13", ""))
        return block

    def by_marker(block):
        if "et_header_layout" in block:
            return swap_in(block, BRAND_HEADER)
        if "et_footer_layout" in block:
            # The footer sits on the light palette background, same as the
            # header, so it takes the same dark-ink wordmark. (An earlier
            # revision assumed a #222222 footer and shipped the white variant,
            # which washed out against the actual background.)
            return swap_in(block, BRAND_FOOTER)
        return block

    # WXR: header and footer layouts are each their own <item>.
    text = re.sub(r"\t<item>.*?</item>\n", lambda m: by_marker(m.group(0)), text, flags=re.S)

    # Theme Builder JSON: the layout id keys the object, so the post_type marker
    # sits inside it rather than before it. Brace-match to find its extent.
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
        flat = block.replace(" ", "")
        if '"post_type":"et_header_layout"' not in flat and '"post_type":"et_footer_layout"' not in flat:
            continue
        out.append(text[cursor:a])
        out.append(by_marker(block))
        cursor = b
    out.append(text[cursor:])
    text = "".join(out)

    # The site logo option itself (Theme Options / epanel).
    text, n = re.subn(
        r'("divi_logo"\s*:\s*")([^"]*?)media(\\?/)logo\.png(")',
        lambda m: f"{m.group(1)}{m.group(2)}media{m.group(3)}{BRAND_HEADER.replace('/', m.group(3))}{m.group(4)}",
        text)
    total += n
    return text, total


def reencode_brand_previews(text):
    """Replace the base64 preview Divi ships for each brand logo.

    Divi's portability payload carries an `images` map of url -> {encoded, id}.
    swap_brand_assets rewrites the key and `url` to our wordmark, but `encoded`
    stays the vendor's 544x153 Nexus PNG and `id` stays attachment 13. If the
    importer ever sideloads from `encoded`, it writes PNG bytes to a `.svg`
    filename and the navbar shows the vendor logo with no error anywhere.

    Substituting the real SVG bytes is correct whether or not Divi reads the
    field, which is why this is preferred over deleting the entry: removing a
    member from a 5 MB single-line JSON map risks leaving `{,` behind.
    """
    cache = {}

    def encoded_for(name):
        if name not in cache:
            path = os.path.join(DST, "media", "brand", name)
            with open(path, "rb") as fh:
                cache[name] = base64.b64encode(fh.read()).decode("ascii")
        return cache[name]

    pattern = re.compile(
        r'("[^"]*brand(?:\\?/)(logo-text-[a-z]+\.svg)"\s*:\s*\{)([^{}]*)(\})'
    )

    def fix(m):
        body = re.sub(r'"encoded"\s*:\s*"[^"]*"',
                      lambda _: '"encoded":"' + encoded_for(m.group(2)) + '"',
                      m.group(3))
        # Same stale attachment id as the module carries, in integer form here.
        body = re.sub(r'"id"\s*:\s*13\b', '"id":0', body)
        return m.group(1) + body + m.group(4)

    return pattern.subn(fix, text)


def drop_vendor_logo(text):
    """Remove every trace of the vendor's own logo.png.

    It is unmodified Nexus wordmark artwork. Once the navbar and footer point at
    our SVGs nothing renders it, but two orphans keep it alive: the Divi Theme
    Options portability payload (`images` map, still carrying the base64 PNG)
    and its WXR attachment record. Both are dropped here so the binary can leave
    the repository -- we have no licence to redistribute it.

    branding-guard.sh cannot catch this on its own: the guard greps text, and a
    PNG's bytes contain no vendor string.
    """
    total = 0

    # WXR: the whole <item> for the attachment.
    def drop_item(m):
        nonlocal total
        if re.search(r"media/logo\.png", m.group(0)):
            total += 1
            return ""
        return m.group(0)

    text = re.sub(r"\t<item>.*?</item>\n", drop_item, text, flags=re.S)

    # Theme Options: the entry in the portability `images` map. The value object
    # is flat, so a non-nested brace match is exact.
    text, n = re.subn(
        r',?\s*"[^"]*media(?:\\?/)logo\.png"\s*:\s*\{[^{}]*\}',
        "",
        text,
    )
    total += n
    return text, total


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
