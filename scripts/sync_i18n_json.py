#!/usr/bin/env python3
"""
Extract __('...') / __("...") string keys from PHP and Blade sources,
write resources/lang/en.json (keys = English values),
and resources/lang/rw.json (same keys, Kinyarwanda via Google translate endpoint).

Run from repo root:
  python3 scripts/sync_i18n_json.py

Requires outbound HTTPS (translate.googleapis.com).
"""

from __future__ import annotations

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


def unescape_php_single(raw: str) -> str:
    out: list[str] = []
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


def unescape_php_double(raw: str) -> str:
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


def iter_source_files() -> list[Path]:
    out: list[Path] = []
    for base in SCAN_DIRS:
        if not base.is_dir():
            continue
        for p in base.rglob("*"):
            if p.suffix in EXTENSIONS or p.name.endswith(".blade.php"):
                out.append(p)
    return sorted(out)


def extract_keys_from_text(text: str) -> set[str]:
    found: set[str] = set()
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
        # Skip dynamic / concatenated keys (heuristic)
        if "$" in key or "{" in key or "{{" in key:
            continue
        found.add(key)
    return found


def collect_all_keys() -> list[str]:
    all_keys: set[str] = set()
    for path in iter_source_files():
        try:
            content = path.read_text(encoding="utf-8", errors="replace")
        except OSError:
            continue
        all_keys |= extract_keys_from_text(content)
    return sorted(all_keys)


def translate_chunk(text: str, ctx: ssl.SSLContext) -> str:
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
    url = f"https://translate.googleapis.com/translate_a/single?{params}"
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
    with urllib.request.urlopen(req, context=ctx, timeout=45) as resp:
        data = json.loads(resp.read().decode("utf-8"))
    # [[["translated",...]], ...]
    parts: list[str] = []
    for block in data[0]:
        if block and isinstance(block[0], str):
            parts.append(block[0])
    return "".join(parts) if parts else text


def translate_text(text: str, ctx: ssl.SSLContext) -> str:
    max_chunk = 4500
    if len(text) <= max_chunk:
        return translate_chunk(text, ctx)
    out: list[str] = []
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


def main() -> int:
    keys = collect_all_keys()
    if not keys:
        print("No translation keys found.", file=sys.stderr)
        return 1

    en_obj = {k: k for k in keys}
    LANG_DIR.mkdir(parents=True, exist_ok=True)
    EN_PATH.write_text(
        json.dumps(en_obj, ensure_ascii=False, indent=4, sort_keys=True) + "\n",
        encoding="utf-8",
    )
    print(f"Wrote {len(keys)} keys to {EN_PATH.relative_to(ROOT)}")

    existing_rw: dict[str, str] = {}
    if RW_PATH.is_file():
        try:
            existing_rw = json.loads(RW_PATH.read_text(encoding="utf-8"))
        except json.JSONDecodeError:
            pass

    ctx = ssl._create_unverified_context()

    def worker(k: str) -> tuple[str, str]:
        try:
            tw = translate_text(k, ctx)
            time.sleep(0.06)
            return k, tw
        except (urllib.error.URLError, TimeoutError, json.JSONDecodeError, IndexError, KeyError) as e:
            print(f"WARN translate failed for {k[:60]!r}: {e}", file=sys.stderr)
            return k, existing_rw.get(k, k)

    rw_obj: dict[str, str] = {}
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
                print(f"  translated {done}/{len(missing)} …")

    for k in keys:
        if k not in rw_obj:
            rw_obj[k] = k

    RW_PATH.write_text(
        json.dumps(rw_obj, ensure_ascii=False, indent=4, sort_keys=True) + "\n",
        encoding="utf-8",
    )
    print(f"Wrote {len(rw_obj)} keys to {RW_PATH.relative_to(ROOT)}")

    if set(en_obj.keys()) != set(rw_obj.keys()):
        print("ERROR: en and rw key sets differ", file=sys.stderr)
        return 1
    print("OK: en.json and rw.json have identical keys.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
