#!/usr/bin/env python3
"""Extract files from an All-in-One WP Migration .wpress archive.

The format is an uncompressed concatenation of records:

    [4377-byte header][content bytes]

with the header laid out as
    255 B  filename
     14 B  content size, ASCII decimal
     12 B  mtime, ASCII decimal
   4096 B  relative path

A header of 4377 null bytes terminates the archive.

Only paths matching --prefix are written, so the 58 MB database dump, the
bundled themes and plugins, and the Wordfence logs never touch disk.

    python3 tools/extract-wpress.py ARCHIVE --prefix uploads/ --out DIR
    python3 tools/extract-wpress.py ARCHIVE --list
"""
import argparse
import os
import sys

HEADER = 4377
NAME, SIZE, TIME, PATH = 255, 14, 12, 4096


def records(fh):
    """Yield (relative_path, size, content_offset), leaving fh past the content."""
    while True:
        head = fh.read(HEADER)
        if len(head) < HEADER or head == b"\x00" * HEADER:
            return
        name = head[:NAME].rstrip(b"\x00").decode("utf-8", "replace")
        raw = head[NAME:NAME + SIZE].rstrip(b"\x00").strip()
        path = head[NAME + SIZE + TIME:].rstrip(b"\x00").decode("utf-8", "replace")
        try:
            size = int(raw)
        except ValueError:
            raise SystemExit(f"corrupt header near offset {fh.tell()}: size={raw!r}")
        rel = f"{path}/{name}" if path not in ("", ".") else name
        yield rel, size, fh.tell()
        fh.seek(size, os.SEEK_CUR)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("archive")
    ap.add_argument("--prefix", default="", help="only handle paths starting with this")
    ap.add_argument("--out", help="destination directory; required unless --list")
    ap.add_argument("--list", action="store_true", help="report only, write nothing")
    a = ap.parse_args()
    if not a.list and not a.out:
        ap.error("--out is required unless --list is given")

    n = total = 0
    with open(a.archive, "rb") as fh:
        for rel, size, offset in records(fh):
            if not rel.startswith(a.prefix):
                continue
            n += 1
            total += size
            if a.list:
                print(f"{size:>10}  {rel}")
                continue
            dest = os.path.join(a.out, rel)
            os.makedirs(os.path.dirname(dest), exist_ok=True)
            here = fh.tell()
            fh.seek(offset)
            with open(dest, "wb") as out:
                remaining = size
                while remaining:
                    chunk = fh.read(min(1 << 20, remaining))
                    if not chunk:
                        raise SystemExit(f"unexpected EOF inside {rel}")
                    out.write(chunk)
                    remaining -= len(chunk)
            fh.seek(here)

    verb = "listed" if a.list else "extracted"
    print(f"{verb} {n} files, {total / 1e6:.2f} MB, prefix={a.prefix!r}", file=sys.stderr)


if __name__ == "__main__":
    main()
