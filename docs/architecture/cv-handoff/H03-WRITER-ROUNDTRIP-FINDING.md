# H03 Writer roundtrip characterization — 2026-11-23

## Reproduced

- Using `tests/Fixtures/LegacySamples/templates/sample_textfeld.odt`, `setElement('test1', ...)` inserts a native `draw:custom-shape` inside `text:p / draw:frame / draw:text-box / text:p`.
- H03 `ShapeElement` with bitmap ellipse: source ODT has one native custom shape and `Pictures/WaltDietzney.png`; after headless LibreOffice ODT-to-ODT roundtrip, the custom shape count is zero. The output still has a fill-image declaration but the original bitmap filename is replaced by an internal Writer image name.
- Existing `CircularImageElement` using the *same* template placeholder and Writer roundtrip likewise changes from one custom shape to zero. This is a legacy-path comparison, not evidence that H03 alone caused the loss.
- New H03 PHPUnit packaging and repeat-save tests pass; they do not establish Writer roundtrip compatibility.

## Interpretation and next experiment

The shared failure is consistent with invalid or unsupported nested shape placement in `draw:text-box`, but the root cause is **not proven**. Compare an otherwise identical native shape in a top-level paragraph (using a namespace-preserving fixture), then compare with a Writer-authored reference shape. Do not alter legacy `CircularImageElement` or generic placeholder semantics until this distinction is established and characterized.

## Acceptance gate

H03 is not Writer-roundtrip approved. A passing Writer-native preservation test for the three agreed shapes and their fills, plus visual inspection, is required before completion.

## Follow-up: paragraph placement confirmed (23 November 2026)

A namespace-preserving copy of `sample_textfeld.odt` was prepared in `/tmp/h03-geometry-check/in/h03-paragraph-fixture.odt` by appending an ordinary `text:p` with `{{shape}}` directly under `office:body/office:text`. No source sample or `samples/output` artifact was modified. The H03 element was inserted via the public `setElement()` API and saved through the normal ODT package pipeline.

Headless Writer ODT-to-ODT conversion with isolated LibreOffice profiles preserved **one native `draw:custom-shape` before and after** for each of the three first-slice cases:

| Geometry | Source native shapes | Writer roundtrip native shapes | `draw:type` retained |
| --- | ---: | ---: | --- |
| Solid rectangle | 1 | 1 | `rectangle` |
| Bitmap ellipse | 1 | 1 | `ellipse` |
| Solid arrow-like contour | 1 | 1 | `arrow-contour` |

Writer renamed/repackaged the bitmap from `Pictures/WaltDietzney.png` to a generated internal `Pictures/...png` path. Therefore the output bitmap filename is **not** a stable identity across Writer roundtrips. This is not by itself resource loss; image presence and visual correctness require independent checks.

**Conclusion:** The earlier loss is specific to the nested textbox test setup under these conditions. Normal paragraph insertion preserves the native shape carrier through headless Writer save. This does not yet prove that the arrow path is rendered as intended, that bitmap appearance is correct, or that the result passes manual Writer visual review. Keep H03 human gate open.

## Visual diagnosis and corrective slice (23 November 2026)

The existing `tools/visual-regression/render.sh` was used to inspect the generated paragraph-anchored rectangle, bitmap ellipse and contour. Initially the bitmap ellipse showed only the template's default blue graphic fill, despite a valid packaged bitmap and fill-image declaration. A controlled package experiment moving only the shape's graphic style from common `styles.xml` to `content.xml` automatic styles caused Writer to render the bitmap. Removing the parent style or bitmap width/height attributes while retaining the common-style placement did **not** resolve the missing bitmap. H03's shape style requirement has therefore been changed to `SCOPE_AUTOMATIC` / `PART_CONTENT`, while the fill-image resource declaration remains in `styles.xml`.

The previous `arrow-contour` preset was copied from E009's `flowchart-stored-data` contour and was incorrectly described as an arrow. The public H03 preset key is retained, but the native carrier is now `right-arrow` with a simple polygonal arrow path. This is an intentional H03-only semantic correction, not a modification of E009's recorded reference.

**Remaining visual issue:** The bitmap is now visible but appears vertically flattened in the present paragraph-based fixture, despite a `3cm` by `3cm` custom-shape box in `content.xml`. Do not mark bitmap geometry as visually accepted until the placement/style interaction is isolated and a roundtrip/render comparison confirms correct proportions. The old template also contains a large unrelated blue textbox, which is not part of H03 and should not be confused with the new shape.

## Follow-up: bitmap proportions (23 November 2026)

Reproduced the flattened bitmap ellipse in the actual H03-generated paragraph fixture. Four isolated package variants (without enhanced path, with polygon path, and with two alternate arc endpoints) all produced the same flattened image, so the contour path alone is not the cause. Writer ODT-to-ODT roundtrip retains the shape at approximately 2.999cm × 2.999cm, and preserves the ellipse enhanced path. This separates the declared native object geometry from the visible bitmap-fill proportions. The next investigation must compare Writer-authored bitmap fill style properties and/or the PDF drawing geometry before changing layout semantics. No speculative bitmap correction has been committed.

## Isolated Writer style-attribute comparison (23 November 2026)

Starting with the same H03 bitmap ellipse package, five isolated temporary ODT variants were generated without modifying engine code: explicit horizontal/vertical position, text wrapping, flow-with-text, minimum size, and their combined position/wrap/flow attributes. Each variant rendered successfully using `tools/visual-regression/render.sh`, but **none corrected the visibly flattened bitmap ellipse**. Therefore the additional positioning/wrapping attributes found in E009 are not sufficient to explain or fix this defect. The E009 Writer-authored bitmap shape uses `draw:fill="bitmap"`, `draw:fill-image-name` and `style:repeat="stretch"`, as does H03. The E009 Writer-authored `e009-3.odt` also rendered successfully as an independent baseline. No speculative layout/style change was applied to the implementation.

Next: compare the rendered bounding box against the actual `svg:width`/`svg:height`, determine whether the apparent flattening comes from the source image itself or the shape geometry, and create a minimal blank-page Writer-native bitmap ellipse fixture to eliminate interference from the legacy textbox template.

## Root cause and minimal correction: inherited `Frame` graphic style (23 November 2026)

A minimal fixture was produced by removing all children of `office:body/office:text` from a temporary copy of the legacy sample and inserting exactly one ordinary paragraph placeholder. The original fixture and `samples/output` were not modified. A 3cm × 3cm bitmap ellipse inserted through the public API still rendered as a flattened oval, proving that nesting in the legacy textbox was **not** responsible for the aspect-ratio defect.

Controlled variants on that minimal document tested explicit `svg:x`/`svg:y`, no parent graphic style, solid fill, 6cm height, and tiled bitmap fill. **Only removing `style:parent-style-name="Frame"` restored a circular 3cm × 3cm bitmap ellipse with undistorted proportions**. The solid ellipse with inherited `Frame` was also flattened, confirming the problem affected the shape geometry and not just bitmap scaling. The cause was unintended inheritance from the template's existing `Frame` graphic style.

H03 `ShapeElement` now creates its own automatic graphic style without a `Frame` parent; no shared style or legacy element implementation was changed. The focused integration test asserts the H03 bitmap graphic style has no parent. The corrected minimal bitmap ellipse renders circularly; a headless Writer ODT-to-ODT roundtrip preserves its native `draw:custom-shape` (1 before, 1 after). Focused tests: 6 passed, 37 assertions. Final visual editability gate and full post-fix regression suite remain pending.
