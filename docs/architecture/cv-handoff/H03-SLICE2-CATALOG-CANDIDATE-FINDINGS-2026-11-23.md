# H03 Slice 2 — Native shape catalog candidate findings (2026-11-23)

Status: preliminary characterization, **not** Writer-authored reference evidence or implementation approval.

## Procedure

Five programmatically constructed `draw:custom-shape` ODTs were saved and roundtripped through LibreOffice Writer 26.2.6.3. The shape geometry was explicitly supplied as `draw:enhanced-path`; these paths were **not** obtained from Writer's shape palette. Each roundtrip was converted with the repository's mandatory `tools/visual-regression/render.sh` and the PNGs visually inspected.

Ephemeral input and roundtrip files: `/tmp/h03-catalog-candidates-20261123/`. Render candidates: `tmp/visual-regression/` (not baselines).

## Observed outcomes

| Candidate | XML roundtrip | PNG appearance |
| --- | --- | --- |
| Triangle | one `draw:custom-shape` and enhanced geometry retained | recognizable upward triangle |
| Pentagon | retained | recognizable pentagon |
| Hexagon | retained | recognizable hexagon |
| Chevron | retained | recognizable right-pointing chevron |
| Five-point star (`star5`) | retained | recognizable five-point star |

All five LibreOffice conversions exited successfully. This establishes a feasible native rendering path for these **specific custom paths**, not Writer-authored preset equivalence, editability after interactive GUI manipulation, or full API compatibility.

## Next gate before catalog implementation

Acquire or create actual Writer-authored shape examples (including rounded rectangle, star, chevron and arrow variants), inspect their `draw:type`, enhanced paths, modifiers and styles, and compare to these candidates. Then settle the preset names and path contract before editing `ShapeGeometry`. Include focused PHPUnit and the visual regression tool in the implementation review. Do not generalize this result to all proposed presets.
