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
