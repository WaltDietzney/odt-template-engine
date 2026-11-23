# H03 Slice 2 — Installed LibreOffice shape reference audit (2026-11-23)

## Scope and evidence quality

Searched ODT fixtures under `tests/fixtures/` and `samples/templates/` for `draw:enhanced-geometry` in `content.xml` and `styles.xml`: no matching shapes found. This does not prove none exist in other repository paths.

Examined locally installed LibreOffice templates under `/usr/lib/libreoffice/share/template/`. These are LibreOffice-shipped **Impress presentation templates (`.otp`)**, not Writer-authored ODT documents. Therefore they provide native ODF geometry references but do not close the Writer-GUI authoring/editability gate.

## Concrete reference comparison

- LibreOffice template `common/presnt/Candy.otp` contains `draw:type="hexagon"`, `svg:viewBox="0 0 21600 21600"`, and `draw:enhanced-path="M 5400 0 L 16200 0 21600 10800 16200 21600 5400 21600 0 10800 Z N"`. This path **exactly matches** our programmatic hexagon candidate that passed ODT roundtrip and visual regression.
- LibreOffice template `common/presnt/Growing_Liberty.otp` contains `draw:type="left-arrow"` with `draw:modifiers="5400 5400"` and a formula-based enhanced path (`?f0`, `?f1`, `?f2`). The current simplified candidate approach cannot claim equivalence to this native adjustable preset; associated equations/handles need inspection.
- Other found types include rectangle, ellipse, flowchart-alternate-process, and mso-spt100. No installed template references for the tested triangle, pentagon, chevron or star were found in this scan.

## Decision and next gate

The hexagon has a verified LibreOffice-shipped geometry match, but no Writer-authored editability proof. Do not claim the broader catalog is reference-verified. Before implementing the remaining presets, create or acquire actual Writer-authored ODT references and compare `draw:type`, enhanced path, modifiers, equations and handles. Preserve `tools/visual-regression/render.sh` as mandatory.

No PHP code, baselines, or sample outputs were changed in this audit.
