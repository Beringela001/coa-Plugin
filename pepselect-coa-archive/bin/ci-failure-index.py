#!/usr/bin/env python3
"""Prints a compact one-line-per-failure index from a PHPUnit JUnit XML log.

The full PHPUnit log needs authentication to read from a GitHub Actions run, and
the tail alone truncates a long failure list. This emits just what a triage
write-up needs: the test, where it lives, and the first line of the assertion.
"""
import re
import sys
import xml.etree.ElementTree as ET

path = sys.argv[1] if len(sys.argv) > 1 else "/tmp/junit.xml"

try:
    root = ET.parse(path).getroot()
except Exception as exc:  # noqa: BLE001 - any parse problem should be visible, not fatal
    print(f"could not parse {path}: {exc}")
    sys.exit(0)

rows = []
for case in root.iter("testcase"):
    for kind in ("failure", "error"):
        node = case.find(kind)
        if node is None:
            continue
        text = (node.text or "").strip()
        # The trailing "/path/to/file.php:NN" lines carry the real location.
        locations = re.findall(r"([^\s]+\.php):(\d+)", text)
        location = ""
        for candidate, line in locations:
            if "/tests/" in candidate or candidate.endswith("test.php"):
                location = f"{candidate.split('/')[-1]}:{line}"
                break
        if not location and locations:
            location = f"{locations[-1][0].split('/')[-1]}:{locations[-1][1]}"
        if not location:
            location = f"{case.get('file', '?').split('/')[-1]}:{case.get('line', '?')}"
        first = next((ln.strip() for ln in text.splitlines() if ln.strip()), "")
        rows.append((kind.upper(), case.get("class", "?"), case.get("name", "?"), location, first[:130]))
        break

print(f"TOTAL {len(rows)} (failures+errors)")
for kind, cls, name, location, first in sorted(rows, key=lambda r: (r[1], r[2])):
    print(f"{kind} | {cls}::{name} | {location} | {first}")
