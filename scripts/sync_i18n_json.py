#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Extract __('...') / __("...") string keys from PHP and Blade sources,
write resources/lang/en.json (keys = English values),
and resources/lang/rw.json (same keys, Kinyarwanda via Google translate endpoint).

Requires Python 3.6+ (tested for hosts still on 3.6).

Run from repo root:
  python3 scripts/sync_i18n_json.py

Requires outbound HTTPS (translate.googleapis.com).
"""

import json
import re
import ssl
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path
from typing import Dict, List, Set, Tuple

ROOT = Path(__file__).resolve().parents[1]
LANG_DIR = ROOT / "resources" / "lang"
EN_PATH = LANG_DIR / "en.json"
RW_PATH = LANG_DIR / "rw.json"

SCAN_DIRS = [
    ROOT / "app",
    ROOT / "resources" / "views",
    ROOT / "routes",
    ROOT / "bootstrap",
]

EXTENSIONS = {".php", ".blade.php"}

# __('key' or __("key" — first argument only; skip obvious non-literals
RE = re.compile(
    r"""__\(\s*(['"])((?:\\.|(?!\1).)*)\1""",
    re.DOTALL,
)


def unescape_php_single(raw):
    # type: (str) -> str
    out = []  # type: List[str]
    i = 0
    while i < len(raw):
        if raw[i] == "\\" and i + 1 < len(raw) and raw[i + 1] in "'\\":
            out.append(raw[i + 1])
            i += 2
        elif raw[i] == "\\" and i + 1 < len(raw):
            out.append(raw[i])
            out.append(raw[i + 1])
            i += 2
        else:
            out.append(raw[i])
            i += 1
    return "".join(out)


def unescape_php_double(raw):
    # type: (str) -> str
    try:
        return bytes(raw, "utf-8").decode("unicode_escape")
    except UnicodeDecodeError:
        return (
            raw.replace("\\\\", "\\")
            .replace('\\"', '"')
            .replace("\\n", "\n")
            .replace("\\r", "\r")
            .replace("\\t", "\t")
        )


def iter_source_files():
    # type: () -> List[Path]
    out = []  # type: List[Path]
    for base in SCAN_DIRS:
        if not base.is_dir():
            continue
        for p in base.rglob("*"):
            if p.suffix in EXTENSIONS or p.name.endswith(".blade.php"):
                out.append(p)
    return sorted(out)


def extract_keys_from_text(text):
    # type: (str) -> Set[str]
    found = set()  # type: Set[str]
    for m in RE.finditer(text):
        raw = m.group(2)
        quote = m.group(1)
        if quote == "'":
            key = unescape_php_single(raw)
        else:
            key = unescape_php_double(raw)
        key = key.strip("\n\r")
        if not key or len(key) > 5000:
            continue
        if "$" in key or "{" in key or "{{" in key:
            continue
        found.add(key)
    return found


def collect_all_keys():
    # type: () -> List[str]
    all_keys = set()  # type: Set[str]
    for path in iter_source_files():
        try:
            content = path.read_text(encoding="utf-8", errors="replace")
        except OSError:
            continue
        all_keys |= extract_keys_from_text(content)
    return sorted(all_keys)


def translate_chunk(text, ctx):
    # type: (str, ssl.SSLContext) -> str
    if not text.strip():
        return text
    params = urllib.parse.urlencode(
        {
            "client": "gtx",
            "sl": "en",
            "tl": "rw",
            "dt": "t",
            "q": text,
        }
    )
    url = "https://translate.googleapis.com/translate_a/single?" + params
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
    with urllib.request.urlopen(req, context=ctx, timeout=45) as resp:
        data = json.loads(resp.read().decode("utf-8"))
    parts = []  # type: List[str]
    for block in data[0]:
        if block and isinstance(block[0], str):
            parts.append(block[0])
    return "".join(parts) if parts else text


def translate_text(text, ctx):
    # type: (str, ssl.SSLContext) -> str
    max_chunk = 4500
    if len(text) <= max_chunk:
        return translate_chunk(text, ctx)
    out = []  # type: List[str]
    start = 0
    while start < len(text):
        end = min(start + max_chunk, len(text))
        if end < len(text):
            cut = text.rfind(" ", start, end)
            if cut > start + 200:
                end = cut + 1
        piece = text[start:end]
        out.append(translate_chunk(piece, ctx))
        start = end
        time.sleep(0.12)
    return "".join(out)


def main():
    # type: () -> int
    keys = collect_all_keys()
    if not keys:
        sys.stderr.write("No translation keys found.\n")
        return 1

    en_obj = {k: k for k in keys}
    LANG_DIR.mkdir(parents=True, exist_ok=True)
    EN_PATH.write_text(
        json.dumps(en_obj, ensure_ascii=False, indent=4, sort_keys=True) + "\n",
        encoding="utf-8",
    )
    print("Wrote {} keys to {}".format(len(keys), str(EN_PATH.relative_to(ROOT))))

    existing_rw = {}  # type: Dict[str, str]
    if RW_PATH.is_file():
        try:
            existing_rw = json.loads(RW_PATH.read_text(encoding="utf-8"))
        except ValueError:
            pass

    ctx = ssl._create_unverified_context()

    def worker(k):
        # type: (str) -> Tuple[str, str]
        try:
            tw = translate_text(k, ctx)
            time.sleep(0.06)
            return k, tw
        except (urllib.error.URLError, TimeoutError, ValueError, IndexError, KeyError) as e:
            snippet = repr(k[:60])
            sys.stderr.write("WARN translate failed for {}: {}\n".format(snippet, e))
            return k, existing_rw.get(k, k)

    rw_obj = {}  # type: Dict[str, str]
    for k in keys:
        prev = existing_rw.get(k)
        if prev is not None and prev != k:
            rw_obj[k] = prev

    missing = [k for k in keys if k not in rw_obj]
    max_workers = 6
    with ThreadPoolExecutor(max_workers=max_workers) as ex:
        futures = {ex.submit(worker, k): k for k in missing}
        done = 0
        for fut in as_completed(futures):
            k, tw = fut.result()
            rw_obj[k] = tw
            done += 1
            if done % 25 == 0 or done == len(missing):
                print("  translated {}/{} …".format(done, len(missing)))

    for k in keys:
        if k not in rw_obj:
            rw_obj[k] = k

    RW_PATH.write_text(
        json.dumps(rw_obj, ensure_ascii=False, indent=4, sort_keys=True) + "\n",
        encoding="utf-8",
    )
    print("Wrote {} keys to {}".format(len(rw_obj), str(RW_PATH.relative_to(ROOT))))

    if set(en_obj.keys()) != set(rw_obj.keys()):
        sys.stderr.write("ERROR: en and rw key sets differ\n")
        return 1
    print("OK: en.json and rw.json have identical keys.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
