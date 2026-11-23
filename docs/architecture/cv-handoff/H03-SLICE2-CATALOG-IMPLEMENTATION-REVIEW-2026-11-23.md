# H03 Slice 2 — Shape Catalog implementation review (2026-11-23)

## Implemented in feature worktree (uncommitted)

- Existing rectangle, ellipse, diamond and arrow-contour remain available.
- Added `hexagon`, `isosceles-triangle`, `ring`, `flowchart-stored-data`, `flowchart-off-page-connector`, `flowchart-punched-tape`, `pentagon`, `chevron`, `star5`.
- `ShapeElement` now emits optional enhanced-geometry attributes, equations and handles for the Writer-authored E009 adjustable presets; no generic grouping API introduced.
- E009-backed presets: triangle, ring and the three flowchart shapes. The hexagon path has an exact match in an installed LibreOffice Impress template. Pentagon, chevron and star5 use characterized **programmatically authored** paths and must not be described as Writer-palette-verified.

## Evidence

- Clean-template Engine ODTs rendered through `tools/visual-regression/render.sh`; visually inspected triangle, ring, hexagon, three flowchart shapes, pentagon, chevron and star5: all show recognizable expected contours.
- Writer headless ODT roundtrip preserved native shape types for all nine new shapes. Triangle retained eight equations and one handle; ring retained two equations and one handle.
- The initial opaque blue rectangle was existing content in the legacy sample template, not an Engine-generated shape. Clean template used for subsequent visual tests.
- Focused unit and integration tests have passed. Full composer test and repository-wide lint results should be recorded separately when completed.

## Open review gates (do not merge)

1. Writer-authored native references for rounded rectangle, cross, speech bubble and left/up/down arrows. No unsupported geometry should be silently invented or declared fully Writer-editable.
2. Writer GUI manual editability review, especially pentagon, chevron and star5 (their XML roundtrip and PDF render are **not** GUI-editability proof).
3. Confirm public API semantics and compatibility; consider whether to keep unverified candidates exposed before review.
4. Complete full preflight and human visual gate, then conduct the agreed **intermediate review** before SkillBar.

Do not touch `samples/output/*.odt`, visual baselines or LibreOffice lock files.
