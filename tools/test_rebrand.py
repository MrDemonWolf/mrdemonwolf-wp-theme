#!/usr/bin/env python3
"""Assert that supplementary/ is a correct transform of the vendor exports.

Run after tools/rebrand-exports.py. Every check compares the generated output
against either the pristine vendor input or the repository itself, so it fails
loudly if a transform drops, mangles or half-applies something.

The class-set check is the important one: the whole reason this tooling exists
is that the exports referenced CSS classes the stylesheet no longer defined,
which produced a site with no styling and no error message anywhere.
"""
import glob
import json
import os
import re
import sys
import xml.etree.ElementTree as ET

SRC = "tmp/Nexus-multipurpose-divi-child-theme-supplementary-files"
DST = "supplementary"
MEDIA = os.path.join(DST, "media")

failures = []


def check(ok, label, detail=""):
    print(f"  {'ok  ' if ok else 'FAIL'}  {label}")
    if not ok:
        if detail:
            print(f"          {detail}")
        failures.append(label)


def read(path):
    return open(path, encoding="utf-8", errors="replace").read()


def outputs():
    return sorted(glob.glob(f"{DST}/*.xml") + glob.glob(f"{DST}/*.json"))


def main():
    outs = outputs()
    check(len(outs) == 5, "five export files generated", f"found {len(outs)}")
    blob = "".join(read(p) for p in outs)
    src_blob = "".join(read(p) for p in sorted(glob.glob(f"{SRC}/*.xml") + glob.glob(f"{SRC}/*.json"))
                       if " 2." not in p)

    # --- branding -----------------------------------------------------------
    check(not re.search(r"nexus", blob, re.I), "no vendor brand name survives")
    check("hostingersite" not in blob, "no vendor host survives")

    # --- structural validity ------------------------------------------------
    for p in outs:
        if p.endswith(".json"):
            try:
                json.load(open(p, encoding="utf-8"))
                check(True, f"valid JSON: {os.path.basename(p)}")
            except Exception as e:
                check(False, f"valid JSON: {os.path.basename(p)}", str(e))
        else:
            try:
                ET.parse(p)
                check(True, f"well-formed XML: {os.path.basename(p)}")
            except Exception as e:
                check(False, f"well-formed XML: {os.path.basename(p)}", str(e))

    # --- CSS classes: nothing lost in the prefix rename ----------------------
    src_classes = {m.lower()[len("nexus-"):] for m in re.findall(r"nexus-[a-z0-9-]+", src_blob, re.I)}
    src_classes.discard("multipurpose-divi-child-theme")
    out_classes = {m.lower()[len("mdw-"):] for m in re.findall(r"mdw-[a-z0-9-]+", blob, re.I)}
    missing = sorted(src_classes - out_classes)
    check(not missing, f"all {len(src_classes)} vendor CSS classes carried over",
          f"lost: {missing}")

    # Every class the exports use must resolve to a selector, a JS hook, or
    # module-level custom CSS inside the exports themselves. Anything else
    # renders unstyled with no error.
    css = read("theme/style.css")
    js = read("theme/script.js")
    styled = {m.lower() for m in re.findall(r"\.mdw-([a-z0-9-]+)", css)}
    unresolved = []
    for c in sorted(src_classes):
        if c in styled:
            continue
        if f"mdw-{c}" in js:
            continue
        if re.search(r"selector[^\"]{0,40}\.mdw-" + re.escape(c) + r"(?![a-z0-9-])", blob, re.I):
            continue
        unresolved.append(c)
    known_orphans = {"logo-slider", "sidebar-category", "bs-contain",
                     "header-btn", "slider-partners", "sidebar"}
    surprises = sorted(set(unresolved) - known_orphans)
    check(not surprises, "every CSS class resolves (or is a known vendor orphan)",
          f"unresolved: {surprises}")

    # --- global colors: ids are opaque keys and must not be renamed ----------
    src_gcids = set(re.findall(r"gcid-[a-z0-9-]+", src_blob, re.I))
    out_gcids = set(re.findall(r"gcid-[a-z0-9-]+", blob, re.I))
    check(src_gcids == out_gcids, f"all {len(src_gcids)} gcid ids unchanged",
          f"added={sorted(out_gcids - src_gcids)} lost={sorted(src_gcids - out_gcids)}")

    # --- shortcodes match what functions.php actually registers --------------
    registered = set(re.findall(r"add_shortcode\(\s*\n?\s*'([a-z_]+)'", read("theme/functions.php")))
    used = set(re.findall(r"\[(mrdemonwolf_[a-z_]+)", blob))
    check(used <= registered, "every shortcode used is registered in functions.php",
          f"unregistered: {sorted(used - registered)}")
    check(not re.search(r"\[[Nn]exus_", blob), "no vendor shortcode tags survive")

    # --- media: every referenced file is actually in the repo ---------------
    refs = {r.replace("\\/", "/") for r in re.findall(
        r"supplementary(?:\\?/)media(?:\\?/)((?:[A-Za-z0-9_%-]+(?:\\?/))*[A-Za-z0-9_.%-]+\.[A-Za-z0-9]{2,5})",
        blob)}
    on_disk = set()
    for root, _dirs, names in os.walk(MEDIA):
        for n in names:
            on_disk.add(os.path.relpath(os.path.join(root, n), MEDIA))
    absent = sorted(r for r in refs if r not in on_disk)
    check(refs and not absent, f"all {len(refs)} referenced media files present",
          f"absent: {absent[:10]}")
    unused = sorted(on_disk - refs)
    check(not unused, f"no unreferenced files shipped in media/ ({len(on_disk)} on disk)",
          f"unused: {unused[:10]}")
    check(any(r.startswith("brand/") for r in refs), "brand assets are referenced")

    # --- content: demo posts dropped, everything else kept ------------------
    src_xml = read(os.path.join(SRC, "All Content.xml"))
    out_xml = read(os.path.join(DST, "All Content.xml"))
    def types(x):
        return re.findall(r"<wp:post_type><!\[CDATA\[([^\]]+)\]\]></wp:post_type>", x)
    s_t, o_t = types(src_xml), types(out_xml)
    check(o_t.count("post") == 0, "all vendor demo posts dropped",
          f"{o_t.count('post')} remain")
    for t in ("page", "project", "service", "attachment", "nav_menu_item"):
        check(s_t.count(t) == o_t.count(t), f"{t} count preserved ({s_t.count(t)})",
              f"src={s_t.count(t)} out={o_t.count(t)}")
    check(len(o_t) == len(s_t) - s_t.count("post"),
          f"item count is {len(s_t)} - {s_t.count('post')} posts = {len(s_t) - s_t.count('post')}",
          f"got {len(o_t)}")

    print()
    if failures:
        print(f"{len(failures)} check(s) FAILED: {failures}", file=sys.stderr)
        sys.exit(1)
    print("all checks passed")


if __name__ == "__main__":
    main()
