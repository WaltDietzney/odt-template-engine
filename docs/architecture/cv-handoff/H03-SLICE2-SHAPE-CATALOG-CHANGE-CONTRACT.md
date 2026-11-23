# H03 Slice 2 — Shape catalog and CV skill bars: architecture/change contract (draft)
Status: planning draft; no implementation authorized by this document. Intended base: `develop` after H03 Slice 1 merge; dedicated feature branch.
Status: **Draft for review, not approved for implementation**. Base: `forgejo/develop` at `ae1cfa1` (H03 Slice 1 merged); branch: `feature/h03-shape-catalog`. Existing APIs: `ShapeGeometry::preset()`, `ShapeAppearance`, `ShapeElement`, `DrawingLayout`, `DrawingLayoutProjector`.
## Product outcome

Support reusable Writer-editable shapes and CV skill indicators, avoiding rasterized bars. Keep LibreOffice as visual designer. Skill value semantics are application-provided and must not silently be inferred from an arbitrary label.

## Scope proposal

1. Preset catalog: existing rectangle, ellipse, diamond, right-arrow; add rounded rectangle, triangle, hexagon, pentagon, chevron, left/up/down arrows, star, cross, speech bubble. Add shapes incrementally based on Writer-authored ODF references, not presumed path syntax. Avoid promising perfect editability until roundtrip and manual checks.
2. Skill bars: a background track and foreground fill, with normalized 0..100 values, width computation, configurable length/height/gap, fill colors, and optional textual label/value. Consider segmented bars and dot indicators as optional follow-up only after basic bar passes.
3. Layout and editability: first characterize side-by-side and overlay placement using paragraph-anchored `draw:custom-shape`, text flow, relative positioning, save/reopen, and different font/paragraph contexts. No generic grouping API before evidence. Do not introduce mutable global layout state.
4. Style and compatibility: automatic graphic styles scoped to content.xml without inherited Frame; reuse ShapeAppearance and resource collector; stable render/save; no legacy shape behavior changes.

## Evidence gates and implementation slices

A. Characterize Writer-authored examples for rounded rectangle, triangle, star, chevron and two overlapping rectangles; verify ODF geometry and placement in source, roundtrip and PDF. Decide which can be added safely as presets.
B. Add a first batch of presets with geometry validation and focused XML + Writer tests; keep names and behavior stable.
C. Design and implement a CV-neutral skill-bar composition API only after proving positioning. Validate percent range, physical dimensions, and zero/full values. Characterize repeat-save and multiple bars in one document.
D. Integration and visual regression across different templates, full composer test, lint, diff check, user LibreOffice human gate. Record known unsupported configurations.

## Out of scope for this slice

Arbitrary user-provided enhanced-path expressions, generic object grouping, rotation, full connector/line endpoint API, chart engine, CV generator data-contract changes, broad template-authoring UX changes. These require separate semantics and evidence.

## Open decisions

- Are skill bars single semantic composite elements or document-level helpers that insert two independently editable shapes? Choose based on Writer placement evidence and existing structured insertion contracts.
- Should the initial skill bar expose only a normalized value (0..100), or support an explicit scale and label? Default proposal: normalized 0..100, label managed separately until text-layout proof.
- Is a rounded rectangle essential for initial bar appearance, or should plain rectangles ship first? Default: plain rectangles first; rounded corners after verified ODF/Writer support.
